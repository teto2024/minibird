<?php
require_once __DIR__ . '/config.php';

$me = user();
if (!$me || !in_array($me['role'], ['mod','admin'])) { 
    http_response_code(403); 
    echo "forbidden"; 
    exit; 
}

// 新しい統合管理ページにリダイレクト
header('Location: admin_unified.php');
exit;
?>
