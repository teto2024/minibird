<?php
require_once __DIR__ . '/config.php';
require_login();
$pdo = db();
$pdo->prepare("UPDATE users SET coins=coins+5, crystals=crystals+1 WHERE id=?")->execute([$_SESSION['uid']]);
$pdo->prepare("INSERT INTO reward_events(user_id,kind,amount,meta) VALUES(?,?,?,NULL)")->execute([$_SESSION['uid'],'focus_reward',6]);
echo "ok";
