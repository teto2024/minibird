-- ===============================================
-- MiniBird 機能追加・修正スキーマ 2024
-- 1. 不足している資源の追加確認
-- 2. 負傷兵治療の病床数制限の準備
-- 3. 同盟タブの大使館レベル制限用カラム追加
-- 4. 軍事建物の出撃上限ボーナス確認
-- 5. ヒーローシステムのバトル適用準備（第2バトルスキル追加）
-- ===============================================

USE microblog;

-- ===============================================
-- 1. 不足している資源の確認・追加（冪等性確保）
-- ===============================================
-- 全ての資源を確実に追加
INSERT IGNORE INTO civilization_resource_types (resource_key, name, icon, description, unlock_order, color) VALUES
-- 基本資源
('food', '食料', '🍖', '住民を養う基本資源', 0, '#8B4513'),
('wood', '木材', '🪵', '建設に必要な基本資源', 0, '#228B22'),
('stone', '石材', '🪨', '頑丈な建物に必要', 0, '#808080'),
-- 青銅器時代
('bronze', '青銅', '🔶', '道具と武器の素材', 1, '#CD7F32'),
('herbs', '薬草', '🌿', '医薬品の原料', 1, '#228B22'),
('bandages', '包帯', '🩹', '基本的な治療材料', 1, '#FFFFFF'),
('horses', '馬', '🐴', '騎兵と輸送に使用', 2, '#8B4513'),
('cloth', '布', '🧵', '服や帆に使用', 2, '#DDA0DD'),
-- 鉄器時代
('iron', '鉄', '⚙️', '強力な武器と建物に必要', 2, '#4A5568'),
('glass', 'ガラス', '🔮', '窓と科学機器に使用', 3, '#ADD8E6'),
('marble', '大理石', '🏛️', '高級建築に使用', 3, '#F5F5DC'),
-- 中世
('gold', '金', '💰', '貿易と高級品に使用', 3, '#FFD700'),
('sulfur', '硫黄', '🔶', '火薬と爆発物に使用', 3, '#FFFF00'),
('gems', '宝石', '💎', '装飾品と高級品に使用', 4, '#9400D3'),
('steel', '鋼鉄', '⚙️', '高品質な武器と防具に使用', 4, '#708090'),
-- ルネサンス
('knowledge', '知識', '📚', '研究と発展に必要', 4, '#4169E1'),
('coal', '石炭', '⬛', '産業と鍛冶に使用', 4, '#36454F'),
('medicine', '医薬品', '💊', '負傷兵の治療に使用', 4, '#FF69B4'),
('spices', '香辛料', '🌶️', '貿易と食品保存に使用', 4, '#FF4500'),
-- 産業革命
('gunpowder', '火薬', '💥', '火器と爆発物に使用', 5, '#2F4F4F'),
('gunpowder_res', '火薬資源', '💥', '火器の生産に必要', 5, '#FF4500'),
('oil', '石油', '🛢️', '産業と軍事に必要', 5, '#2F4F4F'),
('rubber', 'ゴム', '⚫', '近代的な装備に使用', 6, '#1C1C1C'),
-- 現代
('crystal', '文明クリスタル', '💎', '高度な技術に必要', 6, '#9932CC'),
('mana', 'マナ', '✨', '魔法の力の源', 7, '#4B0082'),
('electronics', '電子部品', '🔌', '現代技術に必要', 7, '#00BFFF'),
('titanium', 'チタン', '🔩', '軽量で強靭な金属', 7, '#C0C0C0'),
('uranium', 'ウラン', '☢️', '核技術に必要な資源', 8, '#32CD32'),
('diamond', '文明ダイヤモンド', '💠', '最高級の資源', 9, '#00CED1');

-- ===============================================
-- 2. 大使館の制限システム用カラム追加
-- ===============================================
-- 建物タイプに援助制限ボーナスを追加
ALTER TABLE civilization_building_types 
ADD COLUMN IF NOT EXISTS transfer_limit_bonus INT UNSIGNED NOT NULL DEFAULT 0 
COMMENT '1時間あたりの援助上限ボーナス（レベルごとに加算）';

-- 大使館に援助制限ボーナスを設定（資源1000/兵士50 per level）
UPDATE civilization_building_types 
SET transfer_limit_bonus = 1 
WHERE building_key = 'embassy';

-- ===============================================
-- 3. 同盟転送制限履歴テーブル（1時間ごとの制限追跡）
-- ===============================================
CREATE TABLE IF NOT EXISTS alliance_transfer_hourly_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    hour_window DATETIME NOT NULL COMMENT '1時間枠の開始時刻',
    resources_transferred DECIMAL(20,2) NOT NULL DEFAULT 0 COMMENT '転送した資源総量',
    troops_transferred INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '転送した兵士総数',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_hour (user_id, hour_window),
    INDEX idx_user (user_id),
    INDEX idx_hour (hour_window)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='同盟援助の1時間ごとの制限追跡';

-- ===============================================
-- 4. 既存の軍事建物に出撃上限ボーナス確認・設定
-- ===============================================
-- 兵舎: 10 per level
UPDATE civilization_building_types SET troop_deployment_bonus = 10 
WHERE building_key = 'barracks' AND (troop_deployment_bonus IS NULL OR troop_deployment_bonus = 0);

-- 要塞: 30 per level
UPDATE civilization_building_types SET troop_deployment_bonus = 30
WHERE building_key = 'fortress' AND (troop_deployment_bonus IS NULL OR troop_deployment_bonus = 0);

-- 城: 50 per level
UPDATE civilization_building_types SET troop_deployment_bonus = 50
WHERE building_key = 'castle' AND (troop_deployment_bonus IS NULL OR troop_deployment_bonus = 0);

-- 軍事基地: 200 per level
UPDATE civilization_building_types SET troop_deployment_bonus = 200
WHERE building_key = 'military_base' AND (troop_deployment_bonus IS NULL OR troop_deployment_bonus = 0);

-- 空軍基地: 150 per level
UPDATE civilization_building_types SET troop_deployment_bonus = 150
WHERE building_key = 'air_base' AND (troop_deployment_bonus IS NULL OR troop_deployment_bonus = 0);

-- ===============================================
-- 5. ヒーローシステムのバトル適用準備
-- ===============================================
-- ヒーローに第2バトルスキルを追加
ALTER TABLE heroes 
ADD COLUMN IF NOT EXISTS battle_skill_2_name VARCHAR(100) NULL COMMENT '第2バトルスキル名',
ADD COLUMN IF NOT EXISTS battle_skill_2_desc TEXT NULL COMMENT '第2バトルスキル説明',
ADD COLUMN IF NOT EXISTS battle_skill_2_effect JSON NULL COMMENT '第2バトルスキル効果データ';

-- ユーザーヒーローにバトル用スキル選択を追加
ALTER TABLE user_heroes
ADD COLUMN IF NOT EXISTS selected_battle_skill_1 INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '選択したバトルスキル1 (1 or 2)',
ADD COLUMN IF NOT EXISTS selected_battle_skill_2 INT UNSIGNED NULL COMMENT '選択したバトルスキル2 (1 or 2, NULL=未選択)';

-- 出撃時にヒーローを選択するためのテーブル
CREATE TABLE IF NOT EXISTS user_battle_hero_selection (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    battle_type ENUM('conquest', 'world_boss', 'wandering_monster', 'war', 'defense') NOT NULL COMMENT 'バトル種類',
    hero_id INT UNSIGNED NULL COMMENT '選択したヒーローID',
    skill_1_type INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'スキル1の種類 (1=第1バトルスキル, 2=第2バトルスキル)',
    skill_2_type INT UNSIGNED NULL COMMENT 'スキル2の種類 (1=第1バトルスキル, 2=第2バトルスキル)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hero_id) REFERENCES heroes(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_battle (user_id, battle_type),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='バトル別ヒーロー選択';

-- ===============================================
-- 6. 既存ヒーローに第2バトルスキルを追加
-- ===============================================
UPDATE heroes SET 
    battle_skill_2_name = '疾風の斬撃',
    battle_skill_2_desc = '素早い動きで敵に80%のダメージを与え、自分の速度を20%上昇させる',
    battle_skill_2_effect = '{"damage_multiplier": 0.8, "self_speed_buff": 20, "duration": 2}'
WHERE hero_key = 'blade_master';

UPDATE heroes SET 
    battle_skill_2_name = '挑発',
    battle_skill_2_desc = '敵の攻撃を自分に集中させ、2ターン味方への被ダメージを30%軽減',
    battle_skill_2_effect = '{"taunt": true, "team_damage_reduction": 30, "duration": 2}'
WHERE hero_key = 'shield_guardian';

UPDATE heroes SET 
    battle_skill_2_name = '火炎球',
    battle_skill_2_desc = '単体の敵に180%の炎ダメージを与え、2ターン燃焼状態にする',
    battle_skill_2_effect = '{"damage_multiplier": 1.8, "burn": true, "burn_damage": 10, "duration": 2}'
WHERE hero_key = 'flame_mage';

UPDATE heroes SET 
    battle_skill_2_name = '氷結の矢',
    battle_skill_2_desc = '敵1体に100%のダメージを与え、50%の確率で1ターン凍結させる',
    battle_skill_2_effect = '{"damage_multiplier": 1.0, "freeze_chance": 50, "freeze_duration": 1}'
WHERE hero_key = 'frost_queen';

UPDATE heroes SET 
    battle_skill_2_name = '雷鳴の一撃',
    battle_skill_2_desc = 'ランダムな敵3体に100%のダメージを与える',
    battle_skill_2_effect = '{"damage_multiplier": 1.0, "target_count": 3, "random_target": true}'
WHERE hero_key = 'thunder_god';

UPDATE heroes SET 
    battle_skill_2_name = '再生の風',
    battle_skill_2_desc = '最も体力が少ない味方の体力を50%回復し、毒と燃焼を解除する',
    battle_skill_2_effect = '{"heal_percent": 50, "target": "lowest_hp_ally", "cleanse": ["poison", "burn"]}'
WHERE hero_key = 'nature_druid';

UPDATE heroes SET 
    battle_skill_2_name = '致命の一撃',
    battle_skill_2_desc = '敵1体に250%のクリティカルダメージを与える',
    battle_skill_2_effect = '{"damage_multiplier": 2.5, "guaranteed_crit": true}'
WHERE hero_key = 'shadow_assassin';

UPDATE heroes SET 
    battle_skill_2_name = '聖なる盾',
    battle_skill_2_desc = '味方1体に2ターン無敵シールドを付与する',
    battle_skill_2_effect = '{"shield": true, "invincible": true, "duration": 2}'
WHERE hero_key = 'holy_paladin';

UPDATE heroes SET 
    battle_skill_2_name = '時の巻き戻し',
    battle_skill_2_desc = '味方全体の体力を1ターン前の状態に戻す',
    battle_skill_2_effect = '{"rewind_hp": true, "turns_back": 1}'
WHERE hero_key = 'time_sage';

UPDATE heroes SET 
    battle_skill_2_name = '破壊の渦',
    battle_skill_2_desc = '敵全体に150%のダメージを与え、バフを全て解除する',
    battle_skill_2_effect = '{"damage_multiplier": 1.5, "aoe": true, "dispel_buffs": true}'
WHERE hero_key = 'chaos_lord';

-- ===============================================
-- 7. 内政スキルの調整（環境に合わせた効果）
-- ===============================================
UPDATE heroes SET 
    passive_skill_name = '資源生産強化',
    passive_skill_desc = '全資源の生産量が5%増加',
    passive_skill_effect = '{"production_bonus": 5}'
WHERE hero_key = 'blade_master';

UPDATE heroes SET 
    passive_skill_name = '建設速度強化',
    passive_skill_desc = '建設時間が10%短縮',
    passive_skill_effect = '{"build_speed_bonus": 10}'
WHERE hero_key = 'shield_guardian';

UPDATE heroes SET 
    passive_skill_name = '研究効率強化',
    passive_skill_desc = '研究速度が10%増加',
    passive_skill_effect = '{"research_speed_bonus": 10}'
WHERE hero_key = 'flame_mage';

UPDATE heroes SET 
    passive_skill_name = '訓練速度強化',
    passive_skill_desc = '兵士の訓練時間が10%短縮',
    passive_skill_effect = '{"train_speed_bonus": 10}'
WHERE hero_key = 'frost_queen';

UPDATE heroes SET 
    passive_skill_name = '経験値ブースト',
    passive_skill_desc = '獲得経験値が15%増加',
    passive_skill_effect = '{"exp_bonus": 15}'
WHERE hero_key = 'thunder_god';

UPDATE heroes SET 
    passive_skill_name = '治療効率強化',
    passive_skill_desc = '負傷兵の治療時間が15%短縮',
    passive_skill_effect = '{"heal_speed_bonus": 15}'
WHERE hero_key = 'nature_druid';

UPDATE heroes SET 
    passive_skill_name = '略奪ボーナス',
    passive_skill_desc = '戦争勝利時の略奪量が10%増加',
    passive_skill_effect = '{"loot_bonus": 10}'
WHERE hero_key = 'shadow_assassin';

UPDATE heroes SET 
    passive_skill_name = '人口上限強化',
    passive_skill_desc = '最大人口が5%増加',
    passive_skill_effect = '{"population_bonus": 5}'
WHERE hero_key = 'holy_paladin';

UPDATE heroes SET 
    passive_skill_name = '時間短縮マスター',
    passive_skill_desc = '全ての時間消費が8%短縮',
    passive_skill_effect = '{"all_time_reduction": 8}'
WHERE hero_key = 'time_sage';

UPDATE heroes SET 
    passive_skill_name = '幸運の加護',
    passive_skill_desc = 'ガチャとドロップのレア率が15%増加',
    passive_skill_effect = '{"luck_bonus": 15}'
WHERE hero_key = 'chaos_lord';

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'MiniBird enhancements 2024 schema applied successfully' AS status;
