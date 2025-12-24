<?php
require_once __DIR__ . '/config.php';

$me = user();
if (!$me){ header('Location: ./login.php'); exit; }
$pdo = db();

// トークン鍛冶のレシピ定義
$FORGE_RECIPES = [
    'crystals_to_normal' => [
        'from' => 'crystals',
        'from_amount' => 10,
        'to' => 'normal_tokens',
        'to_amount' => 1,
        'from_label' => 'クリスタル',
        'from_icon' => '💎',
        'to_label' => 'ノーマルトークン',
        'to_icon' => '⚪'
    ],
    'normal_to_rare' => [
        'from' => 'normal_tokens',
        'from_amount' => 2,
        'to' => 'rare_tokens',
        'to_amount' => 1,
        'from_label' => 'ノーマルトークン',
        'from_icon' => '⚪',
        'to_label' => 'レアトークン',
        'to_icon' => '🟢'
    ],
    'rare_to_unique' => [
        'from' => 'rare_tokens',
        'from_amount' => 3,
        'to' => 'unique_tokens',
        'to_amount' => 1,
        'from_label' => 'レアトークン',
        'from_icon' => '🟢',
        'to_label' => 'ユニークトークン',
        'to_icon' => '🔵'
    ],
    'unique_to_legend' => [
        'from' => 'unique_tokens',
        'from_amount' => 4,
        'to' => 'legend_tokens',
        'to_amount' => 1,
        'from_label' => 'ユニークトークン',
        'from_icon' => '🔵',
        'to_label' => 'レジェンドトークン',
        'to_icon' => '🟡'
    ],
    'legend_to_epic' => [
        'from' => 'legend_tokens',
        'from_amount' => 5,
        'to' => 'epic_tokens',
        'to_amount' => 1,
        'from_label' => 'レジェンドトークン',
        'from_icon' => '🟡',
        'to_label' => 'エピックトークン',
        'to_icon' => '🟣'
    ],
    'epic_to_hero' => [
        'from' => 'epic_tokens',
        'from_amount' => 6,
        'to' => 'hero_tokens',
        'to_amount' => 1,
        'from_label' => 'エピックトークン',
        'from_icon' => '🟣',
        'to_label' => 'ヒーロートークン',
        'to_icon' => '🔴'
    ],
    'hero_to_mythic' => [
        'from' => 'hero_tokens',
        'from_amount' => 7,
        'to' => 'mythic_tokens',
        'to_amount' => 1,
        'from_label' => 'ヒーロートークン',
        'from_icon' => '🔴',
        'to_label' => 'ミシックトークン',
        'to_icon' => '🌈'
    ]
];

// Ajax処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $recipe_key = $_POST['recipe'] ?? '';
    
    if (!isset($FORGE_RECIPES[$recipe_key])) {
        echo json_encode(['ok' => false, 'error' => '不正なレシピです']);
        exit;
    }
    
    $recipe = $FORGE_RECIPES[$recipe_key];
    
    // 現在のユーザー情報を取得
    $st = $pdo->prepare("SELECT * FROM users WHERE id=? FOR UPDATE");
    $pdo->beginTransaction();
    
    try {
        $st->execute([$me['id']]);
        $user = $st->fetch();
        
        // 素材チェック（安全なカラム名のみ許可）
        $from_col = $recipe['from'];
        $to_col = $recipe['to'];
        
        // 許可されたカラム名のホワイトリスト
        $allowed_columns = ['crystals', 'normal_tokens', 'rare_tokens', 'unique_tokens', 'legend_tokens', 'epic_tokens', 'hero_tokens', 'mythic_tokens'];
        if (!in_array($from_col, $allowed_columns) || !in_array($to_col, $allowed_columns)) {
            throw new Exception('不正なカラム名です');
        }
        
        if (($user[$from_col] ?? 0) < $recipe['from_amount']) {
            throw new Exception($recipe['from_label'] . 'が不足しています');
        }
        
        // 素材を消費してトークンを生成（ホワイトリスト検証済みのカラム名を使用）
        $st = $pdo->prepare("UPDATE users SET {$from_col} = {$from_col} - ?, {$to_col} = {$to_col} + ? WHERE id = ?");
        $st->execute([$recipe['from_amount'], $recipe['to_amount'], $me['id']]);
        
        // 履歴を記録
        $st = $pdo->prepare("INSERT INTO token_forge_history (user_id, from_type, from_amount, to_type, to_amount) VALUES (?,?,?,?,?)");
        $st->execute([$me['id'], $from_col, $recipe['from_amount'], $to_col, $recipe['to_amount']]);
        
        $pdo->commit();
        
        // 更新後の残高を取得
        $st = $pdo->prepare("SELECT * FROM users WHERE id=?");
        $st->execute([$me['id']]);
        $updated_user = $st->fetch();
        
        echo json_encode([
            'ok' => true, 
            'message' => $recipe['to_label'] . 'を1つ獲得しました！',
            'balance' => [
                'crystals' => $updated_user['crystals'],
                'normal_tokens' => $updated_user['normal_tokens'] ?? 0,
                'rare_tokens' => $updated_user['rare_tokens'] ?? 0,
                'unique_tokens' => $updated_user['unique_tokens'] ?? 0,
                'legend_tokens' => $updated_user['legend_tokens'] ?? 0,
                'epic_tokens' => $updated_user['epic_tokens'] ?? 0,
                'hero_tokens' => $updated_user['hero_tokens'] ?? 0,
                'mythic_tokens' => $updated_user['mythic_tokens'] ?? 0
            ]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 現在のトークン残高を取得
$st = $pdo->prepare("SELECT * FROM users WHERE id=?");
$st->execute([$me['id']]);
$user = $st->fetch();
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>トークン鍛冶 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
.forge-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.forge-header {
    background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 8px 16px rgba(255, 107, 53, 0.3);
}

.forge-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.forge-header p {
    margin: 0;
    opacity: 0.9;
}

.token-balance {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 10px;
    background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%);
    padding: 20px;
    border-radius: 16px;
    margin-bottom: 30px;
}

.token-balance-item {
    text-align: center;
    padding: 15px 10px;
    border-radius: 10px;
    background: rgba(255,255,255,0.05);
}

.token-balance-item .icon {
    font-size: 24px;
    display: block;
    margin-bottom: 5px;
}

.token-balance-item .count {
    font-size: 20px;
    font-weight: bold;
    color: #fff;
}

.token-balance-item .label {
    font-size: 11px;
    color: #888;
    margin-top: 5px;
}

.forge-recipes {
    display: grid;
    gap: 15px;
}

.forge-recipe {
    background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%);
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s, box-shadow 0.3s;
    border: 1px solid rgba(255,255,255,0.1);
}

.forge-recipe:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
}

.forge-from, .forge-to {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.forge-icon {
    font-size: 36px;
}

.forge-info {
    flex: 1;
}

.forge-info .name {
    font-size: 16px;
    font-weight: bold;
    color: #fff;
}

.forge-info .amount {
    font-size: 14px;
    color: #888;
}

.forge-info .current {
    font-size: 12px;
    color: #666;
}

.forge-arrow {
    font-size: 24px;
    color: #ff6b35;
}

.forge-button {
    padding: 12px 24px;
    background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    white-space: nowrap;
}

.forge-button:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
}

.forge-button:disabled {
    background: #444;
    cursor: not-allowed;
    opacity: 0.6;
}

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 20px;
    background: rgba(255,255,255,0.1);
    color: #ff6b35;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
}

.back-link:hover {
    background: #ff6b35;
    color: white;
}

@media (max-width: 768px) {
    .forge-recipe {
        flex-direction: column;
        text-align: center;
    }
    
    .forge-arrow {
        transform: rotate(90deg);
    }
    
    .forge-from, .forge-to {
        justify-content: center;
    }
    
    .token-balance {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 480px) {
    .token-balance {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
</head>
<body>
<div class="forge-container">
    <a href="./" class="back-link">← フィードに戻る</a>
    
    <div class="forge-header">
        <h1>🔨 トークン鍛冶</h1>
        <p>トークンを消費して上位ランクのトークンを生成しよう！</p>
    </div>
    
    <div class="token-balance" id="tokenBalance">
        <div class="token-balance-item">
            <span class="icon">💎</span>
            <span class="count" id="bal_crystals"><?= $user['crystals'] ?? 0 ?></span>
            <span class="label">クリスタル</span>
        </div>
        <div class="token-balance-item">
            <span class="icon">⚪</span>
            <span class="count" id="bal_normal_tokens"><?= $user['normal_tokens'] ?? 0 ?></span>
            <span class="label">ノーマル</span>
        </div>
        <div class="token-balance-item">
            <span class="icon">🟢</span>
            <span class="count" id="bal_rare_tokens"><?= $user['rare_tokens'] ?? 0 ?></span>
            <span class="label">レア</span>
        </div>
        <div class="token-balance-item">
            <span class="icon">🔵</span>
            <span class="count" id="bal_unique_tokens"><?= $user['unique_tokens'] ?? 0 ?></span>
            <span class="label">ユニーク</span>
        </div>
        <div class="token-balance-item">
            <span class="icon">🟡</span>
            <span class="count" id="bal_legend_tokens"><?= $user['legend_tokens'] ?? 0 ?></span>
            <span class="label">レジェンド</span>
        </div>
        <div class="token-balance-item">
            <span class="icon">🟣</span>
            <span class="count" id="bal_epic_tokens"><?= $user['epic_tokens'] ?? 0 ?></span>
            <span class="label">エピック</span>
        </div>
        <div class="token-balance-item">
            <span class="icon">🔴</span>
            <span class="count" id="bal_hero_tokens"><?= $user['hero_tokens'] ?? 0 ?></span>
            <span class="label">ヒーロー</span>
        </div>
        <div class="token-balance-item">
            <span class="icon">🌈</span>
            <span class="count" id="bal_mythic_tokens"><?= $user['mythic_tokens'] ?? 0 ?></span>
            <span class="label">ミシック</span>
        </div>
    </div>
    
    <div class="forge-recipes">
        <?php foreach ($FORGE_RECIPES as $key => $recipe): ?>
        <div class="forge-recipe">
            <div class="forge-from">
                <span class="forge-icon"><?= $recipe['from_icon'] ?></span>
                <div class="forge-info">
                    <div class="name"><?= $recipe['from_label'] ?></div>
                    <div class="amount">×<?= $recipe['from_amount'] ?> 必要</div>
                </div>
            </div>
            <span class="forge-arrow">→</span>
            <div class="forge-to">
                <span class="forge-icon"><?= $recipe['to_icon'] ?></span>
                <div class="forge-info">
                    <div class="name"><?= $recipe['to_label'] ?></div>
                    <div class="amount">×<?= $recipe['to_amount'] ?> 獲得</div>
                </div>
            </div>
            <button class="forge-button" data-recipe="<?= $key ?>" data-from="<?= $recipe['from'] ?>" data-from-amount="<?= $recipe['from_amount'] ?>">
                鍛冶する
            </button>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// 残高を更新
function updateBalance(balance) {
    for (const [key, value] of Object.entries(balance)) {
        const el = document.getElementById('bal_' + key);
        if (el) el.textContent = value;
    }
    updateButtonStates();
}

// ボタンの状態を更新
function updateButtonStates() {
    document.querySelectorAll('.forge-button').forEach(btn => {
        const from = btn.dataset.from;
        const fromAmount = parseInt(btn.dataset.fromAmount);
        const currentEl = document.getElementById('bal_' + from);
        const current = currentEl ? parseInt(currentEl.textContent) : 0;
        btn.disabled = current < fromAmount;
    });
}

// 鍛冶ボタンのイベント
document.querySelectorAll('.forge-button').forEach(btn => {
    btn.addEventListener('click', async () => {
        const recipe = btn.dataset.recipe;
        
        if (!confirm('本当に鍛冶しますか？\n素材は消費されます。')) {
            return;
        }
        
        btn.disabled = true;
        btn.textContent = '鍛冶中...';
        
        try {
            const formData = new FormData();
            formData.append('recipe', recipe);
            
            const res = await fetch('', {method: 'POST', body: formData});
            const data = await res.json();
            
            if (data.ok) {
                alert('✅ ' + data.message);
                updateBalance(data.balance);
            } else {
                alert('❌ ' + data.error);
            }
        } catch (e) {
            alert('❌ 通信エラーが発生しました');
        }
        
        btn.textContent = '鍛冶する';
        updateButtonStates();
    });
});

// 初期状態を更新
updateButtonStates();
</script>
</body>
</html>
