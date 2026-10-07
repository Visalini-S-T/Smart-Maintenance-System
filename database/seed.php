<?php
require_once __DIR__ . '/../config/database.php';

$accounts = [
 ['System Admin','admin@example.com','Admin@123','admin'],
 ['Main Supervisor','supervisor@example.com','Supervisor@123','supervisor'],
 ['Maintenance Technician','technician@example.com','Technician@123','technician'],
];

foreach($accounts as $a){
    $hash = password_hash($a[2], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), name=VALUES(name), role=VALUES(role)");
    $stmt->execute([$a[0],$a[1],$hash,$a[3]]);
}
echo "Demo accounts seeded. You can now log in.";
