<?php
require_once __DIR__ . '/config.php';
$me = user();
if (!$me){ header('Location: ./'); exit; }
$pdo = db();

// Seed sample frames if none
// Seed sample frames if none
$cnt = (int)$pdo->query("SELECT COUNT(*) FROM frames")->fetchColumn();
if ($cnt===0){
  $pdo->exec("INSERT INTO frames(name,css_token,price_coins,price_crystals,preview_css) VALUES
  ('クラシック','frame-classic',200,0,''),
  ('ネオン','frame-neon',800,2,''),
  ('サクラ','frame-sakura',500,1,''),
  ('花火','frame-fireworks',600,1,''),
  ('サイバーパンク','frame-cyberpunk',900,3,''),
  ('ネオン文字','frame-neon-text',850,2,''),
  ('VIP','frame-vip',2000,5,''),
  ('パープル','frame-purple',700,1,''),
  ('星空','frame-stars',50000,5,''),
  ('ラブリー','frame-lovely',30000,3,''),
  ('炎','frame-flame',40000,4,''),
  ('クリスマス','frame-christmas',30000,50,''),
  ('冬','frame-winter',5000,50,''),
  ('サクラⅡ','frame-sakura-enhanced',10000,60,''),
  ('オーロラ','frame-aurora',24000,120,''),
  ('サンタ','frame-santa',39000,100,''),
  ('ネオン','frame-neon-style',18000,90,''),
  ('集中マスター','frame-master',100000,10','')"); // 集中マスターはティア10以上限定
}

// POST処理
if ($_SERVER['REQUEST_METHOD']==='POST'){
  $frame_id = (int)($_POST['frame_id'] ?? 0);
  $act = $_POST['act'] ?? '';
  if ($act==='buy'){
    $fr = $pdo->prepare("SELECT * FROM frames WHERE id=?"); $fr->execute([$frame_id]); $f = $fr->fetch();
    if (!$f){ $msg='フレームが見つかりません'; }
    else {
        // 集中マスターフレームはティア10以上チェック
        if ($f['css_token']==='frame-master' && ($me['focus_tier'] ?? 0) < 10){
            $msg = '集中マスターはティア10以上のユーザーのみ購入可能です';
        } elseif ($me['coins'] >= $f['price_coins'] && $me['crystals'] >= $f['price_crystals']){
            $pdo->prepare("INSERT IGNORE INTO user_frames(user_id,frame_id) VALUES(?,?)")->execute([$me['id'],$frame_id]);
            $pdo->prepare("UPDATE users SET coins=coins-?, crystals=crystals-? WHERE id=?")->execute([$f['price_coins'],$f['price_crystals'],$me['id']]);
            $pdo->prepare("INSERT INTO reward_events(user_id,kind,amount,meta) VALUES(?,?,?,JSON_OBJECT('frame_id',?))")
                ->execute([$me['id'],'buy_frame',-$f['price_coins'],$frame_id]);
            $msg='購入しました';
        } else { $msg='残高不足'; }
    }
  } elseif ($act==='equip'){
    $own = $pdo->prepare("SELECT 1 FROM user_frames WHERE user_id=? AND frame_id=?"); $own->execute([$me['id'],$frame_id]);
    if ($own->fetch()){
      $pdo->prepare("UPDATE users SET active_frame_id=? WHERE id=?")->execute([$frame_id,$me['id']]);
      $msg='装備しました';
    } else { $msg='未購入です'; }
  }
  header("Location: shop.php?msg=".urlencode($msg)); exit;
}


$frames = $pdo->query("SELECT f.*, (SELECT 1 FROM user_frames uf WHERE uf.user_id={$me['id']} AND uf.frame_id=f.id) owned FROM frames f ORDER BY id")->fetchAll();
$active = (int)($me['active_frame_id'] ?? 0);
?>
<!doctype html><html lang="ja"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>フレームショップ - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
</head><body>
<header class="topbar"><div class="logo"><a class="link" href="./">← 戻る</a></div></header>
<main class="layout">
<section class="center">
  <div class="card"><h3>残高</h3>コイン: <?=$me['coins']?> / クリスタル: <?=$me['crystals']?></div>
  <?php if(isset($_GET['msg'])): ?><div class="notice"><?=htmlspecialchars($_GET['msg'])?></div><?php endif; ?>
  <?php foreach ($frames as $f): ?>
    <div class="card">
      <div class="<?=$f['css_token']?>">
        <h3><?=$f['name']?></h3>
        <p>コイン <?=$f['price_coins']?> / クリスタル <?=$f['price_crystals']?></p>
        <?php if($f['owned']): ?>
          <form method="post"><input type="hidden" name="frame_id" value="<?=$f['id']?>"><input type="hidden" name="act" value="equip"><button>装備する<?=$active===$f['id']?'（現在）':''?></button></form>
        <?php else: ?>
          <form method="post"><input type="hidden" name="frame_id" value="<?=$f['id']?>"><input type="hidden" name="act" value="buy"><button>購入する</button></form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</section>
</main>
</body></html>
