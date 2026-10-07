<?php
require_once '../config/database.php'; require_once '../config/auth.php'; require_role(['admin','supervisor']);
if($_SERVER['REQUEST_METHOD']==='POST'){
 $code=trim($_POST['machine_code']);$name=trim($_POST['name']);$location=trim($_POST['location']);
 $category=trim($_POST['category']);$date=$_POST['installation_date'] ?: null;
 if($code && $name && $location){
   $stmt=$pdo->prepare("INSERT INTO machines(machine_code,name,location,category,installation_date) VALUES(?,?,?,?,?)");
   $stmt->execute([$code,$name,$location,$category,$date]); header('Location:index.php');exit;
 }
 $error='Please fill all required fields.';
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Add Machine</title><link rel="stylesheet" href="../assets/style.css"></head>
<body><main class="main" style="margin:0;width:100%"><div class="card" style="max-width:720px;margin:40px auto"><h1>Add Machine</h1><?php if(isset($error)):?><div class="alert danger"><?=$error?></div><?php endif;?>
<form method="post" data-validate><div class="form-grid">
<label>Machine Code*<input name="machine_code" required></label><label>Machine Name*<input name="name" required></label>
<label>Location*<input name="location" required></label><label>Category<input name="category" value="Production"></label>
<label>Installation Date<input type="date" name="installation_date"></label></div><div class="actions"><button class="btn primary">Save</button><a class="btn secondary" href="index.php">Cancel</a></div></form></div></main><script src="../assets/app.js"></script></body></html>
