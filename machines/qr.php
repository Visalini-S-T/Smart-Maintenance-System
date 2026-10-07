<?php
require_once '../config/database.php'; require_once '../config/auth.php'; require_login();
$id=(int)($_GET['id']??0);$stmt=$pdo->prepare("SELECT * FROM machines WHERE id=?");$stmt->execute([$id]);$m=$stmt->fetch();if(!$m)exit('Machine not found');
$payload=urlencode("Machine: {$m['machine_code']} | {$m['name']} | Location: {$m['location']}");
?>
<!doctype html><html><head><meta charset="utf-8"><title>QR <?=$m['machine_code']?></title><link rel="stylesheet" href="../assets/style.css"></head><body>
<main class="main" style="margin:0;width:100%"><div class="card" style="max-width:500px;margin:50px auto;text-align:center"><h1><?=htmlspecialchars($m['machine_code'])?></h1><p><?=htmlspecialchars($m['name'])?></p>
<div id="qrcode" style="display:flex;justify-content:center;margin:25px"></div><p class="muted">Scan for machine identification.</p><button class="btn primary" onclick="window.print()">Print QR</button></div></main>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script><script>new QRCode(document.getElementById('qrcode'),{text:decodeURIComponent("<?=$payload?>"),width:220,height:220});</script></body></html>
