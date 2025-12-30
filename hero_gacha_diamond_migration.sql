-- ===============================================
-- hero_gacha_diamond_migration.sql
-- ダイヤモンドガチャ対応のための移行スクリプト
-- ===============================================

-- hero_gacha_historyテーブルにダイヤモンドコストカラムを追加
ALTER TABLE hero_gacha_history
ADD COLUMN IF NOT EXISTS cost_diamonds INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '消費ダイヤモンド' AFTER cost_crystals;

-- gacha_typeにdiamond系を追加（ENUMの場合はALTERで追加）
ALTER TABLE hero_gacha_history
MODIFY COLUMN gacha_type ENUM('normal', 'crystal', 'diamond', 'normal_10', 'crystal_10', 'diamond_10') NOT NULL COMMENT 'ガチャ種類';
