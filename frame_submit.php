<?php
require_once __DIR__ . '/config.php';
$me = user();
if (!$me){ header('Location: ./'); exit; }

$pdo = db();
$msg = null;

// ユーザーが提出したフレームの一覧を取得
$submitted_frames = $pdo->prepare("
    SELECT * FROM user_designed_frames 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$submitted_frames->execute([$me['id']]);
$my_submissions = $submitted_frames->fetchAll();

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $css_token = trim($_POST['css_token'] ?? '');
    $preview_css = trim($_POST['preview_css'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price_coins = max(0, (int)($_POST['price_coins'] ?? 0));
    $price_crystals = max(0, (int)($_POST['price_crystals'] ?? 0));
    $price_diamonds = max(0, (int)($_POST['price_diamonds'] ?? 0));
    
    // サーバー側でのバリデーション
    if (empty($name) || empty($css_token)) {
        $msg = ['error' => 'フレーム名とCSSトークンは必須です'];
    } elseif (!preg_match('/^frame-[a-z0-9-]+$/', $css_token)) {
        $msg = ['error' => 'CSSトークンは「frame-」で始まり、英小文字・数字・ハイフンのみ使用できます'];
    } else {
        // css_tokenの重複チェック
        $check = $pdo->prepare("SELECT 1 FROM frames WHERE css_token = ?");
        $check->execute([$css_token]);
        if ($check->fetch()) {
            $msg = ['error' => 'このCSSトークンは既に使用されています'];
        } else {
            $check2 = $pdo->prepare("SELECT 1 FROM user_designed_frames WHERE css_token = ? AND status = 'pending'");
            $check2->execute([$css_token]);
            if ($check2->fetch()) {
                $msg = ['error' => 'このCSSトークンは既に提案されています'];
            } else {
                // フレームデザインを提出
                $insert = $pdo->prepare("
                    INSERT INTO user_designed_frames 
                    (user_id, name, css_token, preview_css, description, 
                     proposed_price_coins, proposed_price_crystals, proposed_price_diamonds, 
                     status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $insert->execute([
                    $me['id'], $name, $css_token, $preview_css, $description,
                    $price_coins, $price_crystals, $price_diamonds
                ]);
                
                $msg = ['success' => 'フレームデザインを提出しました。管理者の審査をお待ちください。'];
                
                // リロードして最新の一覧を取得
                $submitted_frames->execute([$me['id']]);
                $my_submissions = $submitted_frames->fetchAll();
            }
        }
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>フレームデザイン提出 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
}
.container {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
h1 {
    color: #2d3748;
    margin-bottom: 10px;
}
.description {
    color: #718096;
    margin-bottom: 30px;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #4a5568;
}
.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e0;
    border-radius: 8px;
    font-size: 16px;
}
.form-group textarea {
    min-height: 100px;
    resize: vertical;
}
.form-group small {
    color: #718096;
    font-size: 13px;
    display: block;
    margin-top: 5px;
}
.price-inputs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}
.btn {
    padding: 14px 28px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-submit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}
.btn-back {
    background: #e2e8f0;
    color: #2d3748;
    text-decoration: none;
    display: inline-block;
    margin-bottom: 20px;
}
.btn-back:hover {
    background: #cbd5e0;
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
.alert-error {
    background: #fed7d7;
    color: #c53030;
}
.submissions-section {
    margin-top: 40px;
    border-top: 2px solid #e2e8f0;
    padding-top: 30px;
}
.submission-card {
    background: #f7fafc;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    border-left: 4px solid #667eea;
}
.submission-card h3 {
    margin: 0 0 10px 0;
    color: #2d3748;
}
.status-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    margin-left: 10px;
}
.status-pending {
    background: #fef3c7;
    color: #92400e;
}
.status-approved {
    background: #c6f6d5;
    color: #2f855a;
}
.status-rejected {
    background: #fed7d7;
    color: #c53030;
}
</style>
</head>
<body>
<div class="container">
    <a href="shop.php" class="btn btn-back">← フレームショップに戻る</a>
    
    <h1>🎨 フレームデザイン提出</h1>
    <p class="description">
        あなたのオリジナルフレームデザインを提出できます。<br>
        管理者が審査し、承認されるとフレームショップに掲載されます！
    </p>
    
    <?php if($msg): ?>
        <div class="alert <?= isset($msg['error']) ? 'alert-error' : 'alert-success' ?>">
            <?= htmlspecialchars($msg['error'] ?? $msg['success']) ?>
        </div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label for="name">フレーム名 *</label>
            <input type="text" id="name" name="name" required maxlength="100" placeholder="例: キラキラスター">
            <small>フレームの表示名を入力してください</small>
        </div>
        
        <div class="form-group">
            <label for="css_token">CSSトークン *</label>
            <input type="text" id="css_token" name="css_token" required maxlength="100" 
                   pattern="frame-[a-z0-9-]+" placeholder="例: frame-sparkle-star">
            <small>「frame-」で始まる英数字とハイフンのみ（例: frame-custom-design）</small>
        </div>
        
        <div class="form-group">
            <label for="preview_css">プレビューCSS（任意）</label>
            <textarea id="preview_css" name="preview_css" placeholder="例: background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); border: 2px solid gold;"></textarea>
            <small>フレームのスタイルを定義するCSS（CSSクラスの内容）</small>
        </div>
        
        <div class="form-group">
            <label for="description">説明（任意）</label>
            <textarea id="description" name="description" placeholder="フレームのコンセプトや特徴を説明してください"></textarea>
        </div>
        
        <div class="form-group">
            <label>提案価格</label>
            <div class="price-inputs">
                <div>
                    <label for="price_coins" style="font-weight: normal; font-size: 14px;">🪙 コイン</label>
                    <input type="number" id="price_coins" name="price_coins" min="0" value="1000">
                </div>
                <div>
                    <label for="price_crystals" style="font-weight: normal; font-size: 14px;">💎 クリスタル</label>
                    <input type="number" id="price_crystals" name="price_crystals" min="0" value="5">
                </div>
                <div>
                    <label for="price_diamonds" style="font-weight: normal; font-size: 14px;">💠 ダイヤ</label>
                    <input type="number" id="price_diamonds" name="price_diamonds" min="0" value="0">
                </div>
            </div>
            <small>管理者が最終的な価格を決定します</small>
        </div>
        
        <button type="submit" class="btn btn-submit">📤 提出する</button>
    </form>
    
    <?php if(!empty($my_submissions)): ?>
    <div class="submissions-section">
        <h2>提出履歴</h2>
        <?php foreach($my_submissions as $sub): ?>
        <div class="submission-card">
            <h3>
                <?= htmlspecialchars($sub['name']) ?>
                <span class="status-badge status-<?= $sub['status'] ?>">
                    <?php
                    $status_text = [
                        'pending' => '審査中',
                        'approved' => '承認済み',
                        'rejected' => '却下'
                    ];
                    echo $status_text[$sub['status']] ?? $sub['status'];
                    ?>
                </span>
            </h3>
            <p><strong>CSSトークン:</strong> <?= htmlspecialchars($sub['css_token']) ?></p>
            <?php if($sub['description']): ?>
            <p><strong>説明:</strong> <?= nl2br(htmlspecialchars($sub['description'])) ?></p>
            <?php endif; ?>
            <p><strong>提案価格:</strong> 
                🪙<?= number_format($sub['proposed_price_coins']) ?> 
                💎<?= number_format($sub['proposed_price_crystals']) ?> 
                💠<?= number_format($sub['proposed_price_diamonds']) ?>
            </p>
            <p><strong>提出日:</strong> <?= htmlspecialchars($sub['created_at']) ?></p>
            
            <?php if($sub['status'] === 'approved' && $sub['approved_frame_id']): ?>
            <p style="color: #2f855a; font-weight: bold;">
                ✅ このフレームは承認され、ショップで販売中です！
            </p>
            <?php endif; ?>
            
            <?php if($sub['status'] === 'rejected' && $sub['admin_comment']): ?>
            <div style="background: #fff5f5; padding: 10px; border-radius: 8px; margin-top: 10px;">
                <strong>管理者コメント:</strong><br>
                <?= nl2br(htmlspecialchars($sub['admin_comment'])) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
