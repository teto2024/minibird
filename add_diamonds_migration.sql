-- ===============================================
-- MiniBird - ダイヤモンド通貨追加マイグレーション
-- ===============================================

USE microblog;

-- usersテーブルにdiamondsカラムを追加（既存の場合はスキップ）
ALTER TABLE users ADD COLUMN IF NOT EXISTS diamonds INT UNSIGNED NOT NULL DEFAULT 0 AFTER crystals;

-- インデックス追加（パフォーマンス向上）
-- ALTER TABLE users ADD INDEX IF NOT EXISTS idx_diamonds (diamonds);

SELECT 'Diamonds column added successfully' AS status;
