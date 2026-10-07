<?php
require_once '../config/database.php'; require_once '../config/auth.php'; require_login();
$machines=$pdo->query("SELECT * FROM machines ORDER BY id DESC")->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Machines</title><link rel="stylesheet" href="../assets/style.css"></head>
<body><div class="app"><aside class="sidebar"><div class="logo">⚙ Smart Maintenance</div><nav class="nav"><a href="../dashboard.php">Dashboard</a><a class="active" href="index.php">Machines</a><a href="../maintenance/index.php">Maintenance</a><a href="../workorders/index.php">Work Orders</a><a href="../inventory/index.php">Spare Parts</a><a href="../reports/index.php">Reports</a><a href="../auth/logout.php">Logout</a></nav></aside>
<main class="main"><div class="topbar"><div><h1>Machines</h1><div class="muted">Asset register</div></div><?php if(in_array($_SESSION['role'],['admin','supervisor'])): ?><a class="btn primary" href="create.php">+ Add Machine</a><?php endif;?></div>
<div class="card"><table><thead><tr><th>Code</th><th>Name</th><th>Location</th><th>Status</th><th>Operating Hours</th><th>QR</th></tr></thead><tbody>
<?php foreach($machines as $m): ?><tr><td><strong><?=htmlspecialchars($m['machine_code'])?></strong></td><td><?=htmlspecialchars($m['name'])?></td><td><?=htmlspecialchars($m['location'])?></td><td><span class="badge info"><?=htmlspecialchars(ucfirst($m['status']))?></span></td><td><?=number_format($m['operating_hours'],1)?></td><td><a class="btn secondary" href="qr.php?id=<?=$m['id']?>">QR</a></td></tr><?php endforeach;?>
</tbody></table></div></main></div></body></html>
