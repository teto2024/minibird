<?php
require_once __DIR__ . '/config.php';
require_login();
$pdo = db();

$uid = $_SESSION['uid'];
$st = $pdo->prepare("SELECT focus_tier FROM users WHERE id=?");
$st->execute([$uid]);
$tier = $st->fetchColumn();
echo json_encode(['ok'=>true, 'tier'=>$tier]);
