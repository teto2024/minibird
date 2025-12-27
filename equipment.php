<?php
require_once __DIR__ . '/config.php';

$me = user();
if (!$me){ header('Location: ./login.php'); exit; }
$pdo = db();

// レアリティ定義（失敗率を半分に設定）
$RARITIES = [
    'normal' => ['name' => 'ノーマル', 'color' => '#808080', 'icon' => '⚪', 'buff_count' => 1, 'fail_rate' => 0, 'token_col' => 'normal_tokens'],
    'rare' => ['name' => 'レア', 'color' => '#00cc00', 'icon' => '🟢', 'buff_count' => 2, 'fail_rate' => 5, 'token_col' => 'rare_tokens'],
    'unique' => ['name' => 'ユニーク', 'color' => '#0080ff', 'icon' => '🔵', 'buff_count' => 3, 'fail_rate' => 10, 'token_col' => 'unique_tokens'],
    'legend' => ['name' => 'レジェンド', 'color' => '#ffcc00', 'icon' => '🟡', 'buff_count' => 4, 'fail_rate' => 15, 'token_col' => 'legend_tokens'],
    'epic' => ['name' => 'エピック', 'color' => '#cc00ff', 'icon' => '🟣', 'buff_count' => 5, 'fail_rate' => 20, 'token_col' => 'epic_tokens'],
    'hero' => ['name' => 'ヒーロー', 'color' => '#ff0000', 'icon' => '🔴', 'buff_count' => 6, 'fail_rate' => 25, 'token_col' => 'hero_tokens'],
    'mythic' => ['name' => 'ミシック', 'color' => 'rainbow', 'icon' => '🌈', 'buff_count' => 7, 'fail_rate' => 30, 'token_col' => 'mythic_tokens']
];

// 装備部位定義
$SLOTS = [
    'weapon' => ['name' => '武器', 'icon' => '⚔️'],
    'helm' => ['name' => 'ヘルム', 'icon' => '🪖'],
    'body' => ['name' => 'ボディ', 'icon' => '🛡️'],
    'shoulder' => ['name' => 'ショルダー', 'icon' => '🎽'],
    'arm' => ['name' => 'アーム', 'icon' => '🧤'],
    'leg' => ['name' => 'レッグ', 'icon' => '👢']
];

// バフ種類定義（レジェンド以上のバフを上方修正）
$BUFF_TYPES = [
    'attack' => ['name' => '攻撃力', 'icon' => '⚔️', 'min' => 1, 'max_normal' => 10, 'max_mythic' => 200],
    'armor' => ['name' => 'アーマー', 'icon' => '🛡️', 'min' => 1, 'max_normal' => 10, 'max_mythic' => 200],
    'health' => ['name' => '体力', 'icon' => '❤️', 'min' => 5, 'max_normal' => 50, 'max_mythic' => 1000],
    'coin_drop' => ['name' => 'コインドロップ', 'icon' => '🪙', 'min' => 1, 'max_normal' => 5, 'max_mythic' => 100, 'unit' => '%'],
    'crystal_drop' => ['name' => 'クリスタルドロップ', 'icon' => '💎', 'min' => 1, 'max_normal' => 3, 'max_mythic' => 60, 'unit' => '%'],
    'token_normal_drop' => ['name' => 'ノーマルトークンドロップ', 'icon' => '⚪', 'min' => 1, 'max_normal' => 5, 'max_mythic' => 100, 'unit' => '%'],
    'token_rare_drop' => ['name' => 'レアトークンドロップ', 'icon' => '🟢', 'min' => 1, 'max_normal' => 4, 'max_mythic' => 80, 'unit' => '%'],
    'exp_bonus' => ['name' => '経験値ボーナス', 'icon' => '⭐', 'min' => 1, 'max_normal' => 5, 'max_mythic' => 50, 'unit' => '%']
];

// レアリティ別バフ倍率（レジェンド以上を上方修正）
$RARITY_BUFF_MULTIPLIERS = [
    'normal' => 1.0,
    'rare' => 1.0,
    'unique' => 1.0,
    'legend' => 1.0,
    'epic' => 1.5,    // エピックは1.5倍
    'hero' => 2.0,    // ヒーローは2倍
    'mythic' => 3.0   // ミシックは3倍
];

$CRAFT_COST_COINS = 10000;
$UPGRADE_BUFF_INCREASE_RATE = 0.10;  // アップグレード時のバフ上昇率（10%）

// 装備作成のAPI処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    
    if ($action === 'craft') {
        $slot = $_POST['slot'] ?? '';
        $rarity = $_POST['rarity'] ?? '';
        
        if (!isset($SLOTS[$slot]) || !isset($RARITIES[$rarity])) {
            echo json_encode(['ok' => false, 'error' => '不正なパラメータです']);
            exit;
        }
        
        $rarity_info = $RARITIES[$rarity];
        $token_col = $rarity_info['token_col'];
        
        // 許可されたカラム名のホワイトリスト
        $allowed_token_columns = ['normal_tokens', 'rare_tokens', 'unique_tokens', 'legend_tokens', 'epic_tokens', 'hero_tokens', 'mythic_tokens'];
        if (!in_array($token_col, $allowed_token_columns)) {
            echo json_encode(['ok' => false, 'error' => '不正なトークンカラムです']);
            exit;
        }
        
        $pdo->beginTransaction();
        try {
            // ユーザー情報を取得
            $st = $pdo->prepare("SELECT * FROM users WHERE id=? FOR UPDATE");
            $st->execute([$me['id']]);
            $user = $st->fetch();
            
            // トークンとコインをチェック
            if (($user[$token_col] ?? 0) < 1) {
                throw new Exception($rarity_info['name'] . 'トークンが不足しています');
            }
            if ($user['coins'] < $CRAFT_COST_COINS) {
                throw new Exception('コインが不足しています（必要: ' . number_format($CRAFT_COST_COINS) . '）');
            }
            
            // 素材を消費（ホワイトリスト検証済みのカラム名を使用）
            $st = $pdo->prepare("UPDATE users SET {$token_col} = {$token_col} - 1, coins = coins - ? WHERE id = ?");
            $st->execute([$CRAFT_COST_COINS, $me['id']]);
            
            // 失敗判定
            $fail_roll = mt_rand(1, 100);
            $success = $fail_roll > $rarity_info['fail_rate'];
            
            $equipment_id = null;
            
            if ($success) {
                // バフを生成
                $buff_count = $rarity_info['buff_count'];
                $buff_keys = array_keys($BUFF_TYPES);
                shuffle($buff_keys);
                $selected_buffs = array_slice($buff_keys, 0, $buff_count);
                
                $buffs = [];
                $rarity_index = array_search($rarity, array_keys($RARITIES));
                $max_rarity_index = count($RARITIES) - 1;
                
                // レアリティ別倍率を取得
                $rarity_multiplier = $RARITY_BUFF_MULTIPLIERS[$rarity] ?? 1.0;
                
                foreach ($selected_buffs as $buff_key) {
                    $buff_info = $BUFF_TYPES[$buff_key];
                    // レアリティに応じて最大値を補間
                    $max_value = $buff_info['max_normal'] + ($buff_info['max_mythic'] - $buff_info['max_normal']) * ($rarity_index / $max_rarity_index);
                    // レジェンド以上の場合、レアリティ倍率を適用
                    $max_value = $max_value * $rarity_multiplier;
                    $value = round($buff_info['min'] + (mt_rand(0, 100) / 100) * ($max_value - $buff_info['min']), 2);
                    $buffs[$buff_key] = $value;
                }
                
                // 装備名を生成（50-100種類を2-3個組み合わせ）
                $prefixes1 = ['輝く', '神秘の', '古代の', '伝説の', '英雄の', '神の', '究極の', '聖なる', '闇の', '炎の', 
                              '氷の', '雷の', '風の', '大地の', '光の', '影の', '星の', '月の', '太陽の', '深淵の',
                              '永遠の', '無限の', '幻の', '真実の', '魔法の', '秘密の', '失われた', '禁断の', '天の', '魂の'];
                $prefixes2 = ['勇者', '賢者', '戦士', '騎士', '魔道士', '竜騎士', '暗殺者', '守護者', '征服者', '破壊者',
                              '創造者', '審判者', '預言者', '解放者', '支配者', '探求者', '覚醒者', '超越者', '救世主', '天使',
                              '悪魔', '精霊', '巨人', '妖精', '英霊', '王者', '覇者', '神官', '導師', '帝王'];
                $suffixes = ['の証', 'の刻印', 'の紋章', 'の守護', 'の力', 'の意志', 'の誓い', 'の運命', 'の奇跡', 'の祝福',
                             'の栄光', 'の輝き', 'の覚醒', 'の結晶', 'の魂', 'の心臓', 'の眼', 'の翼', 'の牙', 'の爪',
                             'の加護', 'の恩恵', 'の試練', 'の遺産', 'の秘宝', 'の継承', 'の約束', 'の希望', 'の絆', 'の軌跡'];
                
                // 2-3個の組み合わせパターンをランダム選択
                $pattern = mt_rand(1, 3);
                switch ($pattern) {
                    case 1:
                        // パターン1: prefix1 + 部位名
                        $name = $prefixes1[array_rand($prefixes1)] . $SLOTS[$slot]['name'];
                        break;
                    case 2:
                        // パターン2: prefix1 + prefix2 + の + 部位名
                        $name = $prefixes1[array_rand($prefixes1)] . $prefixes2[array_rand($prefixes2)] . 'の' . $SLOTS[$slot]['name'];
                        break;
                    case 3:
                        // パターン3: prefix2 + suffix + 部位名
                        $name = $prefixes2[array_rand($prefixes2)] . $suffixes[array_rand($suffixes)] . '・' . $SLOTS[$slot]['name'];
                        break;
                }
                
                // 装備を保存
                $st = $pdo->prepare("
                    INSERT INTO user_equipment (user_id, slot, name, rarity, buffs)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $st->execute([$me['id'], $slot, $name, $rarity, json_encode($buffs)]);
                $equipment_id = $pdo->lastInsertId();
                
                // ヒーロー・ミシック装備（エピック以上）作成時にお知らせbot通知
                $high_tier_rarities = ['epic', 'hero', 'mythic'];
                if (in_array($rarity, $high_tier_rarities)) {
                    $user_st = $pdo->prepare("SELECT handle, display_name FROM users WHERE id = ?");
                    $user_st->execute([$me['id']]);
                    $user_info = $user_st->fetch();
                    $user_name = $user_info['display_name'] ?: $user_info['handle'];
                    
                    $notification_content = "🎉 おめでとうございます！\n\n@{$user_info['handle']} さんが {$rarity_info['icon']} **{$rarity_info['name']}装備**「{$name}」を作成しました！\n\n素晴らしい成果です！👏";
                    
                    $notification_html = markdown_to_html($notification_content);
                    $notify_st = $pdo->prepare("INSERT INTO posts(user_id, content_md, content_html, created_at) VALUES(5, ?, ?, NOW())");
                    $notify_st->execute([$notification_content, $notification_html]);
                }
            }
            
            // 履歴を記録
            $st = $pdo->prepare("
                INSERT INTO equipment_craft_history (user_id, equipment_id, rarity, success, token_used, coins_used)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $st->execute([$me['id'], $equipment_id, $rarity, $success ? 1 : 0, $token_col, $CRAFT_COST_COINS]);
            
            $pdo->commit();
            
            // 更新後のトークン残高を取得
            $st = $pdo->prepare("SELECT * FROM users WHERE id=?");
            $st->execute([$me['id']]);
            $updated_user = $st->fetch();
            
            $balance = [
                'coins' => $updated_user['coins'],
                'normal_tokens' => $updated_user['normal_tokens'] ?? 0,
                'rare_tokens' => $updated_user['rare_tokens'] ?? 0,
                'unique_tokens' => $updated_user['unique_tokens'] ?? 0,
                'legend_tokens' => $updated_user['legend_tokens'] ?? 0,
                'epic_tokens' => $updated_user['epic_tokens'] ?? 0,
                'hero_tokens' => $updated_user['hero_tokens'] ?? 0,
                'mythic_tokens' => $updated_user['mythic_tokens'] ?? 0
            ];
            
            if ($success) {
                echo json_encode([
                    'ok' => true,
                    'success' => true,
                    'message' => '装備の作成に成功しました！',
                    'equipment' => [
                        'id' => $equipment_id,
                        'name' => $name,
                        'slot' => $slot,
                        'rarity' => $rarity,
                        'buffs' => $buffs,
                        'upgrade_level' => 0
                    ],
                    'balance' => $balance
                ]);
            } else {
                echo json_encode([
                    'ok' => true,
                    'success' => false,
                    'message' => '装備の作成に失敗しました...素材は消費されました。',
                    'fail_rate' => $rarity_info['fail_rate'],
                    'balance' => $balance
                ]);
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'equip') {
        $equipment_id = (int)($_POST['equipment_id'] ?? 0);
        
        try {
            // 装備の所有確認
            $st = $pdo->prepare("SELECT * FROM user_equipment WHERE id = ? AND user_id = ?");
            $st->execute([$equipment_id, $me['id']]);
            $equipment = $st->fetch();
            
            if (!$equipment) {
                throw new Exception('装備が見つかりません');
            }
            
            $pdo->beginTransaction();
            
            // 同じ部位の装備で、現在装備中のものを取得
            $st = $pdo->prepare("SELECT id FROM user_equipment WHERE user_id = ? AND slot = ? AND is_equipped = 1");
            $st->execute([$me['id'], $equipment['slot']]);
            $previously_equipped = $st->fetch();
            $previously_equipped_id = $previously_equipped ? $previously_equipped['id'] : null;
            
            // 同じ部位の装備を外す
            $st = $pdo->prepare("UPDATE user_equipment SET is_equipped = 0 WHERE user_id = ? AND slot = ?");
            $st->execute([$me['id'], $equipment['slot']]);
            
            // 装備する
            $st = $pdo->prepare("UPDATE user_equipment SET is_equipped = 1 WHERE id = ?");
            $st->execute([$equipment_id]);
            
            $pdo->commit();
            
            echo json_encode([
                'ok' => true, 
                'message' => '装備しました',
                'equipment_id' => $equipment_id,
                'slot' => $equipment['slot'],
                'previously_equipped_id' => $previously_equipped_id
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'unequip') {
        $equipment_id = (int)($_POST['equipment_id'] ?? 0);
        
        try {
            // 装備情報を取得
            $st = $pdo->prepare("SELECT slot FROM user_equipment WHERE id = ? AND user_id = ?");
            $st->execute([$equipment_id, $me['id']]);
            $equipment = $st->fetch();
            
            $st = $pdo->prepare("UPDATE user_equipment SET is_equipped = 0 WHERE id = ? AND user_id = ?");
            $st->execute([$equipment_id, $me['id']]);
            
            echo json_encode([
                'ok' => true, 
                'message' => '装備を外しました',
                'equipment_id' => $equipment_id,
                'slot' => $equipment ? $equipment['slot'] : null
            ]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'upgrade') {
        $equipment_id = (int)($_POST['equipment_id'] ?? 0);
        
        if ($equipment_id <= 0) {
            echo json_encode(['ok' => false, 'error' => '不正な装備IDです']);
            exit;
        }
        
        $pdo->beginTransaction();
        try {
            // 装備の所有確認と現在の状態を取得
            $st = $pdo->prepare("SELECT * FROM user_equipment WHERE id = ? AND user_id = ? FOR UPDATE");
            $st->execute([$equipment_id, $me['id']]);
            $equipment = $st->fetch();
            
            if (!$equipment) {
                throw new Exception('装備が見つかりません');
            }
            
            $current_level = (int)($equipment['upgrade_level'] ?? 0);
            $rarity = $equipment['rarity'];
            $rarity_info = $RARITIES[$rarity];
            $token_col = $rarity_info['token_col'];
            
            // 許可されたカラム名のホワイトリスト
            $allowed_token_columns = ['normal_tokens', 'rare_tokens', 'unique_tokens', 'legend_tokens', 'epic_tokens', 'hero_tokens', 'mythic_tokens'];
            if (!in_array($token_col, $allowed_token_columns)) {
                throw new Exception('不正なトークンカラムです');
            }
            
            // 必要トークン数を計算（アップグレードレベル + 1）
            $required_tokens = $current_level + 1;
            
            // ユーザー情報を取得
            $st = $pdo->prepare("SELECT * FROM users WHERE id=? FOR UPDATE");
            $st->execute([$me['id']]);
            $user = $st->fetch();
            
            // トークンをチェック
            if (($user[$token_col] ?? 0) < $required_tokens) {
                throw new Exception($rarity_info['name'] . 'トークンが不足しています（必要: ' . $required_tokens . '個）');
            }
            
            // トークンを消費（switch文で安全にカラム指定）
            switch ($token_col) {
                case 'normal_tokens':
                    $st = $pdo->prepare("UPDATE users SET normal_tokens = normal_tokens - ? WHERE id = ?");
                    break;
                case 'rare_tokens':
                    $st = $pdo->prepare("UPDATE users SET rare_tokens = rare_tokens - ? WHERE id = ?");
                    break;
                case 'unique_tokens':
                    $st = $pdo->prepare("UPDATE users SET unique_tokens = unique_tokens - ? WHERE id = ?");
                    break;
                case 'legend_tokens':
                    $st = $pdo->prepare("UPDATE users SET legend_tokens = legend_tokens - ? WHERE id = ?");
                    break;
                case 'epic_tokens':
                    $st = $pdo->prepare("UPDATE users SET epic_tokens = epic_tokens - ? WHERE id = ?");
                    break;
                case 'hero_tokens':
                    $st = $pdo->prepare("UPDATE users SET hero_tokens = hero_tokens - ? WHERE id = ?");
                    break;
                case 'mythic_tokens':
                    $st = $pdo->prepare("UPDATE users SET mythic_tokens = mythic_tokens - ? WHERE id = ?");
                    break;
                default:
                    throw new Exception('不正なトークンカラムです');
            }
            $st->execute([$required_tokens, $me['id']]);
            
            // バフを上昇させる（設定した上昇率で上昇）
            $buffs = json_decode($equipment['buffs'], true) ?: [];
            $buff_increase = [];
            foreach ($buffs as $buff_key => $value) {
                $increase = round($value * $UPGRADE_BUFF_INCREASE_RATE, 2);
                $buff_increase[$buff_key] = $increase;
                $buffs[$buff_key] = round($value + $increase, 2);
            }
            
            $new_level = $current_level + 1;
            
            // 装備を更新
            $st = $pdo->prepare("UPDATE user_equipment SET buffs = ?, upgrade_level = ? WHERE id = ?");
            $st->execute([json_encode($buffs), $new_level, $equipment_id]);
            
            // 履歴を記録
            $st = $pdo->prepare("
                INSERT INTO equipment_upgrade_history (user_id, equipment_id, from_level, to_level, token_used, token_amount, buff_increase)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $st->execute([$me['id'], $equipment_id, $current_level, $new_level, $token_col, $required_tokens, json_encode($buff_increase)]);
            
            $pdo->commit();
            
            // 更新後のトークン残高を取得
            $st = $pdo->prepare("SELECT * FROM users WHERE id=?");
            $st->execute([$me['id']]);
            $updated_user = $st->fetch();
            
            // 装備情報を取得
            $st = $pdo->prepare("SELECT * FROM user_equipment WHERE id = ?");
            $st->execute([$equipment_id]);
            $updated_equipment = $st->fetch();
            
            echo json_encode([
                'ok' => true,
                'message' => '装備をアップグレードしました！（+' . $new_level . '）',
                'new_level' => $new_level,
                'new_buffs' => $buffs,
                'buff_increase' => $buff_increase,
                'equipment' => [
                    'id' => $updated_equipment['id'],
                    'name' => $updated_equipment['name'],
                    'rarity' => $rarity,
                    'upgrade_level' => $new_level
                ],
                'balance' => [
                    'coins' => $updated_user['coins'],
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
    
    if ($action === 'sell') {
        $equipment_id = (int)($_POST['equipment_id'] ?? 0);
        
        $pdo->beginTransaction();
        try {
            // 装備の所有確認（装備中でないことも確認）
            $st = $pdo->prepare("SELECT * FROM user_equipment WHERE id = ? AND user_id = ?");
            $st->execute([$equipment_id, $me['id']]);
            $equipment = $st->fetch();
            
            if (!$equipment) {
                throw new Exception('装備が見つかりません');
            }
            
            if ($equipment['is_equipped']) {
                throw new Exception('装備中の装備は売却できません。先に外してください。');
            }
            
            // レアリティとバフ値に基づいて売却価格を計算
            $rarity = $equipment['rarity'];
            $buffs = json_decode($equipment['buffs'], true) ?: [];
            $upgrade_level = (int)($equipment['upgrade_level'] ?? 0);
            
            // 基本売却価格（レアリティ別）
            $base_prices = [
                'normal' => ['coins' => 50, 'crystals' => 0],
                'rare' => ['coins' => 200, 'crystals' => 1],
                'unique' => ['coins' => 500, 'crystals' => 3],
                'legend' => ['coins' => 1500, 'crystals' => 10],
                'epic' => ['coins' => 4000, 'crystals' => 25],
                'hero' => ['coins' => 10000, 'crystals' => 60],
                'mythic' => ['coins' => 25000, 'crystals' => 150]
            ];
            
            $base = $base_prices[$rarity] ?? ['coins' => 50, 'crystals' => 0];
            
            // バフ値による価格ボーナス（各バフ値の合計に応じて価格上昇）
            $total_buff_value = 0;
            foreach ($buffs as $buff_key => $value) {
                $total_buff_value += $value;
            }
            
            // バフボーナス（バフ合計値の10%をコイン、5%をクリスタルに加算）
            $buff_bonus_coins = (int)floor($total_buff_value * 10);
            $buff_bonus_crystals = (int)floor($total_buff_value * 0.5);
            
            // アップグレードレベルによるボーナス（レベルごとに基本価格の20%上昇）
            $upgrade_multiplier = 1 + ($upgrade_level * 0.2);
            
            // 最終売却価格
            $sell_coins = (int)floor(($base['coins'] + $buff_bonus_coins) * $upgrade_multiplier);
            $sell_crystals = (int)floor(($base['crystals'] + $buff_bonus_crystals) * $upgrade_multiplier);
            
            // ユーザーに通貨を付与
            $st = $pdo->prepare("UPDATE users SET coins = coins + ?, crystals = crystals + ? WHERE id = ?");
            $st->execute([$sell_coins, $sell_crystals, $me['id']]);
            
            // 売却履歴を記録
            $st = $pdo->prepare("
                INSERT INTO equipment_sell_history (user_id, equipment_name, equipment_rarity, equipment_buffs, upgrade_level, sell_coins, sell_crystals)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $st->execute([$me['id'], $equipment['name'], $rarity, $equipment['buffs'], $upgrade_level, $sell_coins, $sell_crystals]);
            
            // 装備を削除
            $st = $pdo->prepare("DELETE FROM user_equipment WHERE id = ?");
            $st->execute([$equipment_id]);
            
            $pdo->commit();
            
            // 更新後のユーザー情報を取得
            $st = $pdo->prepare("SELECT coins, crystals FROM users WHERE id = ?");
            $st->execute([$me['id']]);
            $updated_user = $st->fetch();
            
            echo json_encode([
                'ok' => true,
                'message' => "「{$equipment['name']}」を売却しました！",
                'sell_coins' => $sell_coins,
                'sell_crystals' => $sell_crystals,
                'balance' => [
                    'coins' => $updated_user['coins'],
                    'crystals' => $updated_user['crystals']
                ]
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'get_sell_price') {
        $equipment_id = (int)($_POST['equipment_id'] ?? 0);
        
        try {
            $st = $pdo->prepare("SELECT * FROM user_equipment WHERE id = ? AND user_id = ?");
            $st->execute([$equipment_id, $me['id']]);
            $equipment = $st->fetch();
            
            if (!$equipment) {
                throw new Exception('装備が見つかりません');
            }
            
            $rarity = $equipment['rarity'];
            $buffs = json_decode($equipment['buffs'], true) ?: [];
            $upgrade_level = (int)($equipment['upgrade_level'] ?? 0);
            
            $base_prices = [
                'normal' => ['coins' => 50, 'crystals' => 0],
                'rare' => ['coins' => 200, 'crystals' => 1],
                'unique' => ['coins' => 500, 'crystals' => 3],
                'legend' => ['coins' => 1500, 'crystals' => 10],
                'epic' => ['coins' => 4000, 'crystals' => 25],
                'hero' => ['coins' => 10000, 'crystals' => 60],
                'mythic' => ['coins' => 25000, 'crystals' => 150]
            ];
            
            $base = $base_prices[$rarity] ?? ['coins' => 50, 'crystals' => 0];
            
            $total_buff_value = 0;
            foreach ($buffs as $buff_key => $value) {
                $total_buff_value += $value;
            }
            
            $buff_bonus_coins = (int)floor($total_buff_value * 10);
            $buff_bonus_crystals = (int)floor($total_buff_value * 0.5);
            $upgrade_multiplier = 1 + ($upgrade_level * 0.2);
            
            $sell_coins = (int)floor(($base['coins'] + $buff_bonus_coins) * $upgrade_multiplier);
            $sell_crystals = (int)floor(($base['crystals'] + $buff_bonus_crystals) * $upgrade_multiplier);
            
            echo json_encode([
                'ok' => true,
                'equipment_name' => $equipment['name'],
                'is_equipped' => (bool)$equipment['is_equipped'],
                'sell_coins' => $sell_coins,
                'sell_crystals' => $sell_crystals
            ]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

// ユーザーの装備一覧を取得
$st = $pdo->prepare("SELECT * FROM user_equipment WHERE user_id = ? ORDER BY is_equipped DESC, rarity DESC, created_at DESC");
$st->execute([$me['id']]);
$equipments = $st->fetchAll();

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
<title>装備システム - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
.equipment-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.equipment-header {
    background: linear-gradient(135deg, #6b5b95 0%, #8b4b8b 100%);
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 8px 16px rgba(107, 91, 149, 0.3);
}

.equipment-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
}

.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    background: #2d2d44;
    color: #888;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s;
}

.tab-btn.active {
    background: linear-gradient(135deg, #6b5b95 0%, #8b4b8b 100%);
    color: white;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* 作成セクション */
.craft-section {
    background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%);
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 20px;
}

.craft-section h3 {
    margin: 0 0 20px 0;
    font-size: 20px;
    color: #fff;
}

.slot-selector, .rarity-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.slot-btn, .rarity-btn {
    padding: 12px 20px;
    border: 2px solid #444;
    border-radius: 10px;
    background: #2d2d44;
    color: #fff;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.slot-btn.selected, .rarity-btn.selected {
    border-color: #6b5b95;
    background: rgba(107, 91, 149, 0.3);
}

.rarity-btn .icon {
    font-size: 20px;
}

.craft-info {
    background: rgba(0,0,0,0.2);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}

.craft-info p {
    margin: 8px 0;
    color: #aaa;
}

.craft-info .warning {
    color: #ff6b6b;
}

.craft-btn {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #6b5b95 0%, #8b4b8b 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.craft-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(107, 91, 149, 0.4);
}

.craft-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* 装備一覧 */
.equipment-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
}

.equipment-card {
    background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%);
    border-radius: 12px;
    padding: 20px;
    border: 2px solid #333;
    transition: all 0.3s;
}

.equipment-card.equipped {
    border-color: #6b5b95;
    box-shadow: 0 0 15px rgba(107, 91, 149, 0.4);
}

.equipment-card.rarity-normal { border-left: 4px solid #808080; }
.equipment-card.rarity-rare { border-left: 4px solid #00cc00; }
.equipment-card.rarity-unique { border-left: 4px solid #0080ff; }
.equipment-card.rarity-legend { border-left: 4px solid #ffcc00; }
.equipment-card.rarity-epic { border-left: 4px solid #cc00ff; }
.equipment-card.rarity-hero { border-left: 4px solid #ff0000; }
.equipment-card.rarity-mythic { 
    border-left: 4px solid transparent;
    border-image: linear-gradient(180deg, red, orange, yellow, green, blue, indigo, violet) 1;
}

.equipment-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.equipment-name {
    font-size: 18px;
    font-weight: bold;
    color: #fff;
}

.equipment-rarity {
    font-size: 14px;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: bold;
}

.equipment-buffs {
    margin-bottom: 15px;
}

.buff-item {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    font-size: 14px;
}

.buff-item:last-child {
    border-bottom: none;
}

.buff-name {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #aaa;
}

.buff-value {
    color: #00ff88;
    font-weight: bold;
}

.equipment-actions {
    display: flex;
    gap: 10px;
}

.equip-btn, .unequip-btn {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.equip-btn {
    background: linear-gradient(135deg, #6b5b95 0%, #8b4b8b 100%);
    color: white;
}

.unequip-btn {
    background: #444;
    color: #fff;
}

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 20px;
    background: rgba(255,255,255,0.1);
    color: #6b5b95;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
}

.back-link:hover {
    background: #6b5b95;
    color: white;
}

.token-display {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    background: rgba(0,0,0,0.2);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.token-item {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
    font-size: 14px;
}

.no-equipment {
    text-align: center;
    padding: 40px;
    color: #666;
}

.upgrade-info {
    margin-bottom: 10px;
    padding: 8px;
    background: rgba(255, 215, 0, 0.1);
    border-radius: 6px;
    text-align: center;
}

.upgrade-cost {
    font-size: 12px;
    color: #ffcc00;
}

.upgrade-btn {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%);
    color: #333;
}

.upgrade-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
}

.sell-btn {
    padding: 8px 12px;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
    color: white;
    font-size: 12px;
}

.sell-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(229, 62, 62, 0.4);
}
</style>
</head>
<body>
<div class="equipment-container">
    <a href="./" class="back-link">← フィードに戻る</a>
    
    <div class="equipment-header">
        <h1>⚔️ 装備システム</h1>
        <p>トークンを使って装備を作成し、バフを獲得しよう！</p>
    </div>
    
    <div class="tabs">
        <button class="tab-btn active" data-tab="craft">🔨 装備作成</button>
        <button class="tab-btn" data-tab="inventory">📦 所持装備</button>
    </div>
    
    <!-- 装備作成タブ -->
    <div class="tab-content active" id="tab-craft">
        <div class="craft-section">
            <h3>トークン残高</h3>
            <div class="token-display">
                <div class="token-item"><span>🪙</span> <?= number_format($user['coins']) ?></div>
                <div class="token-item"><span>⚪</span> <?= $user['normal_tokens'] ?? 0 ?></div>
                <div class="token-item"><span>🟢</span> <?= $user['rare_tokens'] ?? 0 ?></div>
                <div class="token-item"><span>🔵</span> <?= $user['unique_tokens'] ?? 0 ?></div>
                <div class="token-item"><span>🟡</span> <?= $user['legend_tokens'] ?? 0 ?></div>
                <div class="token-item"><span>🟣</span> <?= $user['epic_tokens'] ?? 0 ?></div>
                <div class="token-item"><span>🔴</span> <?= $user['hero_tokens'] ?? 0 ?></div>
                <div class="token-item"><span>🌈</span> <?= $user['mythic_tokens'] ?? 0 ?></div>
            </div>
        </div>
        
        <div class="craft-section">
            <h3>1. 部位を選択</h3>
            <div class="slot-selector">
                <?php foreach ($SLOTS as $key => $slot): ?>
                <button class="slot-btn" data-slot="<?= $key ?>">
                    <span><?= $slot['icon'] ?></span>
                    <span><?= $slot['name'] ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="craft-section">
            <h3>2. レアリティを選択</h3>
            <div class="rarity-selector">
                <?php foreach ($RARITIES as $key => $rarity): ?>
                <button class="rarity-btn" data-rarity="<?= $key ?>">
                    <span class="icon"><?= $rarity['icon'] ?></span>
                    <span><?= $rarity['name'] ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="craft-section">
            <h3>3. 作成情報</h3>
            <div class="craft-info" id="craftInfo">
                <p>部位とレアリティを選択してください</p>
            </div>
            <button class="craft-btn" id="craftBtn" disabled>装備を作成する</button>
        </div>
    </div>
    
    <!-- 所持装備タブ -->
    <div class="tab-content" id="tab-inventory">
        <?php if (empty($equipments)): ?>
        <div class="no-equipment">
            <p>装備がありません。<br>「装備作成」タブから作成しましょう！</p>
        </div>
        <?php else: ?>
        <div class="equipment-grid">
            <?php foreach ($equipments as $eq): 
                $buffs = json_decode($eq['buffs'], true) ?: [];
                $rarity_info = $RARITIES[$eq['rarity']];
                $upgrade_level = (int)($eq['upgrade_level'] ?? 0);
                $upgrade_display = $upgrade_level > 0 ? ' +' . $upgrade_level : '';
                $required_tokens = $upgrade_level + 1;
            ?>
            <div class="equipment-card <?= $eq['is_equipped'] ? 'equipped' : '' ?> rarity-<?= $eq['rarity'] ?>">
                <div class="equipment-card-header">
                    <span class="equipment-name"><?= htmlspecialchars($eq['name']) ?><?= $upgrade_display ?></span>
                    <span class="equipment-rarity" style="background: <?= $rarity_info['color'] === 'rainbow' ? 'linear-gradient(90deg, red, orange, yellow, green, blue, violet)' : $rarity_info['color'] ?>;">
                        <?= $rarity_info['name'] ?>
                    </span>
                </div>
                <div class="equipment-buffs">
                    <?php foreach ($buffs as $buff_key => $value): 
                        $buff_info = $BUFF_TYPES[$buff_key] ?? ['name' => $buff_key, 'icon' => '❓', 'unit' => ''];
                    ?>
                    <div class="buff-item">
                        <span class="buff-name">
                            <span><?= $buff_info['icon'] ?></span>
                            <?= $buff_info['name'] ?>
                        </span>
                        <span class="buff-value">+<?= $value ?><?= $buff_info['unit'] ?? '' ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="upgrade-info">
                    <span class="upgrade-cost"><?= $rarity_info['icon'] ?> ×<?= $required_tokens ?> で強化</span>
                </div>
                <div class="equipment-actions">
                    <?php if ($eq['is_equipped']): ?>
                    <button class="unequip-btn" data-id="<?= $eq['id'] ?>">外す</button>
                    <?php else: ?>
                    <button class="equip-btn" data-id="<?= $eq['id'] ?>">装備する</button>
                    <button class="sell-btn" data-id="<?= $eq['id'] ?>" data-name="<?= htmlspecialchars($eq['name']) ?>">💰 売却</button>
                    <?php endif; ?>
                    <button class="upgrade-btn" data-id="<?= $eq['id'] ?>" data-rarity="<?= $eq['rarity'] ?>" data-level="<?= $upgrade_level ?>" data-name="<?= htmlspecialchars($eq['name']) ?>">⬆️ 強化</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
const RARITIES = <?= json_encode($RARITIES) ?>;
const CRAFT_COST = <?= $CRAFT_COST_COINS ?>;
const UPGRADE_BUFF_INCREASE_RATE = <?= $UPGRADE_BUFF_INCREASE_RATE * 100 ?>; // パーセント表示用

let selectedSlot = null;
let selectedRarity = null;

// タブ切り替え
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
});

// 部位選択
document.querySelectorAll('.slot-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        selectedSlot = btn.dataset.slot;
        updateCraftInfo();
    });
});

// レアリティ選択
document.querySelectorAll('.rarity-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.rarity-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        selectedRarity = btn.dataset.rarity;
        updateCraftInfo();
    });
});

function updateCraftInfo() {
    const info = document.getElementById('craftInfo');
    const craftBtn = document.getElementById('craftBtn');
    
    if (!selectedSlot || !selectedRarity) {
        info.innerHTML = '<p>部位とレアリティを選択してください</p>';
        craftBtn.disabled = true;
        return;
    }
    
    const rarity = RARITIES[selectedRarity];
    info.innerHTML = `
        <p>📦 必要素材: ${rarity.icon} ${rarity.name}トークン ×1</p>
        <p>🪙 必要コイン: ${CRAFT_COST.toLocaleString()}</p>
        <p>✨ バフ数: ${rarity.buff_count}個</p>
        <p class="warning">⚠️ 失敗率: ${rarity.fail_rate}% ${rarity.fail_rate > 0 ? '（失敗すると素材は消費されます）' : ''}</p>
    `;
    craftBtn.disabled = false;
}

// トークン残高を更新
function updateTokenBalance(balance) {
    if (!balance) return;
    
    const tokenDisplay = document.querySelector('.token-display');
    if (tokenDisplay) {
        const items = tokenDisplay.querySelectorAll('.token-item');
        items[0].querySelector('span:last-child') && (items[0].innerHTML = `<span>🪙</span> ${balance.coins.toLocaleString()}`);
        items[1] && (items[1].innerHTML = `<span>⚪</span> ${balance.normal_tokens}`);
        items[2] && (items[2].innerHTML = `<span>🟢</span> ${balance.rare_tokens}`);
        items[3] && (items[3].innerHTML = `<span>🔵</span> ${balance.unique_tokens}`);
        items[4] && (items[4].innerHTML = `<span>🟡</span> ${balance.legend_tokens}`);
        items[5] && (items[5].innerHTML = `<span>🟣</span> ${balance.epic_tokens}`);
        items[6] && (items[6].innerHTML = `<span>🔴</span> ${balance.hero_tokens}`);
        items[7] && (items[7].innerHTML = `<span>🌈</span> ${balance.mythic_tokens}`);
    }
}

// 装備カードをDOMに追加
function addEquipmentCard(equipment) {
    const grid = document.querySelector('.equipment-grid');
    if (!grid) return;
    
    const rarityInfo = RARITIES[equipment.rarity];
    const buffHtml = Object.entries(equipment.buffs).map(([key, value]) => {
        const buffInfo = {
            'attack': { name: '攻撃力', icon: '⚔️' },
            'armor': { name: 'アーマー', icon: '🛡️' },
            'health': { name: '体力', icon: '❤️' },
            'coin_drop': { name: 'コインドロップ', icon: '🪙', unit: '%' },
            'crystal_drop': { name: 'クリスタルドロップ', icon: '💎', unit: '%' },
            'token_normal_drop': { name: 'ノーマルトークンドロップ', icon: '⚪', unit: '%' },
            'token_rare_drop': { name: 'レアトークンドロップ', icon: '🟢', unit: '%' }
        }[key] || { name: key, icon: '❓' };
        return `<div class="buff-item"><span class="buff-name"><span>${buffInfo.icon}</span>${buffInfo.name}</span><span class="buff-value">+${value}${buffInfo.unit || ''}</span></div>`;
    }).join('');
    
    const card = document.createElement('div');
    card.className = `equipment-card rarity-${equipment.rarity}`;
    card.innerHTML = `
        <div class="equipment-card-header">
            <span class="equipment-name">${equipment.name}</span>
            <span class="equipment-rarity" style="background: ${rarityInfo.color === 'rainbow' ? 'linear-gradient(90deg, red, orange, yellow, green, blue, violet)' : rarityInfo.color};">${rarityInfo.name}</span>
        </div>
        <div class="equipment-buffs">${buffHtml}</div>
        <div class="upgrade-info"><span class="upgrade-cost">${rarityInfo.icon} ×1 で強化</span></div>
        <div class="equipment-actions">
            <button class="equip-btn" data-id="${equipment.id}">装備する</button>
            <button class="upgrade-btn" data-id="${equipment.id}" data-rarity="${equipment.rarity}" data-level="0" data-name="${equipment.name}">⬆️ 強化</button>
        </div>
    `;
    
    // 新しいカードにイベントをバインド
    grid.insertBefore(card, grid.firstChild);
    
    // イベントリスナーを追加
    card.querySelector('.equip-btn').addEventListener('click', handleEquipClick);
    card.querySelector('.upgrade-btn').addEventListener('click', handleUpgradeClick);
}

// 装備作成
document.getElementById('craftBtn').addEventListener('click', async () => {
    if (!selectedSlot || !selectedRarity) return;
    
    const rarity = RARITIES[selectedRarity];
    if (!confirm(`${rarity.name}の装備を作成しますか？\n\n必要: ${rarity.name}トークン×1 + ${CRAFT_COST.toLocaleString()}コイン\n失敗率: ${rarity.fail_rate}%`)) {
        return;
    }
    
    const btn = document.getElementById('craftBtn');
    btn.disabled = true;
    btn.textContent = '作成中...';
    
    try {
        const formData = new FormData();
        formData.append('action', 'craft');
        formData.append('slot', selectedSlot);
        formData.append('rarity', selectedRarity);
        
        const res = await fetch('', {method: 'POST', body: formData});
        const data = await res.json();
        
        if (data.ok) {
            // トークン残高を更新
            updateTokenBalance(data.balance);
            
            if (data.success) {
                alert(`✅ ${data.message}\n\n作成された装備: ${data.equipment.name}`);
                // 新しい装備をDOMに追加
                addEquipmentCard(data.equipment);
            } else {
                alert(`❌ ${data.message}`);
            }
        } else {
            alert('❌ ' + data.error);
        }
    } catch (e) {
        alert('❌ 通信エラーが発生しました');
    }
    
    btn.disabled = false;
    btn.textContent = '装備を作成する';
});

// 装備/外すハンドラー
async function handleEquipClick(e) {
    const btn = e.target;
    const id = btn.dataset.id;
    const action = btn.classList.contains('equip-btn') ? 'equip' : 'unequip';
    
    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = action === 'equip' ? '装備中...' : '外し中...';
    
    const formData = new FormData();
    formData.append('action', action);
    formData.append('equipment_id', id);
    
    try {
        const res = await fetch('', {method: 'POST', body: formData});
        const data = await res.json();
        
        if (data.ok) {
            if (action === 'equip') {
                // 同じスロットの他の装備の「装備中」状態を解除（DOMを更新）
                if (data.previously_equipped_id) {
                    const prevButton = document.querySelector(`.equip-btn[data-id="${data.previously_equipped_id}"], .unequip-btn[data-id="${data.previously_equipped_id}"]`);
                    if (prevButton) {
                        const prevCardContainer = prevButton.closest('.equipment-card');
                        if (prevCardContainer) {
                            prevCardContainer.classList.remove('equipped');
                            // ボタンを「装備する」に変更
                            prevButton.className = 'equip-btn';
                            prevButton.textContent = '装備する';
                        }
                    }
                }
                
                // 現在のカードを「装備中」に変更
                const card = btn.closest('.equipment-card');
                if (card) {
                    card.classList.add('equipped');
                    btn.className = 'unequip-btn';
                    btn.textContent = '外す';
                }
            } else {
                // 外す処理
                const card = btn.closest('.equipment-card');
                if (card) {
                    card.classList.remove('equipped');
                    btn.className = 'equip-btn';
                    btn.textContent = '装備する';
                }
            }
        } else {
            alert('❌ ' + data.error);
            btn.textContent = originalText;
        }
    } catch (e) {
        alert('❌ 通信エラーが発生しました');
        btn.textContent = originalText;
    }
    
    btn.disabled = false;
}

// 装備/外す
document.querySelectorAll('.equip-btn, .unequip-btn').forEach(btn => {
    btn.addEventListener('click', handleEquipClick);
});

// アップグレードハンドラー
async function handleUpgradeClick(e) {
    const btn = e.target;
    const id = btn.dataset.id;
    const rarity = btn.dataset.rarity;
    const level = parseInt(btn.dataset.level) || 0;
    const name = btn.dataset.name;
    const requiredTokens = level + 1;
    const rarityInfo = RARITIES[rarity];
    
    if (!confirm(`「${name}${level > 0 ? ' +' + level : ''}」をアップグレードしますか？\n\n必要: ${rarityInfo.icon} ${rarityInfo.name}トークン ×${requiredTokens}\n効果: 全バフが${UPGRADE_BUFF_INCREASE_RATE}%上昇`)) {
        return;
    }
    
    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = '強化中...';
    
    const formData = new FormData();
    formData.append('action', 'upgrade');
    formData.append('equipment_id', id);
    
    try {
        const res = await fetch('', {method: 'POST', body: formData});
        const data = await res.json();
        
        if (data.ok) {
            // トークン残高を更新
            updateTokenBalance(data.balance);
            
            // カード内の情報を更新
            const card = btn.closest('.equipment-card');
            if (card) {
                // 装備名のレベル表示を更新
                const nameEl = card.querySelector('.equipment-name');
                if (nameEl) {
                    nameEl.textContent = `${name} +${data.new_level}`;
                }
                
                // バフ値を更新
                const buffItems = card.querySelectorAll('.buff-item');
                Object.entries(data.new_buffs).forEach(([key, value], index) => {
                    if (buffItems[index]) {
                        const valueEl = buffItems[index].querySelector('.buff-value');
                        if (valueEl) {
                            const unit = ['coin_drop', 'crystal_drop', 'token_normal_drop', 'token_rare_drop'].includes(key) ? '%' : '';
                            valueEl.textContent = `+${value}${unit}`;
                        }
                    }
                });
                
                // ボタンのデータ属性を更新
                btn.dataset.level = data.new_level;
                
                // 強化コストを更新
                const costEl = card.querySelector('.upgrade-cost');
                if (costEl) {
                    costEl.textContent = `${rarityInfo.icon} ×${data.new_level + 1} で強化`;
                }
            }
            
            alert(`✅ ${data.message}`);
        } else {
            alert('❌ ' + data.error);
        }
    } catch (e) {
        alert('❌ 通信エラーが発生しました');
    }
    
    btn.disabled = false;
    btn.textContent = originalText;
}

// アップグレード
document.querySelectorAll('.upgrade-btn').forEach(btn => {
    btn.addEventListener('click', handleUpgradeClick);
});

// 売却ハンドラー
async function handleSellClick(e) {
    const btn = e.target;
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    
    // まず売却価格を取得
    const priceFormData = new FormData();
    priceFormData.append('action', 'get_sell_price');
    priceFormData.append('equipment_id', id);
    
    try {
        const priceRes = await fetch('', {method: 'POST', body: priceFormData});
        const priceData = await priceRes.json();
        
        if (!priceData.ok) {
            alert('❌ ' + priceData.error);
            return;
        }
        
        if (priceData.is_equipped) {
            alert('⚠️ 装備中の装備は売却できません。先に外してください。');
            return;
        }
        
        if (!confirm(`「${name}」を売却しますか？\n\n売却価格:\n🪙 ${priceData.sell_coins.toLocaleString()} コイン\n💎 ${priceData.sell_crystals.toLocaleString()} クリスタル`)) {
            return;
        }
        
        btn.disabled = true;
        const originalText = btn.textContent;
        btn.textContent = '売却中...';
        
        const formData = new FormData();
        formData.append('action', 'sell');
        formData.append('equipment_id', id);
        
        const res = await fetch('', {method: 'POST', body: formData});
        const data = await res.json();
        
        if (data.ok) {
            // トークン表示を更新
            updateTokenBalance(data.balance);
            
            // カードを削除
            const card = btn.closest('.equipment-card');
            if (card) {
                card.remove();
            }
            
            alert(`✅ ${data.message}\n\n獲得:\n🪙 ${data.sell_coins.toLocaleString()} コイン\n💎 ${data.sell_crystals.toLocaleString()} クリスタル`);
        } else {
            alert('❌ ' + data.error);
            btn.disabled = false;
            btn.textContent = originalText;
        }
    } catch (e) {
        alert('❌ 通信エラーが発生しました');
    }
}

// 売却
document.querySelectorAll('.sell-btn').forEach(btn => {
    btn.addEventListener('click', handleSellClick);
});
</script>
</body>
</html>
