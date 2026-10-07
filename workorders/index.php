<?php

require_once '../config/database.php';
require_once '../config/auth.php';

require_login();

$user = current_user();

$user_id = (int)$user['id'];
$user_role = $user['role'];

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| UPDATE WORK ORDER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $assigned_to_input = $_POST['assigned_to'] ?? '';

    $part = (int)($_POST['spare_part_id'] ?? 0);
    $qty = (int)($_POST['spare_qty'] ?? 0);

    /*
     * Allowed statuses
     */
    $allowed_statuses = [
        'reported',
        'assigned',
        'in_progress',
        'closed'
    ];

    try {

        /*
         * Basic validation
         */
        if ($id <= 0) {
            throw new Exception('Invalid work order.');
        }

        if (!in_array($status, $allowed_statuses, true)) {
            throw new Exception('Invalid work order status.');
        }

        if ($qty < 0) {
            throw new Exception(
                'Spare quantity cannot be negative.'
            );
        }

        /*
         * Start transaction
         */
        $pdo->beginTransaction();

        /*
         * Lock the work order while updating.
         */
        $orderStmt = $pdo->prepare("
            SELECT
                id,
                status,
                assigned_to,
                spare_part_id,
                spare_qty
            FROM work_orders
            WHERE id = ?
            FOR UPDATE
        ");

        $orderStmt->execute([$id]);

        $order = $orderStmt->fetch();

        if (!$order) {
            throw new Exception(
                'Work order not found.'
            );
        }

        $old_status = $order['status'];
        $old_assigned_to = $order['assigned_to'];

        /*
         * CLOSED WORK ORDERS ARE FINAL.
         *
         * They cannot be reopened or modified.
         */
        if ($old_status === 'closed') {
            throw new Exception(
                'Closed work orders cannot be modified.'
            );
        }

        /*
         * Technician permissions
         *
         * A technician can update only work orders
         * assigned to that technician.
         */
        if ($user_role === 'technician') {

            if (
                !$old_assigned_to ||
                (int)$old_assigned_to !== $user_id
            ) {
                throw new Exception(
                    'You can only update work orders assigned to you.'
                );
            }

            /*
             * Technician cannot change assignment.
             */
            $assigned_to = (int)$old_assigned_to;

            /*
             * Technician cannot manipulate spare parts.
             *
             * Keep whatever was already stored.
             */
            $part = $order['spare_part_id']
                ? (int)$order['spare_part_id']
                : 0;

            $qty = (int)$order['spare_qty'];

        } else {

            /*
             * Admin and Supervisor can assign technicians.
             */
            $assigned_to = null;

            if ($assigned_to_input !== '') {

                $assigned_to = (int)$assigned_to_input;

                /*
                 * Verify selected user is an active technician.
                 */
                $techStmt = $pdo->prepare("
                    SELECT id
                    FROM users
                    WHERE id = ?
                    AND role = 'technician'
                    AND is_active = 1
                    LIMIT 1
                ");

                $techStmt->execute([
                    $assigned_to
                ]);

                if (!$techStmt->fetch()) {
                    throw new Exception(
                        'Invalid technician selected.'
                    );
                }
            }

            /*
             * If no spare part is selected,
             * quantity must be zero.
             */
            if ($part <= 0) {
                $part = null;
                $qty = 0;
            }
        }

        /*
         * Assigned / In Progress orders
         * must have a technician.
         */
        if (
            in_array(
                $status,
                ['assigned', 'in_progress'],
                true
            )
            &&
            !$assigned_to
        ) {
            throw new Exception(
                'Please assign a technician before using this status.'
            );
        }

        /*
         * Spare-part deduction happens ONLY when
         * the work order changes from a non-closed
         * status to closed.
         */
        if (
            $status === 'closed'
            &&
            $old_status !== 'closed'
            &&
            $part
            &&
            $qty > 0
        ) {

            /*
             * Lock spare-part row.
             */
            $spareStmt = $pdo->prepare("
                SELECT
                    quantity,
                    part_name
                FROM spare_parts
                WHERE id = ?
                FOR UPDATE
            ");

            $spareStmt->execute([
                $part
            ]);

            $spare = $spareStmt->fetch();

            if (!$spare) {
                throw new Exception(
                    'Selected spare part does not exist.'
                );
            }

            /*
             * Check available stock.
             */
            if ((int)$spare['quantity'] < $qty) {
                throw new Exception(
                    'Insufficient spare-part stock.'
                );
            }

            /*
             * Deduct stock.
             */
            $updateSpare = $pdo->prepare("
                UPDATE spare_parts
                SET quantity = quantity - ?
                WHERE id = ?
            ");

            $updateSpare->execute([
                $qty,
                $part
            ]);
        }

        /*
         * Update work order.
         */
        if ($status === 'closed') {

            $update = $pdo->prepare("
                UPDATE work_orders
                SET
                    status = ?,
                    assigned_to = ?,
                    spare_part_id = ?,
                    spare_qty = ?,
                    closed_at = COALESCE(
                        closed_at,
                        NOW()
                    )
                WHERE id = ?
            ");

            $update->execute([
                $status,
                $assigned_to,
                $part,
                $qty,
                $id
            ]);

        } else {

            $update = $pdo->prepare("
                UPDATE work_orders
                SET
                    status = ?,
                    assigned_to = ?,
                    spare_part_id = ?,
                    spare_qty = ?,
                    closed_at = NULL
                WHERE id = ?
            ");

            $update->execute([
                $status,
                $assigned_to,
                $part,
                $qty,
                $id
            ]);
        }

        /*
         * Commit everything.
         */
        $pdo->commit();

        $success = 'Work order updated successfully.';

    } catch (Throwable $e) {

        /*
         * Roll back if something failed.
         */
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $e->getMessage();
    }
}


/*
|--------------------------------------------------------------------------
| FETCH WORK ORDERS
|--------------------------------------------------------------------------
*/

if ($user_role === 'technician') {

    /*
     * Technicians can see only their own
     * assigned work orders.
     */
    $stmt = $pdo->prepare("
        SELECT
            w.*,
            m.machine_code,
            m.name AS machine_name,
            u.name AS technician
        FROM work_orders w
        JOIN machines m
            ON m.id = w.machine_id
        LEFT JOIN users u
            ON u.id = w.assigned_to
        WHERE w.assigned_to = ?
        ORDER BY w.id DESC
    ");

    $stmt->execute([
        $user_id
    ]);

    $orders = $stmt->fetchAll();

} else {

    /*
     * Admin and Supervisor can see all
     * work orders.
     */
    $orders = $pdo->query("
        SELECT
            w.*,
            m.machine_code,
            m.name AS machine_name,
            u.name AS technician
        FROM work_orders w
        JOIN machines m
            ON m.id = w.machine_id
        LEFT JOIN users u
            ON u.id = w.assigned_to
        ORDER BY w.id DESC
    ")->fetchAll();
}


/*
|--------------------------------------------------------------------------
| ACTIVE TECHNICIANS
|--------------------------------------------------------------------------
*/

$techs = $pdo->query("
    SELECT
        id,
        name
    FROM users
    WHERE role = 'technician'
    AND is_active = 1
    ORDER BY name
")->fetchAll();


/*
|--------------------------------------------------------------------------
| SPARE PARTS
|--------------------------------------------------------------------------
*/

$parts = $pdo->query("
    SELECT
        id,
        part_name,
        quantity
    FROM spare_parts
    ORDER BY part_name
")->fetchAll();

?>

<!doctype html>

<html>

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>Work Orders</title>

<link
    rel="stylesheet"
    href="../assets/style.css"
>

</head>


<body>

<div class="app">


<!-- SIDEBAR -->

<aside class="sidebar">

<div class="logo">
    ⚙ Smart Maintenance
</div>


<nav class="nav">

<a href="../dashboard.php">
    Dashboard
</a>

<a href="../machines/index.php">
    Machines
</a>

<a href="../maintenance/index.php">
    Maintenance
</a>

<a
    class="active"
    href="index.php"
>
    Work Orders
</a>

<a href="../inventory/index.php">
    Spare Parts
</a>

<a href="../reports/index.php">
    Reports
</a>

<a href="../auth/logout.php">
    Logout
</a>

</nav>

</aside>


<!-- MAIN -->

<main class="main">


<!-- TOP BAR -->

<div class="topbar">

<div>

<h1>
    Work Orders
</h1>

<div class="muted">
    Manage maintenance work orders
</div>

</div>

</div>


<!-- ERROR -->

<?php if ($error): ?>

<div class="alert danger">

<?=htmlspecialchars($error)?>

</div>

<?php endif; ?>


<!-- SUCCESS -->

<?php if ($success): ?>

<div class="alert success">

<?=htmlspecialchars($success)?>

</div>

<?php endif; ?>


<!-- WORK ORDER TABLE -->

<div class="card">

<h3>
    Work Order List
</h3>


<div style="overflow-x:auto">

<table>

<thead>

<tr>

<th>
    ID
</th>

<th>
    Machine
</th>

<th>
    Title
</th>

<th>
    Priority
</th>

<th>
    Status
</th>

<th>
    Technician
</th>

<th>
    Spare Part
</th>

<th>
    Quantity
</th>

<th>
    Action
</th>

</tr>

</thead>


<tbody>


<?php foreach ($orders as $o): ?>

<tr>


<!-- ID -->

<td>

#<?= (int)$o['id'] ?>

</td>


<!-- MACHINE -->

<td>

<?=htmlspecialchars(
    $o['machine_code']
)?>

<br>

<small>

<?=htmlspecialchars(
    $o['machine_name']
)?>

</small>

</td>


<!-- TITLE -->

<td>

<strong>

<?=htmlspecialchars(
    $o['title']
)?>

</strong>


<?php if (!empty($o['description'])): ?>

<br>

<small>

<?=htmlspecialchars(
    $o['description']
)?>

</small>

<?php endif; ?>

</td>


<!-- PRIORITY -->

<td>

<?=htmlspecialchars(
    ucfirst($o['priority'])
)?>

</td>


<!-- STATUS -->

<td>

<?=htmlspecialchars(
    ucwords(
        str_replace(
            '_',
            ' ',
            $o['status']
        )
    )
)?>

</td>


<!-- TECHNICIAN -->

<td>

<?=htmlspecialchars(
    $o['technician']
    ??
    'Not assigned'
)?>

</td>


<!-- SPARE PART -->

<td>

<?php

$spare_name = 'None';

foreach ($parts as $p) {

    if (
        (int)$p['id']
        ===
        (int)$o['spare_part_id']
    ) {

        $spare_name = $p['part_name'];

        break;
    }
}

?>

<?=htmlspecialchars(
    $spare_name
)?>

</td>


<!-- QUANTITY -->

<td>

<?= (int)$o['spare_qty'] ?>

</td>


<!-- ACTION -->

<td>


<?php if ($o['status'] === 'closed'): ?>

<!--
    CLOSED WORK ORDERS ARE FINAL.
    No update form is shown.
-->

<strong>
    Closed
</strong>


<?php else: ?>


<form
    method="post"
    style="min-width:240px"
>


<input
    type="hidden"
    name="id"
    value="<?= (int)$o['id'] ?>"
>


<!-- STATUS -->

<label>
    Status
</label>


<select
    name="status"
    required
>

<option
    value="reported"
    <?=$o['status']==='reported'
        ? 'selected'
        : ''
    ?>
>
    Reported
</option>


<option
    value="assigned"
    <?=$o['status']==='assigned'
        ? 'selected'
        : ''
    ?>
>
    Assigned
</option>


<option
    value="in_progress"
    <?=$o['status']==='in_progress'
        ? 'selected'
        : ''
    ?>
>
    In Progress
</option>


<option
    value="closed"
>
    Closed
</option>


</select>


<?php if ($user_role !== 'technician'): ?>


<br>
<br>


<!-- TECHNICIAN -->

<label>
    Technician
</label>


<select
    name="assigned_to"
>

<option value="">
    Not assigned
</option>


<?php foreach ($techs as $t): ?>

<option
    value="<?= (int)$t['id'] ?>"
    <?=(
        (int)$o['assigned_to']
        ===
        (int)$t['id']
    )
        ? 'selected'
        : ''
    ?>
>

<?=htmlspecialchars(
    $t['name']
)?>

</option>

<?php endforeach; ?>


</select>


<br>
<br>


<!-- SPARE PART -->

<label>
    Spare Part
</label>


<select
    name="spare_part_id"
>


<option value="">
    None
</option>


<?php foreach ($parts as $p): ?>

<option
    value="<?= (int)$p['id'] ?>"
    <?=(
        (int)$o['spare_part_id']
        ===
        (int)$p['id']
    )
        ? 'selected'
        : ''
    ?>
>

<?=htmlspecialchars(
    $p['part_name']
)?>

(
<?= (int)$p['quantity'] ?>
in stock)

</option>

<?php endforeach; ?>


</select>


<br>
<br>


<!-- SPARE QUANTITY -->

<label>
    Spare Quantity
</label>


<input
    type="number"
    name="spare_qty"
    min="0"
    value="<?= (int)$o['spare_qty'] ?>"
>


<?php endif; ?>


<br>
<br>


<!-- UPDATE -->

<button
    type="submit"
    class="btn primary"
>

Update

</button>


</form>


<?php endif; ?>


</td>


</tr>


<?php endforeach; ?>


<?php if (!$orders): ?>

<tr>

<td colspan="9">

No work orders found.

</td>

</tr>

<?php endif; ?>


</tbody>

</table>

</div>

</div>


</main>

</div>

</body>

</html>