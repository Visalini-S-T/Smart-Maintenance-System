<?php
require_once '../config/database.php';
require_once '../config/auth.php';

require_login();

$rows = $pdo->query("
    SELECT
        m.machine_code,
        m.name,
        COUNT(b.id) AS failures,
        COALESCE(SUM(b.downtime_hours), 0) AS downtime,
        m.planned_hours
    FROM machines m
    LEFT JOIN breakdown_logs b
        ON b.machine_id = m.id
    GROUP BY
        m.id,
        m.machine_code,
        m.name,
        m.planned_hours
    ORDER BY downtime DESC
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

<title>Reports</title>

<link
    rel="stylesheet"
    href="../assets/style.css"
>

<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>

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

            <a href="../workorders/index.php">
                Work Orders
            </a>

            <a href="../inventory/index.php">
                Spare Parts
            </a>

            <a
                class="active"
                href="index.php"
            >
                Reports
            </a>

            <a href="../auth/logout.php">
                Logout
            </a>

        </nav>

    </aside>


    <!-- MAIN -->

    <main class="main">

        <div class="topbar">

            <div>

                <h1>
                    Maintenance Report
                </h1>

                <div class="muted">
                    Reliability summary
                </div>

            </div>


            <div class="actions no-print">

                <button
                    class="btn secondary"
                    onclick="downloadCSV()"
                >
                    Export CSV
                </button>

                <button
                    class="btn primary"
                    onclick="downloadPDF()"
                >
                    Export PDF
                </button>

            </div>

        </div>


        <!-- REPORT TABLE -->

        <div class="card">

            <h3>
                Machine Reliability Report
            </h3>

            <table id="reportTable">

                <thead>

                    <tr>

                        <th>
                            Machine
                        </th>

                        <th>
                            Failures
                        </th>

                        <th>
                            Downtime (h)
                        </th>

                        <th>
                            MTBF (h)
                        </th>

                        <th>
                            Availability
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($rows as $r): ?>

                    <?php

                    $failures = (int)$r['failures'];

                    $downtime = (float)$r['downtime'];

                    $planned_hours = (float)$r['planned_hours'];

                    if ($failures > 0) {

                        $mtbf =
                            ($planned_hours - $downtime)
                            / $failures;

                    } else {

                        $mtbf =
                            $planned_hours;

                    }

                    if ($planned_hours > 0) {

                        $availability =
                            (
                                ($planned_hours - $downtime)
                                / $planned_hours
                            ) * 100;

                    } else {

                        $availability = 0;

                    }

                    $availability =
                        max(0, min(100, $availability));

                    ?>

                    <tr>

                        <td>

                            <?=htmlspecialchars(
                                $r['machine_code']
                            )?>

                            —

                            <?=htmlspecialchars(
                                $r['name']
                            )?>

                        </td>


                        <td>
                            <?=$failures?>
                        </td>


                        <td>
                            <?=number_format(
                                $downtime,
                                1
                            )?>
                        </td>


                        <td>
                            <?=number_format(
                                $mtbf,
                                1
                            )?>
                        </td>


                        <td>
                            <?=number_format(
                                $availability,
                                1
                            )?>%
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </main>

</div>


<script>

/*
 * EXPORT CSV
 */

function downloadCSV() {

    const rows =
        [
            ...document.querySelectorAll(
                '#reportTable tr'
            )
        ]
        .map(
            tr =>
                [
                    ...tr.children
                ]
                .map(
                    td =>
                        `"${td.innerText
                            .replaceAll('"', '""')}"`
                )
                .join(',')
        );


    const blob =
        new Blob(
            [rows.join('\n')],
            {
                type: 'text/csv;charset=utf-8;'
            }
        );


    const url =
        URL.createObjectURL(blob);


    const a =
        document.createElement('a');


    a.href = url;

    a.download =
        'maintenance-report.csv';


    document.body.appendChild(a);

    a.click();

    document.body.removeChild(a);

    URL.revokeObjectURL(url);

}


/*
 * EXPORT PDF
 */

function downloadPDF() {

    const { jsPDF } =
        window.jspdf;


    const doc =
        new jsPDF();


    doc.setFontSize(16);

    doc.text(
        'Smart Maintenance - Reliability Report',
        14,
        16
    );


    doc.setFontSize(10);

    doc.text(
        'Machine reliability and maintenance performance summary',
        14,
        22
    );


    doc.autoTable({

        html: '#reportTable',

        startY: 28,

        theme: 'grid',

        styles: {
            fontSize: 9,
            cellPadding: 3
        },

        headStyles: {
            fontStyle: 'bold'
        }

    });


    doc.save(
        'maintenance-report.pdf'
    );

}

</script>

</body>

</html>
