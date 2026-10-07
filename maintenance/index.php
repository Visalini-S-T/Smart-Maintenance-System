<?php
require_once '../config/database.php'; require_once '../config/auth.php'; require_login();
$logs=$pdo->query("SELECT b.*,m.machine_code,m.name FROM breakdown_logs b JOIN machines m ON m.id=b.machine_id ORDER BY b.failure_date DESC")->fetchAll();
$machines=$pdo->query("SELECT id,machine_code,name FROM machines ORDER BY machine_code")->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'){
 $mid=(int)$_POST['machine_id'];$type=trim($_POST['failure_type']);$desc=trim($_POST['description']);$down=(float)$_POST['downtime_hours'];
 $stmt=$pdo->prepare("INSERT INTO breakdown_logs(machine_id,failure_date,failure_type,description,downtime_hours,created_by) VALUES(?,NOW(),?,?,?,?)");
 $stmt->execute([$mid,$type,$desc,$down,$_SESSION['user_id']]);
 $countStmt=$pdo->prepare("SELECT COUNT(*) FROM breakdown_logs WHERE machine_id=? AND failure_type=? AND failure_date>=DATE_SUB(NOW(),INTERVAL 30 DAY)");
 $countStmt->execute([$mid,$type]);$count=(int)$countStmt->fetchColumn();
 if($count>=2){
   $u=$pdo->query("SELECT id FROM users WHERE role IN ('admin','supervisor')")->fetchAll();
   $n=$pdo->prepare("INSERT INTO notifications(user_id,title,message,type) VALUES(?,?,?,'warning')");
   foreach($u as $row)$n->execute([$row['id'],'Repeat breakdown alert',"Repeated {$type} detected for machine ID {$mid}."]);
 }
 header('Location:index.php?added=1');exit;
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Maintenance</title><link rel="stylesheet" href="../assets/style.css"></head>
<body><div class="app"><aside class="sidebar"><div class="logo">⚙ Smart Maintenance</div><nav class="nav"><a href="../dashboard.php">Dashboard</a><a href="../machines/index.php">Machines</a><a class="active" href="index.php">Maintenance</a><a href="../workorders/index.php">Work Orders</a><a href="../inventory/index.php">Spare Parts</a><a href="../reports/index.php">Reports</a><a href="../auth/logout.php">Logout</a></nav></aside>
<main class="main"><div class="topbar"><div><h1>Breakdown & Maintenance</h1><div class="muted">Log failures and trigger repeat-breakdown alerts</div></div></div>
<?php if(isset($_GET['added'])):?><div class="alert success">Breakdown recorded. If the same failure occurred twice in 30 days, an alert was generated.</div><?php endif;?>
<div class="grid two"><div class="card"><h3>Report Breakdown</h3><form method="post" data-validate><label>Machine*<select name="machine_id" required><option value="">Select machine</option><?php foreach($machines as $m):?><option value="<?=$m['id']?>"><?=$m['machine_code']?> — <?=$m['name']?></option><?php endforeach;?></select></label>
<label>Failure Type*<input name="failure_type" required></label><label>Downtime (hours)*<input type="number" name="downtime_hours" min="0" step="0.1" required></label><label>Description<textarea name="description"></textarea></label><button class="btn primary">Record Breakdown</button></form></div>
<div class="card"><h3>Recent Breakdowns</h3><table><thead><tr><th>Machine</th><th>Failure</th><th>Downtime</th><th>Date</th></tr></thead><tbody><?php foreach($logs as $l):?><tr><td><?=$l['machine_code']?></td><td><?=htmlspecialchars($l['failure_type'])?></td><td><?=$l['downtime_hours']?> h</td><td><?=htmlspecialchars($l['failure_date'])?></td></tr><?php endforeach;?></tbody></table></div></div>
</main></div><script src="../assets/app.js"></script></body></html>
