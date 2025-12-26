<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'config.php';
require_once __DIR__ . '/exp_system.php';

$targetId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$handle = $_GET['handle'] ?? null;

if (!$targetId && $handle) {
    $st = db()->prepare("SELECT id FROM users WHERE handle=?");
    $st->execute([$handle]);
    $userRow = $st->fetch();
    if ($userRow) $targetId = (int)$userRow['id'];
}

if (!$targetId) die('ユーザーIDが指定されていません');

$pdo = db();
$st = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$st->execute([$targetId]);
$user = $st->fetch();
if (!$user) die('ユーザーが存在しません');

// オンライン状態を計算
$isOnline = false;
$lastSeenText = '';
$lastActivity = $user['last_activity'] ?? null;

if ($lastActivity) {
    $lastActivityTime = strtotime($lastActivity);
    $now = time();
    $diffMinutes = floor(($now - $lastActivityTime) / 60);
    
    if ($diffMinutes < 15) {
        // 15分以内はオンライン
        $isOnline = true;
        if ($diffMinutes < 1) {
            $lastSeenText = '今オンライン';
        } else {
            $lastSeenText = "{$diffMinutes}分前にアクティブ";
        }
    } else {
        // 15分以上でオフライン
        $isOnline = false;
        if ($diffMinutes < 60) {
            $lastSeenText = "{$diffMinutes}分前";
        } elseif ($diffMinutes < 1440) {
            $hours = floor($diffMinutes / 60);
            $lastSeenText = "{$hours}時間前";
        } else {
            $days = floor($diffMinutes / 1440);
            $lastSeenText = "{$days}日前";
        }
    }
} else {
    $lastSeenText = '不明';
}

// ログイン中ユーザー
$me = user();

// フォロー状態（ログイン中ユーザーのみ）
$isFollowing = false;
if ($me) {
    if ($me['id'] !== $targetId) {
        $st = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followee_id = ?");
        $st->execute([$me['id'], $targetId]);
        $isFollowing = (bool)$st->fetchColumn();
    }
}

// フォロー数・フォロワー数を取得
$st = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$st->execute([$targetId]);
$followingCount = (int)$st->fetchColumn();

$st = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE followee_id = ?");
$st->execute([$targetId]);
$followersCount = (int)$st->fetchColumn();

// フォロー中リストを取得
$st = $pdo->prepare("
    SELECT u.id, u.handle, u.display_name, u.icon 
    FROM follows f 
    JOIN users u ON f.followee_id = u.id 
    WHERE f.follower_id = ?
    ORDER BY f.created_at DESC
");
$st->execute([$targetId]);
$followingList = $st->fetchAll(PDO::FETCH_ASSOC);

// フォロワーリストを取得
$st = $pdo->prepare("
    SELECT u.id, u.handle, u.display_name, u.icon 
    FROM follows f 
    JOIN users u ON f.follower_id = u.id 
    WHERE f.followee_id = ?
    ORDER BY f.created_at DESC
");
$st->execute([$targetId]);
$followersList = $st->fetchAll(PDO::FETCH_ASSOC);

// 装備中の装備を取得
$equippedItems = [];
$totalBuffs = [];
try {
    $st = $pdo->prepare("SELECT * FROM user_equipment WHERE user_id = ? AND is_equipped = 1");
    $st->execute([$targetId]);
    $equippedItems = $st->fetchAll(PDO::FETCH_ASSOC);
    
    // 装備中の装備からバフ合計を計算
    foreach ($equippedItems as $item) {
        $buffs = json_decode($item['buffs'], true);
        if ($buffs && is_array($buffs)) {
            foreach ($buffs as $buffKey => $buffValue) {
                if (!isset($totalBuffs[$buffKey])) {
                    $totalBuffs[$buffKey] = 0;
                }
                $totalBuffs[$buffKey] += $buffValue;
            }
        }
    }
} catch (PDOException $e) {
    // テーブルがまだ存在しない場合は無視
    $equippedItems = [];
    $totalBuffs = [];
}

// 装備用の定数
$RARITIES = [
    'normal' => ['name' => 'ノーマル', 'color' => '#808080'],
    'rare' => ['name' => 'レア', 'color' => '#00cc00'],
    'unique' => ['name' => 'ユニーク', 'color' => '#0080ff'],
    'legend' => ['name' => 'レジェンド', 'color' => '#ffcc00'],
    'epic' => ['name' => 'エピック', 'color' => '#cc00ff'],
    'hero' => ['name' => 'ヒーロー', 'color' => '#ff0000'],
    'mythic' => ['name' => 'ミシック', 'color' => 'rainbow']
];
$SLOTS = [
    'weapon' => ['name' => '武器', 'icon' => '⚔️'],
    'helm' => ['name' => 'ヘルム', 'icon' => '🪖'],
    'body' => ['name' => 'ボディ', 'icon' => '🛡️'],
    'shoulder' => ['name' => 'ショルダー', 'icon' => '🎽'],
    'arm' => ['name' => 'アーム', 'icon' => '🧤'],
    'leg' => ['name' => 'レッグ', 'icon' => '👢']
];
// バフ種類定義 (equipment.phpと同じ定義を使用)
$BUFF_TYPES = [
    'attack' => ['name' => '攻撃力', 'icon' => '⚔️'],
    'armor' => ['name' => 'アーマー', 'icon' => '🛡️'],
    'health' => ['name' => '体力', 'icon' => '❤️'],
    'coin_drop' => ['name' => 'コインドロップ', 'icon' => '🪙', 'unit' => '%'],
    'crystal_drop' => ['name' => 'クリスタルドロップ', 'icon' => '💎', 'unit' => '%'],
    'token_normal_drop' => ['name' => 'ノーマルトークンドロップ', 'icon' => '⚪', 'unit' => '%'],
    'token_rare_drop' => ['name' => 'レアトークンドロップ', 'icon' => '🟢', 'unit' => '%'],
    'exp_bonus' => ['name' => '経験値ボーナス', 'icon' => '⭐', 'unit' => '%']
];

// ユーザーレベル情報を取得
$levelInfo = get_user_level_info($targetId);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@<?=htmlspecialchars($user['handle'])?> のプロフィール</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
/* プロフィールページのレスポンシブデザイン */
.profile-header {
    background: var(--card);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.profile-info {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.user-icon {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--blue);
    margin: 0 auto;
    display: block;
}

.user-display-name {
    font-size: 24px;
    font-weight: bold;
    text-align: center;
    margin-top: 10px;
}

.user-handle {
    color: var(--muted);
    text-align: center;
    font-size: 16px;
}

.user-bio {
    text-align: center;
    margin: 15px 0;
    line-height: 1.6;
}

.user-stats {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.stat-item {
    background: linear-gradient(135deg, rgba(29, 155, 240, 0.1), rgba(118, 75, 162, 0.1));
    padding: 12px 20px;
    border-radius: 8px;
    text-align: center;
    min-width: 100px;
}

.stat-label {
    font-size: 12px;
    color: var(--muted);
    display: block;
}

.stat-value {
    font-size: 20px;
    font-weight: bold;
    color: var(--blue);
}

.profile-actions {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
}

.profile-actions button {
    padding: 10px 24px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

#follow-btn {
    background: var(--blue);
    color: white;
    border: none;
}

#follow-btn.following {
    background: transparent;
    border: 2px solid var(--blue);
    color: var(--blue);
}

/* 編集フォーム */
.profile-edit {
    background: var(--card);
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
}

.profile-edit h3 {
    margin-bottom: 15px;
}

.profile-edit form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.profile-edit label {
    display: block;
    color: var(--muted);
    margin-bottom: 5px;
    font-size: 14px;
}

.profile-edit input,
.profile-edit textarea {
    width: 100%;
    padding: 10px;
    border: 2px solid var(--border);
    border-radius: 8px;
    background: var(--bg);
    color: var(--text);
    font-family: inherit;
}

.profile-edit button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
}

/* フォロー統計 */
.follow-stats {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin: 20px 0;
}

.follow-stat-item {
    text-align: center;
    cursor: pointer;
    padding: 10px 20px;
    border-radius: 8px;
    transition: background 0.3s;
}

.follow-stat-item:hover {
    background: rgba(29, 155, 240, 0.1);
}

.follow-stat-count {
    font-size: 24px;
    font-weight: bold;
    color: var(--text);
    display: block;
}

.follow-stat-label {
    font-size: 14px;
    color: var(--muted);
}

/* フォローリストモーダル */
.follow-list-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.follow-list-modal.hidden {
    display: none;
}

.follow-list-content {
    background: var(--card);
    border-radius: 12px;
    width: 400px;
    max-width: 90vw;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.follow-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
}

.follow-list-header h3 {
    margin: 0;
    font-size: 18px;
}

.follow-list-close {
    background: transparent;
    border: none;
    color: var(--muted);
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
}

.follow-list-close:hover {
    background: var(--border);
    color: var(--text);
}

.follow-list-body {
    overflow-y: auto;
    padding: 10px 0;
    flex: 1;
}

.follow-list-empty {
    text-align: center;
    padding: 40px 20px;
    color: var(--muted);
}

.follow-user-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    transition: background 0.2s;
    text-decoration: none;
    color: inherit;
}

.follow-user-item:hover {
    background: rgba(29, 155, 240, 0.1);
}

.follow-user-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.follow-user-info {
    flex: 1;
    min-width: 0;
}

.follow-user-name {
    font-weight: bold;
    color: var(--text);
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.follow-user-handle {
    color: var(--muted);
    font-size: 14px;
}

/* オンライン状態インジケーター */
.online-status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 10px 0;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
}

.online-status.online {
    background: linear-gradient(135deg, rgba(72, 187, 120, 0.1), rgba(72, 187, 120, 0.2));
    color: #48bb78;
    border: 1px solid rgba(72, 187, 120, 0.3);
}

.online-status.offline {
    background: linear-gradient(135deg, rgba(160, 174, 192, 0.1), rgba(160, 174, 192, 0.2));
    color: #a0aec0;
    border: 1px solid rgba(160, 174, 192, 0.3);
}

.online-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.online-dot.online {
    background: #48bb78;
    box-shadow: 0 0 8px rgba(72, 187, 120, 0.6);
    animation: pulse-online 2s infinite;
}

.online-dot.offline {
    background: #a0aec0;
}

@keyframes pulse-online {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.2); }
}

/* タブレット・モバイル対応 */
@media (max-width: 768px) {
    .user-icon {
        width: 100px;
        height: 100px;
    }
    
    .user-display-name {
        font-size: 20px;
    }
    
    .user-stats {
        gap: 10px;
    }
    
    .stat-item {
        min-width: 80px;
        padding: 10px 15px;
    }
    
    .stat-value {
        font-size: 18px;
    }
    
    .follow-stats {
        gap: 20px;
    }
    
    .follow-stat-count {
        font-size: 20px;
    }
    
    .follow-list-content {
        width: 95vw;
    }
}
</style>
</head>
<body>
<header>
    <h1><a href="/" style="text-decoration: none; color: inherit;">🐦 MiniBird</a></h1>
</header>
<main class="layout">
    <section class="center">
        <div class="profile-header">
            <div class="profile-info">
                <!-- アイコン -->
                <img src="<?= htmlspecialchars($user['icon'] ?? 'assets/default_icon.png') ?>" 
                     class="user-icon" alt="アイコン">

                <!-- 表示名 -->
                <div class="user-display-name">
                    <?= htmlspecialchars($user['display_name'] ?? $user['handle']) ?>
                    <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
                        <span class="role-badge admin-badge">ADMIN</span>
                    <?php elseif (isset($user['role']) && $user['role'] === 'mod'): ?>
                        <span class="role-badge mod-badge">MOD</span>
                    <?php endif; ?>
                </div>
                <div class="user-handle">@<?= htmlspecialchars($user['handle']) ?></div>

                <!-- オンライン状態インジケーター -->
                <div class="online-status <?= $isOnline ? 'online' : 'offline' ?>">
                    <span class="online-dot <?= $isOnline ? 'online' : 'offline' ?>"></span>
                    <span><?= $isOnline ? '🟢 オンライン' : '⚫ オフライン' ?></span>
                    <span style="opacity: 0.7; font-size: 12px;">(<?= htmlspecialchars($lastSeenText) ?>)</span>
                </div>
                
                <!-- ユーザーレベル表示 -->
                <?php if ($levelInfo): ?>
                <div class="user-level-section" style="margin: 15px 0; padding: 15px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); border-radius: 12px; border: 1px solid rgba(102, 126, 234, 0.3);">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 10px;">
                        <div style="font-size: 36px; font-weight: bold; color: #667eea;">Lv.<?= $levelInfo['level'] ?></div>
                        <div style="font-size: 14px; color: var(--muted);">⭐ <?= number_format($levelInfo['current_exp']) ?> EXP</div>
                    </div>
                    <div style="background: rgba(0,0,0,0.3); border-radius: 10px; height: 14px; overflow: hidden;">
                        <div style="width: <?= min(100, $levelInfo['progress_percent']) ?>%; height: 100%; background: linear-gradient(90deg, #667eea, #764ba2); transition: width 0.5s; display: flex; align-items: center; justify-content: flex-end; padding-right: 5px;">
                            <span style="font-size: 10px; color: white; font-weight: bold;"><?= $levelInfo['progress_percent'] ?>%</span>
                        </div>
                    </div>
                    <div style="text-align: center; font-size: 12px; color: var(--muted); margin-top: 5px;">
                        次のレベルまで: <?= number_format($levelInfo['level_exp_needed'] - $levelInfo['level_exp']) ?> EXP
                    </div>
                </div>
                <?php endif; ?>

                <!-- 自己紹介 -->
                <div class="user-bio"><?= nl2br(htmlspecialchars($user['bio'] ?? '')) ?></div>

                <!-- フォロー数・フォロワー数 -->
                <div class="follow-stats">
                    <div class="follow-stat-item" id="showFollowing" data-count="<?= $followingCount ?>">
                        <span class="follow-stat-count"><?= $followingCount ?></span>
                        <span class="follow-stat-label">フォロー中</span>
                    </div>
                    <div class="follow-stat-item" id="showFollowers" data-count="<?= $followersCount ?>">
                        <span class="follow-stat-count"><?= $followersCount ?></span>
                        <span class="follow-stat-label">フォロワー</span>
                    </div>
                </div>

                <!-- 通貨情報 -->
                <div class="user-stats">
                    <div class="stat-item">
                        <span class="stat-label">コイン</span>
                        <span class="stat-value">💰<?=$user['coins']?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">クリスタル</span>
                        <span class="stat-value">💎<?=$user['crystals']?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">ダイヤモンド</span>
                        <span class="stat-value">💠<?=$user['diamonds'] ?? 0?></span>
                    </div>
                </div>
                
                <!-- トークン情報 -->
                <div class="token-stats">
                    <h4 style="text-align: center; margin: 20px 0 15px 0; color: var(--text); font-size: 18px;">🎫 トークン</h4>
                    <div class="token-grid">
                        <div class="token-item token-normal">
                            <span class="token-icon">⚪</span>
                            <span class="token-label">ノーマル</span>
                            <span class="token-count"><?=$user['normal_tokens'] ?? 0?></span>
                        </div>
                        <div class="token-item token-rare">
                            <span class="token-icon">🟢</span>
                            <span class="token-label">レア</span>
                            <span class="token-count"><?=$user['rare_tokens'] ?? 0?></span>
                        </div>
                        <div class="token-item token-unique">
                            <span class="token-icon">🔵</span>
                            <span class="token-label">ユニーク</span>
                            <span class="token-count"><?=$user['unique_tokens'] ?? 0?></span>
                        </div>
                        <div class="token-item token-legend">
                            <span class="token-icon">🟡</span>
                            <span class="token-label">レジェンド</span>
                            <span class="token-count"><?=$user['legend_tokens'] ?? 0?></span>
                        </div>
                        <div class="token-item token-epic">
                            <span class="token-icon">🟣</span>
                            <span class="token-label">エピック</span>
                            <span class="token-count"><?=$user['epic_tokens'] ?? 0?></span>
                        </div>
                        <div class="token-item token-hero">
                            <span class="token-icon">🔴</span>
                            <span class="token-label">ヒーロー</span>
                            <span class="token-count"><?=$user['hero_tokens'] ?? 0?></span>
                        </div>
                        <div class="token-item token-mythic">
                            <span class="token-icon">🌈</span>
                            <span class="token-label">ミシック</span>
                            <span class="token-count"><?=$user['mythic_tokens'] ?? 0?></span>
                        </div>
                    </div>
                </div>

                <!-- 装備中の装備 -->
                <?php if (!empty($equippedItems)): ?>
                <div class="equipped-section" style="margin-top: 25px; width: 100%;">
                    <h4 style="text-align: center; margin: 20px 0 15px 0; color: var(--text); font-size: 18px;">⚔️ 装備中</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                        <?php foreach ($equippedItems as $eq): 
                            $buffs = json_decode($eq['buffs'], true) ?: [];
                            $rarity = $RARITIES[$eq['rarity']] ?? ['name' => $eq['rarity'], 'color' => '#888'];
                            $slot = $SLOTS[$eq['slot']] ?? ['name' => $eq['slot'], 'icon' => '❓'];
                            $upgrade_level = (int)($eq['upgrade_level'] ?? 0);
                            $upgrade_display = $upgrade_level > 0 ? ' +' . $upgrade_level : '';
                        ?>
                        <div style="background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%); border-radius: 10px; padding: 12px; border-left: 3px solid <?= $rarity['color'] === 'rainbow' ? '#cc00ff' : $rarity['color'] ?>;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                <span style="font-size: 20px;"><?= $slot['icon'] ?></span>
                                <span style="font-size: 14px; font-weight: bold; color: #fff;"><?= htmlspecialchars($eq['name']) ?><?= $upgrade_display ?></span>
                            </div>
                            <div style="font-size: 11px; color: <?= $rarity['color'] === 'rainbow' ? '#cc00ff' : $rarity['color'] ?>; margin-bottom: 5px;"><?= $rarity['name'] ?></div>
                            <?php foreach ($buffs as $buff_key => $value): 
                                $buffInfo = $BUFF_TYPES[$buff_key] ?? ['name' => $buff_key, 'icon' => '❓', 'unit' => ''];
                            ?>
                            <div style="font-size: 11px; color: #00ff88;"><?= $buffInfo['icon'] ?> +<?= $value ?><?= $buffInfo['unit'] ?? '' ?> <?= htmlspecialchars($buffInfo['name']) ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- バフ合計値 -->
                    <?php if (!empty($totalBuffs)): ?>
                    <div style="margin-top: 20px; background: linear-gradient(135deg, rgba(107, 91, 149, 0.15) 0%, rgba(139, 75, 139, 0.15) 100%); border-radius: 12px; padding: 20px; border: 2px solid rgba(107, 91, 149, 0.3);">
                        <h4 style="text-align: center; margin: 0 0 15px 0; color: var(--text); font-size: 16px; font-weight: bold;">✨ 合計バフ効果</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px;">
                            <?php foreach ($totalBuffs as $buffKey => $buffValue): 
                                $buffInfo = $BUFF_TYPES[$buffKey] ?? ['name' => $buffKey, 'icon' => '❓', 'unit' => ''];
                            ?>
                            <div style="background: rgba(0, 255, 136, 0.05); border-radius: 8px; padding: 10px; border-left: 3px solid #00ff88;">
                                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                    <span style="font-size: 16px;"><?= $buffInfo['icon'] ?></span>
                                    <span style="font-size: 12px; color: #aaa;"><?= htmlspecialchars($buffInfo['name']) ?></span>
                                </div>
                                <div style="font-size: 18px; font-weight: bold; color: #00ff88; text-align: right;">
                                    +<?= number_format($buffValue, 2) ?><?= $buffInfo['unit'] ?? '' ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($me && $me['id'] !== $targetId): ?>
                    <div class="profile-actions">
                        <button id="follow-btn" data-user-id="<?= $user['id'] ?>" class="<?= $isFollowing ? 'following' : '' ?>">
                            <?= $isFollowing ? 'フォロー解除' : 'フォローする' ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($me && $me['id'] === $targetId): ?>
        <div class="profile-edit">
            <h3>プロフィール編集</h3>
            <form id="profileForm" enctype="multipart/form-data">
                <div>
                    <label>表示名:</label>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($user['display_name'] ?? '') ?>">
                </div>

                <div>
                    <label>自己紹介:</label>
                    <textarea name="bio" maxlength="280"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>

                <div>
                    <label>アイコン:</label>
                    <input type="file" name="icon" accept="image/*">
                </div>
                
                <button type="submit">保存</button>
            </form>
        </div>
        <?php endif; ?>
        
        <div id="feed" data-feed="user_<?=$targetId?>"></div>
        <div id="loading">読み込み中...</div>
    </section>
</main>

<!-- フォロー中リストモーダル -->
<div id="followingModal" class="follow-list-modal hidden">
    <div class="follow-list-content">
        <div class="follow-list-header">
            <h3>フォロー中</h3>
            <button class="follow-list-close" onclick="closeFollowingModal()">&times;</button>
        </div>
        <div class="follow-list-body">
            <?php if (empty($followingList)): ?>
                <div class="follow-list-empty">まだ誰もフォローしていません</div>
            <?php else: ?>
                <?php foreach ($followingList as $followUser): ?>
                    <a href="profile.php?id=<?= (int)$followUser['id'] ?>" class="follow-user-item">
                        <img src="<?= htmlspecialchars($followUser['icon'] ?? 'assets/default_icon.png') ?>" class="follow-user-icon" alt="">
                        <div class="follow-user-info">
                            <span class="follow-user-name"><?= htmlspecialchars($followUser['display_name'] ?? $followUser['handle']) ?></span>
                            <span class="follow-user-handle">@<?= htmlspecialchars($followUser['handle']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- フォロワーリストモーダル -->
<div id="followersModal" class="follow-list-modal hidden">
    <div class="follow-list-content">
        <div class="follow-list-header">
            <h3>フォロワー</h3>
            <button class="follow-list-close" onclick="closeFollowersModal()">&times;</button>
        </div>
        <div class="follow-list-body">
            <?php if (empty($followersList)): ?>
                <div class="follow-list-empty">まだフォロワーがいません</div>
            <?php else: ?>
                <?php foreach ($followersList as $followerUser): ?>
                    <a href="profile.php?id=<?= (int)$followerUser['id'] ?>" class="follow-user-item">
                        <img src="<?= htmlspecialchars($followerUser['icon'] ?? 'assets/default_icon.png') ?>" class="follow-user-icon" alt="">
                        <div class="follow-user-info">
                            <span class="follow-user-name"><?= htmlspecialchars($followerUser['display_name'] ?? $followerUser['handle']) ?></span>
                            <span class="follow-user-handle">@<?= htmlspecialchars($followerUser['handle']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="assets/app.js?v=<?= ASSETS_VERSION ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const feedEl = document.getElementById('feed');
    if (feedEl) state.feed = feedEl.dataset.feed;
    refreshFeed(true);

    // フォローボタン
    const btn = document.getElementById("follow-btn");
    if (btn) {
        btn.addEventListener("click", async () => {
            const isFollowing = btn.classList.contains("following");
            const targetId = btn.dataset.userId;
            const action = isFollowing ? "unfollow" : "follow";

            try {
                const res = await fetch('follow_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=${action}&target_id=${targetId}`,
                    credentials: 'same-origin'
                });
                const data = await res.json();

                if (data.status === "success") {
                    if (isFollowing) {
                        btn.classList.remove("following");
                        btn.textContent = "フォローする";
                    } else {
                        btn.classList.add("following");
                        btn.textContent = "フォロー解除";
                    }
                } else {
                    alert(data.message || "エラーが発生しました");
                }
            } catch (e) {
                alert("通信エラーが発生しました");
            }
        });
    }

    // プロフィール編集フォーム
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.onsubmit = async (e) => {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);

            try {
                const res = await fetch('users_api.php', {
                    method: 'POST',
                    body: data
                });
                const json = await res.json();
                if (json.ok) {
                    alert('プロフィール更新完了');
                    if (json.icon) document.querySelector('.user-icon').src = json.icon;
                    if (json.bio) document.querySelector('.user-bio').textContent = json.bio;
                    if (json.display_name) document.querySelector('.user-display-name').textContent = json.display_name;
                } else {
                    alert('更新失敗');
                }
            } catch (err) {
                alert('通信エラーが発生しました');
            }
        };
    }
    
    // フォロー数/フォロワー数クリックでモーダル表示（DOMContentLoaded内に移動）
    document.getElementById('showFollowing')?.addEventListener('click', showFollowingModal);
    document.getElementById('showFollowers')?.addEventListener('click', showFollowersModal);
});

// フォローリストモーダル制御
function showFollowingModal() {
    document.getElementById('followingModal').classList.remove('hidden');
}

function closeFollowingModal() {
    document.getElementById('followingModal').classList.add('hidden');
}

function showFollowersModal() {
    document.getElementById('followersModal').classList.remove('hidden');
}

function closeFollowersModal() {
    document.getElementById('followersModal').classList.add('hidden');
}

// モーダルの外側クリックで閉じる
document.getElementById('followingModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'followingModal') closeFollowingModal();
});

document.getElementById('followersModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'followersModal') closeFollowersModal();
});

// ESCキーでモーダル閉じる
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeFollowingModal();
        closeFollowersModal();
    }
});

</script>
</body>
</html>
