<?php
// ===============================================
// extended_shop.php
// 拡張ショップ（絵文字パッケージ、称号パッケージ）
// ===============================================

require_once __DIR__ . '/config.php';
$me = user();
if (!$me) {
    header('Location: ./');
    exit;
}

$pdo = db();

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $msg = '';
    
    try {
        if ($action === 'buy_emoji') {
            $package_id = intval($_POST['package_id'] ?? 0);
            
            $stmt = $pdo->prepare("SELECT * FROM emoji_packages WHERE id=?");
            $stmt->execute([$package_id]);
            $package = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$package) {
                throw new Exception('パッケージが見つかりません');
            }
            
            // 既に所有しているかチェック
            $stmt = $pdo->prepare("SELECT 1 FROM user_emoji_packages WHERE user_id=? AND package_id=?");
            $stmt->execute([$me['id'], $package_id]);
            if ($stmt->fetch()) {
                throw new Exception('既に所有しています');
            }
            
            // 残高チェック
            if ($me['coins'] < $package['price_coins'] || 
                $me['crystals'] < $package['price_crystals'] || 
                $me['diamonds'] < $package['price_diamonds']) {
                throw new Exception('残高不足');
            }
            
            // 購入処理
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET coins = coins - ?, crystals = crystals - ?, diamonds = diamonds - ?
                WHERE id = ?
            ");
            $stmt->execute([
                $package['price_coins'], 
                $package['price_crystals'], 
                $package['price_diamonds'], 
                $me['id']
            ]);
            
            $stmt = $pdo->prepare("
                INSERT INTO user_emoji_packages (user_id, package_id, purchased_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$me['id'], $package_id]);
            
            $pdo->commit();
            $msg = '絵文字パッケージを購入しました！';
            
        } elseif ($action === 'buy_title') {
            $title_id = intval($_POST['title_id'] ?? 0);
            
            $stmt = $pdo->prepare("SELECT * FROM title_packages WHERE id=?");
            $stmt->execute([$title_id]);
            $title = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$title) {
                throw new Exception('称号が見つかりません');
            }
            
            // 既に所有しているかチェック
            $stmt = $pdo->prepare("SELECT 1 FROM user_titles WHERE user_id=? AND title_id=?");
            $stmt->execute([$me['id'], $title_id]);
            if ($stmt->fetch()) {
                throw new Exception('既に所有しています');
            }
            
            // 残高チェック
            if ($me['coins'] < $title['price_coins'] || 
                $me['crystals'] < $title['price_crystals'] || 
                $me['diamonds'] < $title['price_diamonds']) {
                throw new Exception('残高不足');
            }
            
            // 購入処理
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET coins = coins - ?, crystals = crystals - ?, diamonds = diamonds - ?
                WHERE id = ?
            ");
            $stmt->execute([
                $title['price_coins'], 
                $title['price_crystals'], 
                $title['price_diamonds'], 
                $me['id']
            ]);
            
            $stmt = $pdo->prepare("
                INSERT INTO user_titles (user_id, title_id, is_equipped, purchased_at)
                VALUES (?, ?, FALSE, NOW())
            ");
            $stmt->execute([$me['id'], $title_id]);
            
            $pdo->commit();
            $msg = '称号を購入しました！';
            
        } elseif ($action === 'equip_title') {
            $title_id = intval($_POST['title_id'] ?? 0);
            
            // 所有チェック
            $stmt = $pdo->prepare("SELECT 1 FROM user_titles WHERE user_id=? AND title_id=?");
            $stmt->execute([$me['id'], $title_id]);
            if (!$stmt->fetch()) {
                throw new Exception('所有していない称号です');
            }
            
            // 全ての称号を非装備にしてから装備
            $stmt = $pdo->prepare("UPDATE user_titles SET is_equipped = FALSE WHERE user_id = ?");
            $stmt->execute([$me['id']]);
            
            $stmt = $pdo->prepare("UPDATE user_titles SET is_equipped = TRUE WHERE user_id = ? AND title_id = ?");
            $stmt->execute([$me['id'], $title_id]);
            
            $msg = '称号を装備しました！';
            
        } elseif ($action === 'unequip_title') {
            $stmt = $pdo->prepare("UPDATE user_titles SET is_equipped = FALSE WHERE user_id = ?");
            $stmt->execute([$me['id']]);
            
            $msg = '称号を外しました';
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $msg = 'エラー: ' . $e->getMessage();
    }
    
    header("Location: extended_shop.php?msg=" . urlencode($msg));
    exit;
}

// 絵文字パッケージ取得
$stmt = $pdo->prepare("
    SELECT ep.*, 
           (SELECT 1 FROM user_emoji_packages WHERE user_id=? AND package_id=ep.id) as owned
    FROM emoji_packages ep
    ORDER BY ep.id
");
$stmt->execute([$me['id']]);
$emoji_packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 称号パッケージ取得
$stmt = $pdo->prepare("
    SELECT tp.*, 
           ut.is_equipped,
           (ut.id IS NOT NULL) as owned
    FROM title_packages tp
    LEFT JOIN user_titles ut ON ut.title_id = tp.id AND ut.user_id = ?
    ORDER BY tp.id
");
$stmt->execute([$me['id']]);
$title_packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msg = $_GET['msg'] ?? '';
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>拡張ショップ - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
<style>
.shop-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}
.shop-header {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    text-align: center;
}
.shop-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
}
.currency-display {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin-top: 20px;
    font-size: 18px;
}
.currency-item {
    display: flex;
    align-items: center;
    gap: 8px;
}
.shop-section {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.shop-section h2 {
    margin: 0 0 20px 0;
    color: #2d3748;
    font-size: 24px;
    border-bottom: 3px solid #f093fb;
    padding-bottom: 10px;
}
.package-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}
.package-card {
    background: #f7fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    transition: all 0.3s;
    position: relative;
}
.package-card:hover {
    border-color: #f093fb;
    box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
    transform: translateY(-2px);
}
.package-card.owned {
    background: #e6fffa;
    border-color: #38b2ac;
}
.package-card.equipped {
    background: #fef5e7;
    border-color: #f39c12;
}
.package-preview {
    font-size: 48px;
    text-align: center;
    margin-bottom: 15px;
    min-height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.package-name {
    font-size: 18px;
    font-weight: bold;
    color: #2d3748;
    margin-bottom: 8px;
}
.package-description {
    color: #718096;
    font-size: 14px;
    margin-bottom: 12px;
    min-height: 40px;
}
.package-price {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}
.price-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 14px;
    font-weight: bold;
    color: #2d3748;
}
.package-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.3s;
}
.btn-buy {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    flex: 1;
}
.btn-buy:hover {
    opacity: 0.9;
}
.btn-equip {
    background: #48bb78;
    color: white;
    flex: 1;
}
.btn-unequip {
    background: #718096;
    color: white;
    flex: 1;
}
.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}
.badge-owned {
    background: #38b2ac;
    color: white;
}
.badge-equipped {
    background: #f39c12;
    color: white;
}
.title-preview {
    font-size: 24px;
    font-weight: bold;
    text-align: center;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    min-height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.title-beginner { color: #4299e1; }
.title-veteran { color: #ed8936; text-shadow: 0 0 5px rgba(237, 137, 54, 0.3); }
.title-master { color: #9f7aea; text-shadow: 0 0 10px rgba(159, 122, 234, 0.5); }
.title-legend { 
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: none;
}
.title-leader { color: #48bb78; font-weight: bold; }
.title-trendsetter { 
    color: #f56565; 
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}
.message {
    background: #48bb78;
    color: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
}
</style>
</head>
<body>
<div class="shop-container">
    <?php if ($msg): ?>
    <div class="message"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="shop-header">
        <h1>🛍️ 拡張ショップ</h1>
        <p>絵文字パッケージと称号パッケージが購入できます</p>
        <div class="currency-display">
            <div class="currency-item">
                <span>🪙</span>
                <span><?= number_format($me['coins'] ?? 0) ?> コイン</span>
            </div>
            <div class="currency-item">
                <span>💎</span>
                <span><?= number_format($me['crystals'] ?? 0) ?> クリスタル</span>
            </div>
            <div class="currency-item">
                <span>💠</span>
                <span><?= number_format($me['diamonds'] ?? 0) ?> ダイヤモンド</span>
            </div>
        </div>
    </div>

    <!-- 絵文字パッケージ -->
    <div class="shop-section">
        <h2>😀 絵文字パッケージ</h2>
        <p style="color: #718096; margin-bottom: 20px;">カスタム絵文字やGIFを使って投稿をもっと楽しく！</p>
        <div class="package-grid">
            <?php foreach ($emoji_packages as $package): ?>
            <div class="package-card <?= $package['owned'] ? 'owned' : '' ?>">
                <?php if ($package['owned']): ?>
                <span class="badge badge-owned">所有済み</span>
                <?php endif; ?>
                
                <div class="package-preview"><?= htmlspecialchars($package['preview_emoji']) ?></div>
                <div class="package-name"><?= htmlspecialchars($package['name']) ?></div>
                <div class="package-description"><?= htmlspecialchars($package['description']) ?></div>
                
                <div class="package-price">
                    <?php if ($package['price_coins'] > 0): ?>
                    <div class="price-item">🪙 <?= number_format($package['price_coins']) ?></div>
                    <?php endif; ?>
                    <?php if ($package['price_crystals'] > 0): ?>
                    <div class="price-item">💎 <?= number_format($package['price_crystals']) ?></div>
                    <?php endif; ?>
                    <?php if ($package['price_diamonds'] > 0): ?>
                    <div class="price-item">💠 <?= number_format($package['price_diamonds']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="package-actions">
                    <?php if (!$package['owned']): ?>
                    <form method="POST" style="flex: 1;">
                        <input type="hidden" name="action" value="buy_emoji">
                        <input type="hidden" name="package_id" value="<?= $package['id'] ?>">
                        <button type="submit" class="btn btn-buy" style="width: 100%;">購入する</button>
                    </form>
                    <?php else: ?>
                    <button class="btn" disabled style="width: 100%; background: #cbd5e0; color: #2d3748;">所有済み</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 称号パッケージ -->
    <div class="shop-section">
        <h2>🏆 称号パッケージ</h2>
        <p style="color: #718096; margin-bottom: 20px;">名前の横に表示される特別な称号を手に入れよう！</p>
        <div class="package-grid">
            <?php foreach ($title_packages as $title): ?>
            <div class="package-card <?= $title['owned'] ? 'owned' : '' ?> <?= $title['is_equipped'] ? 'equipped' : '' ?>">
                <?php if ($title['is_equipped']): ?>
                <span class="badge badge-equipped">装備中</span>
                <?php elseif ($title['owned']): ?>
                <span class="badge badge-owned">所有済み</span>
                <?php endif; ?>
                
                <div class="title-preview">
                    <span class="<?= htmlspecialchars($title['title_css']) ?>">
                        <?= htmlspecialchars($title['title_text']) ?>
                    </span>
                </div>
                <div class="package-name"><?= htmlspecialchars($title['name']) ?></div>
                <div class="package-description"><?= htmlspecialchars($title['description']) ?></div>
                
                <div class="package-price">
                    <?php if ($title['price_coins'] > 0): ?>
                    <div class="price-item">🪙 <?= number_format($title['price_coins']) ?></div>
                    <?php endif; ?>
                    <?php if ($title['price_crystals'] > 0): ?>
                    <div class="price-item">💎 <?= number_format($title['price_crystals']) ?></div>
                    <?php endif; ?>
                    <?php if ($title['price_diamonds'] > 0): ?>
                    <div class="price-item">💠 <?= number_format($title['price_diamonds']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="package-actions">
                    <?php if (!$title['owned']): ?>
                    <form method="POST" style="flex: 1;">
                        <input type="hidden" name="action" value="buy_title">
                        <input type="hidden" name="title_id" value="<?= $title['id'] ?>">
                        <button type="submit" class="btn btn-buy" style="width: 100%;">購入する</button>
                    </form>
                    <?php elseif ($title['is_equipped']): ?>
                    <form method="POST" style="flex: 1;">
                        <input type="hidden" name="action" value="unequip_title">
                        <button type="submit" class="btn btn-unequip" style="width: 100%;">外す</button>
                    </form>
                    <?php else: ?>
                    <form method="POST" style="flex: 1;">
                        <input type="hidden" name="action" value="equip_title">
                        <input type="hidden" name="title_id" value="<?= $title['id'] ?>">
                        <button type="submit" class="btn btn-equip" style="width: 100%;">装備する</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <button onclick="location.href='shop.php'" class="btn" style="padding: 12px 30px; background: #667eea; color: white; margin-right: 10px;">
            フレームショップへ
        </button>
        <button onclick="location.href='index.php'" class="btn" style="padding: 12px 30px; background: #cbd5e0; color: #2d3748;">
            フィードに戻る
        </button>
    </div>
</div>
</body>
</html>
