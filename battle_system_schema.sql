-- ===============================================
-- Battle System Schema for MiniBird
-- バトルシステム準備実装用テーブル定義
-- ===============================================

-- ===============================================
-- ユーザーレベルシステム（usersテーブルへのカラム追加）
-- ===============================================
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS user_level INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'ユーザーレベル',
ADD COLUMN IF NOT EXISTS user_exp BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '現在の経験値';

-- ===============================================
-- ヒーローマスターテーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS heroes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hero_key VARCHAR(50) NOT NULL UNIQUE COMMENT 'ヒーロー識別子',
    name VARCHAR(100) NOT NULL COMMENT 'ヒーロー名',
    title VARCHAR(100) COMMENT 'ヒーロー肩書き',
    description TEXT COMMENT 'ヒーロー説明',
    icon VARCHAR(20) NOT NULL COMMENT 'アイコン絵文字',
    generation INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '世代',
    rarity ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') NOT NULL DEFAULT 'common' COMMENT 'レアリティ',
    unlock_shards INT UNSIGNED NOT NULL DEFAULT 10 COMMENT 'アンロックに必要な欠片数',
    star_up_shards JSON COMMENT '各星アップに必要な欠片数 [星1→2, 星2→3, ...]',
    battle_skill_name VARCHAR(100) COMMENT 'バトルスキル名',
    battle_skill_desc TEXT COMMENT 'バトルスキル説明',
    battle_skill_effect JSON COMMENT 'バトルスキル効果データ',
    passive_skill_name VARCHAR(100) COMMENT '内政スキル名',
    passive_skill_desc TEXT COMMENT '内政スキル説明',
    passive_skill_effect JSON COMMENT '内政スキル効果データ',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_generation (generation),
    INDEX idx_rarity (rarity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ヒーローマスター';

-- ===============================================
-- ユーザーヒーロー所持テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS user_heroes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    hero_id INT UNSIGNED NOT NULL,
    star_level INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '星レベル (0=未解放, 1-8=解放済)',
    shards INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所持欠片数',
    is_equipped BOOLEAN NOT NULL DEFAULT FALSE COMMENT '編成中かどうか',
    unlocked_at DATETIME NULL COMMENT 'アンロック日時',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hero_id) REFERENCES heroes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_hero (user_id, hero_id),
    INDEX idx_user (user_id),
    INDEX idx_equipped (user_id, is_equipped)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー所持ヒーロー';

-- ===============================================
-- ヒーローガチャ履歴テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS hero_gacha_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    gacha_type ENUM('normal', 'crystal') NOT NULL COMMENT 'ガチャ種類',
    reward_type ENUM('hero_shards', 'exp', 'coins', 'tokens', 'equipment') NOT NULL COMMENT '報酬種類',
    reward_data JSON NOT NULL COMMENT '報酬詳細データ',
    cost_coins INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '消費コイン',
    cost_crystals INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '消費クリスタル',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    INDEX idx_type (gacha_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ヒーローガチャ履歴';

-- ===============================================
-- 装備売却履歴テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS equipment_sell_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    equipment_name VARCHAR(100) NOT NULL COMMENT '売却した装備名',
    equipment_rarity VARCHAR(20) NOT NULL COMMENT 'レアリティ',
    equipment_buffs JSON NOT NULL COMMENT '売却時のバフデータ',
    upgrade_level INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'アップグレードレベル',
    sell_coins INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '獲得コイン',
    sell_crystals INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '獲得クリスタル',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='装備売却履歴';

-- ===============================================
-- 経験値履歴テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS exp_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    exp_amount INT NOT NULL COMMENT '獲得経験値',
    reason VARCHAR(50) NOT NULL COMMENT '理由（post, like, repost, focus, quest等）',
    exp_bonus_percent DECIMAL(5,2) DEFAULT 0 COMMENT '経験値ボーナス率',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    INDEX idx_reason (reason)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='経験値履歴';

-- ===============================================
-- 初期ヒーローデータ（第0世代10体）
-- ===============================================
INSERT IGNORE INTO heroes (hero_key, name, title, description, icon, generation, rarity, unlock_shards, star_up_shards, battle_skill_name, battle_skill_desc, battle_skill_effect, passive_skill_name, passive_skill_desc, passive_skill_effect) VALUES
('blade_master', 'ブレードマスター', '剣の達人', '伝説の剣技を操る達人。素早い斬撃で敵を圧倒する。', '⚔️', 0, 'common', 10, '[15, 25, 40, 60, 90, 130, 180]', '連続斬り', '敵に3連続攻撃を行い、合計150%のダメージを与える', '{"damage_multiplier": 1.5, "hit_count": 3}', 'コインハンター', 'コイン獲得量が5%増加', '{"coin_bonus": 5}'),
('shield_guardian', 'シールドガーディアン', '盾の守護者', '鉄壁の防御を誇る守護者。仲間を守り抜く。', '🛡️', 0, 'common', 10, '[15, 25, 40, 60, 90, 130, 180]', '守護の壁', '味方全体のアーマーを50%上昇させる', '{"armor_buff": 50, "duration": 3}', 'トークンシールド', 'トークン獲得時に5%のボーナス', '{"token_bonus": 5}'),
('flame_mage', 'フレイムメイジ', '炎の魔術師', '灼熱の炎を操る魔術師。広範囲に壊滅的なダメージを与える。', '🔥', 0, 'uncommon', 15, '[20, 35, 55, 85, 125, 175, 240]', '灼熱の嵐', '敵全体に120%の炎ダメージを与える', '{"damage_multiplier": 1.2, "aoe": true}', 'クリスタルフレイム', 'クリスタル獲得量が8%増加', '{"crystal_bonus": 8}'),
('frost_queen', 'フロストクイーン', '氷の女王', '永遠の冬を司る女王。敵を凍結させ行動不能にする。', '❄️', 0, 'uncommon', 15, '[20, 35, 55, 85, 125, 175, 240]', '絶対零度', '敵1体を2ターン凍結させ、行動不能にする', '{"freeze_duration": 2}', '氷結の祝福', '集中タスク報酬が10%増加', '{"focus_bonus": 10}'),
('thunder_god', 'サンダーゴッド', '雷神', '雷を司る神。一撃で敵を葬り去る破壊力を持つ。', '⚡', 0, 'rare', 25, '[30, 50, 80, 120, 175, 250, 350]', '神鳴り', '最も体力の少ない敵に200%のダメージを与える', '{"damage_multiplier": 2.0, "target": "lowest_hp"}', '雷光の加護', '経験値獲得量が15%増加', '{"exp_bonus": 15}'),
('nature_druid', 'ネイチャードルイド', '森の賢者', '自然の力を借りる賢者。回復と支援に長ける。', '🌿', 0, 'common', 10, '[15, 25, 40, 60, 90, 130, 180]', '大地の癒し', '味方全体の体力を30%回復する', '{"heal_percent": 30}', '自然の恵み', 'ダイヤモンド獲得確率が3%増加', '{"diamond_bonus": 3}'),
('shadow_assassin', 'シャドウアサシン', '影の暗殺者', '闇に潜む暗殺者。一撃必殺の技を持つ。', '🗡️', 0, 'rare', 25, '[30, 50, 80, 120, 175, 250, 350]', '暗殺', '50%の確率で敵を即死させる（ボス無効）', '{"instant_kill_chance": 50}', '闇の取引', 'ショップ価格が5%割引', '{"shop_discount": 5}'),
('holy_paladin', 'ホーリーパラディン', '聖なる騎士', '光の力を持つ騎士。攻守両面で活躍する。', '✨', 0, 'uncommon', 15, '[20, 35, 55, 85, 125, 175, 240]', '聖なる裁き', '敵1体に150%のダメージを与え、自分の体力を20%回復', '{"damage_multiplier": 1.5, "self_heal": 20}', '聖なる祝福', 'クエスト報酬が10%増加', '{"quest_bonus": 10}'),
('time_sage', 'タイムセージ', '時の賢者', '時間を操る賢者。戦況を有利に導く。', '⏰', 0, 'epic', 40, '[50, 80, 125, 190, 280, 400, 550]', '時間停止', '次のターン、敵の行動を全てスキップさせる', '{"skip_enemy_turn": true}', '時の加速', '全ての報酬獲得量が12%増加', '{"all_bonus": 12}'),
('chaos_lord', 'カオスロード', '混沌の支配者', '混沌の力を操る支配者。予測不能な力を持つ。', '🌀', 0, 'legendary', 60, '[80, 130, 200, 300, 440, 620, 850]', '混沌の渦', 'ランダムな効果を発動（ダメージ/回復/バフ/デバフ）', '{"random_effect": true, "power": 200}', '混沌の恩恵', 'ガチャでレア報酬確率が20%増加', '{"gacha_luck": 20}');

-- ===============================================
-- 集中タスククエスト用の追加クエスト
-- ===============================================
INSERT IGNORE INTO quests (quest_key, title, description, type, conditions, reward_coins, reward_crystals, reward_diamonds) VALUES
('daily_focus_15', '集中タスク15分以上', '1回の集中タスクを15分以上成功させる', 'daily', '{"action": "focus_min_15", "count": 1}', 200, 3, 0),
('weekly_focus_100', '集中タスク100分以上', '合計100分以上の集中タスクを成功させる', 'weekly', '{"action": "focus_total_100", "count": 100}', 1000, 20, 2);

-- テーブル作成完了メッセージ
SELECT 'Battle system schema created successfully' AS status;
