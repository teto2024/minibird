<?php
require_once __DIR__ . '/config.php';
$me = user();
if (!$me || !in_array($me['role'], ['admin', 'mod'])) { 
    header('Location: ./'); 
    exit; 
}

$pdo = db();
$msg = null;

// POST処理（審査）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $submission_id = (int)($_POST['submission_id'] ?? 0);
    $action = $_POST['action'];
    
    if ($submission_id > 0) {
        $sub = $pdo->prepare("SELECT * FROM user_designed_frames WHERE id = ?");
        $sub->execute([$submission_id]);
        $submission = $sub->fetch();
        
        if ($submission) {
            if ($action === 'approve') {
                // フレームをframesテーブルに追加
                $price_coins = (int)($_POST['final_price_coins'] ?? $submission['proposed_price_coins']);
                $price_crystals = (int)($_POST['final_price_crystals'] ?? $submission['proposed_price_crystals']);
                $price_diamonds = (int)($_POST['final_price_diamonds'] ?? $submission['proposed_price_diamonds']);
                $is_limited = isset($_POST['is_limited']) ? 1 : 0;
                $sale_start = !empty($_POST['sale_start_date']) ? $_POST['sale_start_date'] : null;
                $sale_end = !empty($_POST['sale_end_date']) ? $_POST['sale_end_date'] : null;
                
                $insert = $pdo->prepare("
                    INSERT INTO frames 
                    (name, css_token, price_coins, price_crystals, price_diamonds, 
                     preview_css, is_user_designed, designed_by_user_id, 
                     is_limited, sale_start_date, sale_end_date)
                    VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)
                ");
                $insert->execute([
                    $submission['name'],
                    $submission['css_token'],
                    $price_coins,
                    $price_crystals,
                    $price_diamonds,
                    $submission['preview_css'],
                    $submission['user_id'],
                    $is_limited,
                    $sale_start,
                    $sale_end
                ]);
                
                $frame_id = $pdo->lastInsertId();
                
                // 提出情報を更新
                $update = $pdo->prepare("
                    UPDATE user_designed_frames 
                    SET status = 'approved', 
                        approved_frame_id = ?,
                        reviewed_at = NOW(),
                        reviewed_by = ?,
                        admin_comment = ?
                    WHERE id = ?
                ");
                $update->execute([
                    $frame_id,
                    $me['id'],
                    $_POST['admin_comment'] ?? null,
                    $submission_id
                ]);
                
                $msg = ['success' => 'フレームを承認し、ショップに追加しました'];
                
            } elseif ($action === 'reject') {
                $update = $pdo->prepare("
                    UPDATE user_designed_frames 
                    SET status = 'rejected',
                        reviewed_at = NOW(),
                        reviewed_by = ?,
                        admin_comment = ?
                    WHERE id = ?
                ");
                $update->execute([
                    $me['id'],
                    $_POST['admin_comment'] ?? '',
                    $submission_id
                ]);
                
                $msg = ['success' => 'フレームを却下しました'];
            }
        }
    }
}

// 審査待ちのフレームを取得
$pending = $pdo->query("
    SELECT udf.*, u.handle as submitter_handle, u.display_name as submitter_name
    FROM user_designed_frames udf
    JOIN users u ON udf.user_id = u.id
    WHERE udf.status = 'pending'
    ORDER BY udf.created_at ASC
")->fetchAll();

// 審査済みのフレームを取得
$reviewed = $pdo->query("
    SELECT udf.*, u.handle as submitter_handle, u.display_name as submitter_name,
           r.handle as reviewer_handle
    FROM user_designed_frames udf
    JOIN users u ON udf.user_id = u.id
    LEFT JOIN users r ON udf.reviewed_by = r.id
    WHERE udf.status IN ('approved', 'rejected')
    ORDER BY udf.reviewed_at DESC
    LIMIT 50
")->fetchAll();
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>フレーム審査 - MiniBird Admin</title>
<link rel="stylesheet" href="assets/style.css">
<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
}
.container {
    max-width: 1200px;
    margin: 0 auto;
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
h1, h2 {
    color: #2d3748;
}
.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: bold;
}
.alert-success {
    background: #c6f6d5;
    color: #2f855a;
}
.btn-back {
    display: inline-block;
    padding: 10px 20px;
    background: #e2e8f0;
    color: #2d3748;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    margin-bottom: 20px;
}
.submission-card {
    background: #f7fafc;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
    border-left: 4px solid #667eea;
}
.submission-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}
.submission-info {
    flex: 1;
}
.review-form {
    margin-top: 20px;
    padding: 20px;
    background: white;
    border-radius: 8px;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #4a5568;
}
.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="datetime-local"],
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    font-size: 14px;
}
.price-inputs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-approve {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
}
.btn-reject {
    background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
    color: white;
    margin-left: 10px;
}
.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.status-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}
.status-approved {
    background: #c6f6d5;
    color: #2f855a;
}
.status-rejected {
    background: #fed7d7;
    color: #c53030;
}
.section {
    margin-bottom: 40px;
}
.toggle-form {
    background: #e2e8f0;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    color: #2d3748;
}
</style>
</head>
<body>
<div class="container">
    <a href="admin.php" class="btn-back">← 管理画面に戻る</a>
    
    <h1>🎨 フレームデザイン審査</h1>
    
    <?php if($msg): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($msg['success']) ?>
        </div>
    <?php endif; ?>
    
    <div class="section">
        <h2>審査待ち (<?= count($pending) ?>件)</h2>
        
        <?php if(empty($pending)): ?>
            <p style="color: #718096;">現在、審査待ちのフレームはありません。</p>
        <?php else: ?>
            <?php foreach($pending as $sub): ?>
            <div class="submission-card">
                <div class="submission-header">
                    <div class="submission-info">
                        <h3 style="margin: 0 0 10px 0;"><?= htmlspecialchars($sub['name']) ?></h3>
                        <p style="margin: 5px 0;">
                            <strong>提出者:</strong> @<?= htmlspecialchars($sub['submitter_handle']) ?>
                            <?= $sub['submitter_name'] ? ' (' . htmlspecialchars($sub['submitter_name']) . ')' : '' ?>
                        </p>
                        <p style="margin: 5px 0;"><strong>提出日:</strong> <?= htmlspecialchars($sub['created_at']) ?></p>
                    </div>
                </div>
                
                <div style="background: white; padding: 15px; border-radius: 8px; margin: 15px 0;">
                    <p><strong>CSSトークン:</strong> <code><?= htmlspecialchars($sub['css_token']) ?></code></p>
                    <?php if($sub['description']): ?>
                    <p><strong>説明:</strong><br><?= nl2br(htmlspecialchars($sub['description'])) ?></p>
                    <?php endif; ?>
                    <?php if($sub['preview_css']): ?>
                    <p><strong>プレビューCSS:</strong></p>
                    <pre style="background: #f7fafc; padding: 10px; border-radius: 6px; overflow-x: auto;"><?= htmlspecialchars($sub['preview_css']) ?></pre>
                    <?php endif; ?>
                    <p><strong>提案価格:</strong> 
                        🪙<?= number_format($sub['proposed_price_coins']) ?> 
                        💎<?= number_format($sub['proposed_price_crystals']) ?> 
                        💠<?= number_format($sub['proposed_price_diamonds']) ?>
                    </p>
                </div>
                
                <button class="toggle-form" onclick="document.getElementById('review-<?= $sub['id'] ?>').style.display = document.getElementById('review-<?= $sub['id'] ?>').style.display === 'none' ? 'block' : 'none'">
                    審査フォームを表示
                </button>
                
                <div id="review-<?= $sub['id'] ?>" class="review-form" style="display: none;">
                    <form method="post">
                        <input type="hidden" name="submission_id" value="<?= $sub['id'] ?>">
                        
                        <div class="form-group">
                            <label>最終価格</label>
                            <div class="price-inputs">
                                <div>
                                    <label style="font-weight: normal; font-size: 13px;">🪙 コイン</label>
                                    <input type="number" name="final_price_coins" value="<?= $sub['proposed_price_coins'] ?>" min="0">
                                </div>
                                <div>
                                    <label style="font-weight: normal; font-size: 13px;">💎 クリスタル</label>
                                    <input type="number" name="final_price_crystals" value="<?= $sub['proposed_price_crystals'] ?>" min="0">
                                </div>
                                <div>
                                    <label style="font-weight: normal; font-size: 13px;">💠 ダイヤ</label>
                                    <input type="number" name="final_price_diamonds" value="<?= $sub['proposed_price_diamonds'] ?>" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_limited" value="1">
                                期間限定フレームにする
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>販売開始日時</label>
                            <input type="datetime-local" name="sale_start_date">
                        </div>
                        
                        <div class="form-group">
                            <label>販売終了日時</label>
                            <input type="datetime-local" name="sale_end_date">
                        </div>
                        
                        <div class="form-group">
                            <label>管理者コメント（任意）</label>
                            <textarea name="admin_comment" rows="3" placeholder="ユーザーへのメッセージ"></textarea>
                        </div>
                        
                        <div>
                            <button type="submit" name="action" value="approve" class="btn btn-approve">✅ 承認してショップに追加</button>
                            <button type="submit" name="action" value="reject" class="btn btn-reject" 
                                    onclick="return confirm('このフレームを却下しますか？')">❌ 却下</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>審査済み（最近50件）</h2>
        <?php if(empty($reviewed)): ?>
            <p style="color: #718096;">審査済みのフレームはまだありません。</p>
        <?php else: ?>
            <?php foreach($reviewed as $sub): ?>
            <div class="submission-card" style="opacity: 0.8;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h3 style="margin: 0;">
                            <?= htmlspecialchars($sub['name']) ?>
                            <span class="status-badge status-<?= $sub['status'] ?>">
                                <?= $sub['status'] === 'approved' ? '承認済み' : '却下' ?>
                            </span>
                        </h3>
                        <p style="margin: 5px 0; font-size: 14px; color: #718096;">
                            提出者: @<?= htmlspecialchars($sub['submitter_handle']) ?> | 
                            審査者: @<?= htmlspecialchars($sub['reviewer_handle'] ?? 'unknown') ?> | 
                            審査日: <?= htmlspecialchars($sub['reviewed_at']) ?>
                        </p>
                    </div>
                </div>
                <?php if($sub['admin_comment']): ?>
                <div style="margin-top: 10px; padding: 10px; background: white; border-radius: 6px; font-size: 14px;">
                    <strong>コメント:</strong> <?= nl2br(htmlspecialchars($sub['admin_comment'])) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
