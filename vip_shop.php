<?php
require_once __DIR__ . '/config.php';
$me = user();
// ファイルの先頭に追加するとエラー内容が見える
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (!$me) {
    header("Location: login.php");
    exit;
}

$pdo = db();

// --- VIP条件チェック関数 ---
//--- VIP条件チェック関数 ---
function can_upgrade_vip($user) {
    $pdo = db();

    // 招待数
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE invite_by = ?");
    $stmt->execute([$user['id']]);
    $invite_count = $stmt->fetchColumn();

    // 累計集中時間 (usersテーブルから直接参照)
    $stmt = $pdo->prepare("SELECT total_focus_time FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $total_minutes = (int)$stmt->fetchColumn();

    $next_level = $user['vip_level'] + 1;
    $need_minutes = 100 * $next_level;
    $need_invites = 2 * $next_level;
    $need_crystals = 100 * $next_level;
    $need_coins = $need_crystals * 100; // クリスタルの100倍
    
    // デイリークエストコンプリート回数（2回から倍増）
    $need_daily_completions = 2 * $next_level;
    
    // ウィークリークエストコンプリート回数（1回から2回ずつ増加）
    $need_weekly_completions = 1 + (2 * ($next_level - 1));
    
    // リレークエストコンプリート回数（1回から倍増）
    $need_relay_completions = 1 * $next_level;

    return (
        $invite_count >= $need_invites && 
        $total_minutes >= $need_minutes &&
        $user['crystals'] >= $need_crystals &&
        $user['coins'] >= $need_coins &&
        $user['daily_quest_completions'] >= $need_daily_completions &&
        $user['weekly_quest_completions'] >= $need_weekly_completions &&
        $user['relay_quest_completions'] >= $need_relay_completions
    );
}

// --- VIP昇格処理 ---
$message = null;
if (isset($_POST['upgrade'])) {
    $next_level = $me['vip_level'] + 1;
    $cost_crystals = 100 * $next_level;
    $cost_coins = $cost_crystals * 100;

    if (!can_upgrade_vip($me)) {
        $message = "⚠️ VIP昇格条件を満たしていません。";
    } elseif ($me['crystals'] < $cost_crystals || $me['coins'] < $cost_coins) {
        $message = "⚠️ コインまたはクリスタルが足りません。";
    } else {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE users SET coins = coins - ?, crystals = crystals - ?, vip_level = vip_level + 1, vip_since = NOW() WHERE id = ?");
        $stmt->execute([$cost_coins, $cost_crystals, $me['id']]);
        $pdo->commit();
        $message = "🎉 VIPレベル{$next_level}になりました！";

        // 最新ユーザー情報を更新
        $me = user();
    }
}

// 招待人数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE invite_by = ?");
$stmt->execute([$me['id']]);
$invite_count = (int)$stmt->fetchColumn();

// 累計集中時間
$total_minutes = (int)$me['total_focus_time'];

// クエストコンプリート回数
$daily_completions = (int)($me['daily_quest_completions'] ?? 0);
$weekly_completions = (int)($me['weekly_quest_completions'] ?? 0);
$relay_completions = (int)($me['relay_quest_completions'] ?? 0);

// 次のVIP条件
$next_level = $me['vip_level'] + 1;
$need_minutes = 100 * $next_level;
$need_invites = 2 * $next_level;
$cost_crystals = 100 * $next_level;
$cost_coins = $cost_crystals * 100;
$need_daily_completions = 2 * $next_level;
$need_weekly_completions = 1 + (2 * ($next_level - 1));
$need_relay_completions = 1 * $next_level;
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>VIPショップ - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
<style>
.vip-badge {
    background: linear-gradient(90deg, gold, orange);
    color: black;
    font-weight: bold;
    border-radius: 8px;
    padding: 2px 6px;
    margin-left: 6px;
    font-size: 0.9em;
}
.vip-card {
    background: #222;
    color: #fff;
    padding: 20px;
    border-radius: 12px;
    margin: 20px auto;
    max-width: 600px;
    text-align: center;
}
.vip-button {
    background: linear-gradient(90deg, gold, orange);
    border: none;
    padding: 10px 20px;
    font-size: 1.1em;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
}
.vip-button:disabled {
    background: #666;
    cursor: not-allowed;
}
</style>
</head>
<body>
<header class="topbar">
    <div class="logo"><a class="link" href="./">← 戻る</a></div>
</header>

<div class="vip-card">
    <h2>VIPショップ</h2>

    <?php if ($message): ?>
        <p><strong><?= htmlspecialchars($message) ?></strong></p>
    <?php endif; ?>

    <p>あなたの現在のVIPレベル: 
        <?php if ($me['vip_level'] > 0): ?>
            <span class="vip-badge">VIP<?= $me['vip_level'] ?></span>
        <?php else: ?>
            なし
        <?php endif; ?>
    </p>
    <p>所持クリスタル: 💎 <?= (int)$me['crystals'] ?></p>

    <?php
    $next_level = $me['vip_level'] + 1;
    $cost_crystals = 100 * $next_level;
    $cost_coins = $cost_crystals * 100;
    ?>
    <p>次のVIPレベル: VIP<?= $next_level ?> (必要クリスタル: <?= $cost_crystals ?> / 必要コイン: <?= $cost_coins ?>)</p>

<div class="vip-conditions">
    <h3>次のVIP昇格条件</h3>
    <ul>
        <li><?= ($invite_count >= $need_invites ? "✅" : "❌") ?> 招待人数: <?= $invite_count ?> / <?= $need_invites ?>人</li>
        <li><?= ($total_minutes >= $need_minutes ? "✅" : "❌") ?> 累計集中時間: <?= $total_minutes ?>分 / <?= $need_minutes ?>分</li>
        <li><?= ($me['crystals'] >= $cost_crystals ? "✅" : "❌") ?> クリスタル: <?= (int)$me['crystals'] ?> / <?= $cost_crystals ?></li>
        <li><?= ($me['coins'] >= $cost_coins ? "✅" : "❌") ?> コイン: <?= (int)$me['coins'] ?> / <?= $cost_coins ?></li>
        <li><?= ($daily_completions >= $need_daily_completions ? "✅" : "❌") ?> デイリークエストコンプリート: <?= $daily_completions ?> / <?= $need_daily_completions ?>回</li>
        <li><?= ($weekly_completions >= $need_weekly_completions ? "✅" : "❌") ?> ウィークリークエストコンプリート: <?= $weekly_completions ?> / <?= $need_weekly_completions ?>回</li>
        <li><?= ($relay_completions >= $need_relay_completions ? "✅" : "❌") ?> リレークエストコンプリート: <?= $relay_completions ?> / <?= $need_relay_completions ?>回</li>
    </ul>
</div>

    <form method="post">
        <button type="submit" name="upgrade" class="vip-button"
            <?php if (!can_upgrade_vip($me) || $me['crystals'] < $cost_crystals || $me['coins'] < $cost_coins): ?>disabled<?php endif; ?>>
            VIP<?= $next_level ?> に昇格する
        </button>
    </form>
</div>

</body>
</html>