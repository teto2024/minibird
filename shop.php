<?php
require_once __DIR__ . '/config.php';
$me = user();
if (!$me){ header('Location: ./'); exit; }
$pdo = db();

// Seed sample frames if none
$cnt = (int)$pdo->query("SELECT COUNT(*) FROM frames")->fetchColumn();
if ($cnt===0){
  $pdo->exec("INSERT INTO frames(name,css_token,price_coins,price_crystals,price_diamonds,preview_css) VALUES
  ('クラシック','frame-classic',200,0,0,''),
  ('ネオン','frame-neon',800,2,0,''),
  ('サクラⅠ,'frame-sakura',500,1,0,''),
  ('花火','frame-fireworks',600,1,0,''),
  ('サイバーパンク','frame-cyberpunk',900,3,0,''),
  ('ネオン文字','frame-neon-text',850,2,0,''),
  ('VIP','frame-vip',2000,5,0,''),
  ('パープルⅠ','frame-purple',700,1,0,''),
  ('星空','frame-stars',50000,5,0,''),
  ('ラブリー','frame-lovely',30000,3,0,''),
  ('炎','frame-flame',40000,4,0,''),
  ('クリスマス','frame-christmas',30000,50,0,''),
  ('冬','frame-winter',5000,50,0,''),
  ('サクラⅡ','frame-sakura-enhanced',10000,60,0,''),
  ('オーロラ','frame-aurora',24000,120,0,''),
  ('サンタ','frame-santa',39000,100,0,''),
  ('ネオンⅢ','frame-neon-style',18000,90,0,''),
  ('新春','frame-newyear',50000,500,5,''),
  ('極寒','frame-arctic',45000,125,3,''),
  ('パープルⅡ','frame-purple-card',15000,45,0,''),
  ('ビーチカード','frame-beach',25000,60,3,''),
  ('ネットランナー','frame-netrunner',56000,140,2,''),
  ('マイアミ風','frame-retro-miami',44000,25,0,''),
  ('集中マスター','frame-master',100000,10,10,'')");
}

// POST処理
if ($_SERVER['REQUEST_METHOD']==='POST'){
  $frame_id = (int)($_POST['frame_id'] ?? 0);
  $act = $_POST['act'] ?? '';
  if ($act==='buy'){
    $fr = $pdo->prepare("SELECT * FROM frames WHERE id=?");
    $fr->execute([$frame_id]);
    $f = $fr->fetch();
    if (!$f){
        $msg='フレームが見つかりません';
    } else {
        if ($f['css_token']==='frame-master' && ($me['focus_tier'] ?? 0) < 10){
            $msg = '集中マスターはティア10以上のユーザーのみ購入可能です';
        } elseif (
            $me['coins'] >= $f['price_coins'] &&
            $me['crystals'] >= $f['price_crystals'] &&
            $me['diamonds'] >= $f['price_diamonds']
        ){
            $pdo->prepare("INSERT IGNORE INTO user_frames(user_id,frame_id) VALUES(?,?)")
                ->execute([$me['id'],$frame_id]);
            $pdo->prepare("UPDATE users SET coins=coins-?, crystals=crystals-?, diamonds=diamonds-? WHERE id=?")
                ->execute([$f['price_coins'],$f['price_crystals'],$f['price_diamonds'],$me['id']]);
            $pdo->prepare("INSERT INTO reward_events(user_id,kind,amount,meta) VALUES(?,?,?,JSON_OBJECT('frame_id',?))")
                ->execute([$me['id'],'buy_frame',-$f['price_coins'],$frame_id]);
            $msg='購入しました';
        } else {
            $msg='残高不足';
        }
    }
  } elseif ($act==='equip'){
    $own = $pdo->prepare("SELECT 1 FROM user_frames WHERE user_id=? AND frame_id=? ");
    $own->execute([$me['id'],$frame_id]);
    if ($own->fetch()){
        $pdo->prepare("UPDATE users SET active_frame_id=? WHERE id=?")
            ->execute([$frame_id,$me['id']]);
        $msg='装備しました';
    } else {
        $msg='未購入です';
    }
  }
  header('Location: shop.php');
  exit;
}

$frames = $pdo->query("
    SELECT f.*,
           (SELECT 1 FROM user_frames uf WHERE uf.user_id={$me['id']} AND uf.frame_id=f.id) owned
    FROM frames f
    ORDER BY id
")->fetchAll();

$active = (int)($me['active_frame_id'] ?? 0);
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>フレームショップ - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
<style>
.shop-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}
.shop-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
}
.shop-header h1 {
    margin: 0 0 15px 0;
    font-size: 36px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.balance-display {
    display: flex;
    justify-content: center;
    gap: 30px;
    font-size: 20px;
    font-weight: bold;
}
.balance-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    backdrop-filter: blur(10px);
}
.frame-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 25px;
    margin-top: 20px;
}
.frame-card {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}
.frame-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
}
.frame-preview {
    min-height: 120px;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 15px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    /*background: #f7fafc;*/
    position: relative;
}
.frame-name {
    font-size: 24px;
    font-weight: bold;
    color: #2d3748;
    margin: 0 0 10px 0;
    text-align: center;
}
.frame-price {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin: 15px 0;
    font-size: 16px;
    color: #4a5568;
}
.price-item {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    background: #edf2f7;
    border-radius: 12px;
    font-weight: bold;
}
.frame-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}
.frame-actions button {
    flex: 1;
    padding: 12px 20px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-buy {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.btn-buy:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}
.btn-buy:disabled {
    background: #cbd5e0;
    cursor: not-allowed;
}
.btn-equip {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
}
.btn-equip:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(72, 187, 120, 0.4);
}
.btn-equipped {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    cursor: default;
}
.owned-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #48bb78;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}
.equipped-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #f59e0b;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}
.special-requirement {
    background: #fed7d7;
    color: #c53030;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 13px;
    margin: 10px 0;
    text-align: center;
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
.back-link {
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 20px;
    background: white;
    color: #667eea;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
}
.back-link:hover {
    background: #667eea;
    color: white;
}
</style>
</head>
<body>
<div class="shop-container">
    <a href="./" class="back-link">← フィードに戻る</a>

    <div class="shop-header">
        <h1>🛍️ フレームショップ</h1>
        <p style="margin: 10px 0; opacity: 0.9;">投稿を彩る華やかなフレームをゲットしよう！</p>
        <div class="balance-display">
            <div class="balance-item">
                <span>🪙</span>
                <span><?= number_format($me['coins']) ?> コイン</span>
            </div>
            <div class="balance-item">
                <span>💎</span>
                <span><?= number_format($me['crystals']) ?> クリスタル</span>
            </div>
            <div class="balance-item">
                <span>💠</span>
                <span><?= number_format($me['diamonds']) ?> ダイヤモンド</span>
            </div>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
    <div class="alert <?= strpos($_GET['msg'], '不足') !== false || strpos($_GET['msg'], '失敗') !== false ? 'alert-error' : 'alert-success' ?>">
        <?= htmlspecialchars($_GET['msg']) ?>
    </div>
    <?php endif; ?>

    <div class="frame-grid">
    <?php foreach ($frames as $f): ?>
        <div class="frame-card">
            <?php if($active === $f['id']): ?>
                <span class="equipped-badge">装備中</span>
            <?php elseif($f['owned']): ?>
                <span class="owned-badge">所有済み</span>
            <?php endif; ?>

            <div class="frame-preview <?= $f['css_token'] ?>">
                <div class="frame-name"><?= htmlspecialchars($f['name']) ?></div>
                <p style="margin: 0; color: #718096;">フレームプレビュー</p>
            </div>

            <?php if($f['css_token'] === 'frame-master' && ($me['focus_tier'] ?? 0) < 10): ?>
            <div class="special-requirement">
                ⚠️ 集中タイマー ティア10以上が必要
            </div>
            <?php endif; ?>

            <div class="frame-price">
                <?php if($f['price_coins'] > 0): ?>
                <div class="price-item">
                    <span>🪙</span>
                    <span><?= number_format($f['price_coins']) ?></span>
                </div>
                <?php endif; ?>
                <?php if($f['price_crystals'] > 0): ?>
                <div class="price-item">
                    <span>💎</span>
                    <span><?= number_format($f['price_crystals']) ?></span>
                </div>
                <?php endif; ?>
                <?php if($f['price_diamonds'] > 0): ?>
                <div class="price-item diamond">
                    <span>💠</span>
                    <span><?= number_format($f['price_diamonds']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="frame-actions">
                <?php if($f['owned']): ?>
                    <?php if($active === $f['id']): ?>
                        <button class="btn-equipped" disabled>✅ 装備中</button>
                    <?php else: ?>
                        <form method="post" style="width: 100%;">
                            <input type="hidden" name="frame_id" value="<?= $f['id'] ?>">
                            <input type="hidden" name="act" value="equip">
                            <button type="submit" class="btn-equip">装備する</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <form method="post" style="width: 100%;">
                        <input type="hidden" name="frame_id" value="<?= $f['id'] ?>">
                        <input type="hidden" name="act" value="buy">
                        <button type="submit" class="btn-buy"
                            <?php if(
                                $me['coins'] < $f['price_coins'] ||
                                $me['crystals'] < $f['price_crystals'] ||
                                $me['diamonds'] < $f['price_diamonds']
                            ): ?>
                                disabled title="残高不足"
                            <?php endif; ?>
                            <?php if($f['css_token'] === 'frame-master' && ($me['focus_tier'] ?? 0) < 10): ?>
                                disabled title="ティア10以上が必要"
                            <?php endif; ?>>
                            🛒 購入する
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>
</body>
</html>
