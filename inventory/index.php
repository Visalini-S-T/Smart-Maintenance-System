<?php
require_once '../config/database.php'; require_once '../config/auth.php'; require_login();
$parts=$pdo->query("SELECT * FROM spare_parts ORDER BY quantity ASC")->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Spare Parts</title><link rel="stylesheet" href="../assets/style.css"></head>
<body><div class="app"><aside class="sidebar"><div class="logo">⚙ Smart Maintenance</div><nav class="nav"><a href="../dashboard.php">Dashboard</a><a href="../machines/index.php">Machines</a><a href="../maintenance/index.php">Maintenance</a><a href="../workorders/index.php">Work Orders</a><a class="active" href="index.php">Spare Parts</a><a href="../reports/index.php">Reports</a><a href="../auth/logout.php">Logout</a></nav></aside>
<main class="main"><h1>Spare Parts Inventory</h1><p class="muted">Stock is automatically deducted when a work order is closed with a part.</p>
<div class="card"><table><thead><tr><th>Part</th><th>Code</th><th>Stock</th><th>Reorder Level</th><th>Unit Cost</th><th>Alert</th></tr></thead><tbody><?php foreach($parts as $p):?><tr><td><?=htmlspecialchars($p['part_name'])?></td><td><?=$p['part_code']?></td><td><strong><?=$p['quantity']?></strong></td><td><?=$p['reorder_level']?></td><td>₹<?=number_format($p['unit_cost'],2)?></td><td><?php if($p['quantity']<=$p['reorder_level']):?><span class="badge danger">Low stock</span><?php else:?><span class="badge success">OK</span><?php endif;?></td></tr><?php endforeach;?></tbody></table></div></main></div></body></html>
