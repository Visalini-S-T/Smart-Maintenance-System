<?php
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/config/auth.php';

require_login();


/* =========================================================
   BASIC DASHBOARD COUNTS
   ========================================================= */

$total = (int)$pdo->query("
    SELECT COUNT(*)
    FROM machines
")->fetchColumn();


$running = (int)$pdo->query("
    SELECT COUNT(*)
    FROM machines
    WHERE status = 'running'
")->fetchColumn();


$breakdowns = (int)$pdo->query("
    SELECT COUNT(*)
    FROM breakdown_logs
    WHERE failure_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetchColumn();


$downtime = (float)$pdo->query("
    SELECT COALESCE(SUM(downtime_hours), 0)
    FROM breakdown_logs
    WHERE failure_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetchColumn();



/* =========================================================
   MACHINE HEALTH & RELIABILITY
   ========================================================= */

$machines = $pdo->query("
    SELECT
        m.*,

        (
            SELECT COUNT(*)
            FROM breakdown_logs b
            WHERE b.machine_id = m.id
        ) AS failures,

        (
            SELECT COALESCE(SUM(b.downtime_hours), 0)
            FROM breakdown_logs b
            WHERE b.machine_id = m.id
        ) AS downtime

    FROM machines m

    ORDER BY m.id
")->fetchAll();



/* =========================================================
   MAINTENANCE ALERTS
   ========================================================= */

$overdue = $pdo->query("
    SELECT
        ms.*,
        m.machine_code,
        m.name

    FROM maintenance_schedule ms

    JOIN machines m
        ON m.id = ms.machine_id

    WHERE
        ms.scheduled_date < CURDATE()
        AND ms.status <> 'completed'

    ORDER BY ms.scheduled_date
")->fetchAll();



/* =========================================================
   REPEAT BREAKDOWN ALERTS
   Machines with 2 or more breakdowns
   during the last 30 days
   ========================================================= */

$repeat_breakdowns = $pdo->query("
    SELECT
        m.machine_code,
        m.name,
        COUNT(b.id) AS breakdown_count,
        COALESCE(SUM(b.downtime_hours), 0) AS total_downtime

    FROM machines m

    JOIN breakdown_logs b
        ON b.machine_id = m.id

    WHERE
        b.failure_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)

    GROUP BY
        m.id,
        m.machine_code,
        m.name

    HAVING COUNT(b.id) >= 2

    ORDER BY breakdown_count DESC

    LIMIT 10
")->fetchAll();



/* =========================================================
   DYNAMIC DOWNTIME TREND
   Last 28 days divided into 4 weeks
   ========================================================= */

$weekly_downtime = [0, 0, 0, 0];


$weekly = $pdo->query("
    SELECT
        FLOOR(
            DATEDIFF(
                CURDATE(),
                DATE(failure_date)
            ) / 7
        ) AS week_index,

        COALESCE(
            SUM(downtime_hours),
            0
        ) AS total_downtime

    FROM breakdown_logs

    WHERE
        failure_date >= DATE_SUB(
            NOW(),
            INTERVAL 28 DAY
        )

    GROUP BY week_index
")->fetchAll();


foreach ($weekly as $week) {

    $index = (int)$week['week_index'];

    if ($index >= 0 && $index <= 3) {

        $weekly_downtime[3 - $index]
            = (float)$week['total_downtime'];
    }
}



/* =========================================================
   MACHINE HEALTH SCORE
   ========================================================= */

function health_score($m)
{
    $availability = max(
        0,
        min(
            100,
            (
                (
                    $m['planned_hours']
                    - $m['downtime']
                )
                /
                max(
                    1,
                    $m['planned_hours']
                )
            ) * 100
        )
    );


    $repeatPenalty = min(
        30,
        max(
            0,
            (
                (int)$m['failures']
                - 1
            ) * 8
        )
    );


    return max(
        0,
        round(
            ($availability * 0.75)
            +
            (
                (100 - $repeatPenalty)
                * 0.25
            )
        )
    );
}

?>

<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>
    Dashboard - Smart Maintenance
</title>


<link
    rel="stylesheet"
    href="assets/style.css"
>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>


<body>


<div class="app">


<!-- =====================================================
     SIDEBAR
     ===================================================== -->

<aside class="sidebar">

    <div class="logo">
        ⚙ Smart Maintenance
    </div>


    <nav class="nav">


        <a
            class="active"
            href="dashboard.php"
        >
            Dashboard
        </a>


        <a href="machines/index.php">
            Machines
        </a>


        <a href="maintenance/index.php">
            Maintenance
        </a>


        <a href="workorders/index.php">
            Work Orders
        </a>


        <a href="inventory/index.php">
            Spare Parts
        </a>


        <a href="reports/index.php">
            Reports
        </a>


        <?php if($_SESSION['role'] === 'admin'): ?>

            <a href="database/seed.php">
                Seed Demo Data
            </a>

        <?php endif; ?>


        <a href="auth/logout.php">
            Logout
        </a>


    </nav>

</aside>



<!-- =====================================================
     MAIN
     ===================================================== -->

<main class="main">


<!-- TOP BAR -->

<div class="topbar">


    <div>

        <h1>
            Maintenance Dashboard
        </h1>


        <div class="muted">
            Factory reliability overview
        </div>

    </div>


    <div class="user-chip">

        <?=htmlspecialchars($_SESSION['name'])?>

        ·

        <?=htmlspecialchars(
            ucfirst($_SESSION['role'])
        )?>

    </div>


</div>



<!-- =====================================================
     KPI CARDS
     ===================================================== -->

<div class="grid kpis">


    <div class="card">

        <div class="muted">
            Total Machines
        </div>


        <div class="kpi">
            <?=$total?>
        </div>

    </div>



    <div class="card">

        <div class="muted">
            Running
        </div>


        <div class="kpi">
            <?=$running?>
        </div>

    </div>



    <div class="card">

        <div class="muted">
            30-Day Breakdowns
        </div>


        <div class="kpi">
            <?=$breakdowns?>
        </div>

    </div>



    <div class="card">

        <div class="muted">
            30-Day Downtime
        </div>


        <div class="kpi">

            <?=number_format(
                $downtime,
                1
            )?>

            h

        </div>

    </div>


</div>



<!-- =====================================================
     DOWNTIME TREND + MAINTENANCE ALERTS
     ===================================================== -->

<div
    class="grid two"
    style="margin-top:16px"
>


    <!-- DOWNTIME TREND -->

    <div class="card">


        <h3>
            Downtime Trend
        </h3>


        <canvas
            id="downtimeChart"
            height="120"
        ></canvas>


    </div>



    <!-- MAINTENANCE ALERTS -->

    <div class="card">


        <h3>
            Maintenance Alerts
        </h3>


        <?php if(!$overdue): ?>


            <div class="alert success">

                No overdue maintenance.

            </div>


        <?php else: ?>


            <?php foreach($overdue as $o): ?>


                <div class="alert warning">


                    <strong>

                        <?=htmlspecialchars(
                            $o['machine_code']
                        )?>

                    </strong>


                    —


                    <?=htmlspecialchars(
                        $o['maintenance_type']
                    )?>


                    <br>


                    <small>

                        Due

                        <?=htmlspecialchars(
                            $o['scheduled_date']
                        )?>

                    </small>


                </div>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>


</div>



<!-- =====================================================
     MACHINE HEALTH & RELIABILITY
     ===================================================== -->

<div
    class="card"
    style="margin-top:16px"
>


    <div class="topbar">


        <h3>
            Machine Health & Reliability
        </h3>


        <span class="muted">

            Health score is calculated from
            availability and failure frequency.

        </span>


    </div>



    <table>


        <thead>

            <tr>

                <th>
                    Machine
                </th>

                <th>
                    Status
                </th>

                <th>
                    Failures
                </th>

                <th>
                    Downtime
                </th>

                <th>
                    MTBF
                </th>

                <th>
                    Health
                </th>

            </tr>

        </thead>



        <tbody>


        <?php foreach($machines as $m): ?>


            <?php

            $health = health_score($m);


            $mtbf =
                ($m['failures'] > 0)

                ?

                max(
                    0,
                    (
                        $m['planned_hours']
                        -
                        $m['downtime']
                    )
                    /
                    $m['failures']
                )

                :

                $m['planned_hours'];


            $cls =
                $health >= 85

                ?

                'success'

                :

                (
                    $health >= 65

                    ?

                    'warning'

                    :

                    'danger'
                );

            ?>


            <tr>


                <td>


                    <strong>

                        <?=htmlspecialchars(
                            $m['machine_code']
                        )?>

                    </strong>


                    <br>


                    <span class="muted">

                        <?=htmlspecialchars(
                            $m['name']
                        )?>

                    </span>


                </td>



                <td>


                    <span
                        class="badge
                        <?=in_array(
                            $m['status'],
                            ['running']
                        )
                        ?
                        'success'
                        :
                        (
                            $m['status'] === 'breakdown'
                            ?
                            'danger'
                            :
                            'warning'
                        )?>"
                    >


                        <?=htmlspecialchars(
                            ucfirst(
                                $m['status']
                            )
                        )?>


                    </span>


                </td>



                <td>
                    <?=$m['failures']?>
                </td>



                <td>

                    <?=number_format(
                        $m['downtime'],
                        1
                    )?>

                    h

                </td>



                <td>

                    <?=number_format(
                        $mtbf,
                        1
                    )?>

                    h

                </td>



                <td>


                    <span
                        class="badge <?=$cls?>"
                    >

                        <?=$health?>/100

                    </span>



                    <div
                        class="progress"
                        style="margin-top:6px"
                    >

                        <span
                            style="width:<?=$health?>%"
                        ></span>

                    </div>


                </td>


            </tr>


        <?php endforeach; ?>


        </tbody>


    </table>


</div>



<!-- =====================================================
     REPEAT BREAKDOWN ALERTS
     ===================================================== -->

<div
    class="card"
    style="margin-top:16px"
>


    <h3>
        🚨 Repeat Breakdown Alerts
    </h3>



    <?php if(!$repeat_breakdowns): ?>


        <div class="alert success">

            No repeat breakdowns detected
            in the last 30 days.

        </div>


    <?php else: ?>


        <?php foreach($repeat_breakdowns as $r): ?>


            <div
                class="alert danger"
                style="margin-bottom:10px"
            >


                <strong>

                    <?=htmlspecialchars(
                        $r['machine_code']
                    )?>

                </strong>


                —


                <?=htmlspecialchars(
                    $r['name']
                )?>


                <br>


                <small>


                    <?= (int)$r['breakdown_count'] ?>

                    breakdowns in the last 30 days


                    ·


                    <?=number_format(
                        $r['total_downtime'],
                        1
                    )?>

                    hours downtime


                </small>


            </div>


        <?php endforeach; ?>


    <?php endif; ?>


</div>


</main>


</div>



<!-- =====================================================
     DYNAMIC DOWNTIME CHART
     ===================================================== -->

<script>


const labels = [
    'Week 1',
    'Week 2',
    'Week 3',
    'Week 4'
];


const downtimeData =
    <?=json_encode(
        $weekly_downtime
    )?>;


new Chart(
    document.getElementById(
        'downtimeChart'
    ),
    {

        type: 'bar',


        data: {

            labels: labels,


            datasets: [

                {

                    label:
                        'Downtime (hours)',


                    data:
                        downtimeData

                }

            ]

        },


        options: {

            responsive: true,


            plugins: {

                legend: {

                    display: false

                }

            },


            scales: {

                y: {

                    beginAtZero: true

                }

            }

        }

    }
);


</script>


</body>

</html>