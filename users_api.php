<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'config.php';

header('Content-Type: application/json');

try {
    $me = user();
    if(!$me) throw new Exception("ログインしていません");

    // POST データ取得
    $bio = $_POST['bio'] ?? null;
    $display_name = $_POST['display_name'] ?? null;

    // アイコンアップロード
    $icon_url = null;
    if(!empty($_FILES['icon']['tmp_name'])){
        $targetDir = 'uploads/icons/';
        if(!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        $ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
        $filename = $me['id'].'.'.$ext;
        $dst = $targetDir.$filename;
        if(move_uploaded_file($_FILES['icon']['tmp_name'], $dst)){
            $icon_url = $dst;
            // DB 更新例
            $pdo = db();
            $st = $pdo->prepare("UPDATE users SET icon=? WHERE id=?");
            $st->execute([$icon_url, $me['id']]);
        }
    }

    // DB 更新
    $pdo = db();
    $st = $pdo->prepare("UPDATE users SET bio=?, display_name=? WHERE id=?");
    $st->execute([$bio, $display_name, $me['id']]);

    echo json_encode([
        'ok'=>true,
        'bio'=>$bio,
        'display_name'=>$display_name,
        'icon'=>$icon_url
    ]);
} catch(Exception $e){
    echo json_encode([
        'ok'=>false,
        'error'=>$e->getMessage()
    ]);
}
