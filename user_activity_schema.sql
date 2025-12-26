-- ===============================================
-- User Activity Schema for MiniBird
-- ユーザーオンライン状態管理用テーブル修正
-- ===============================================

-- ユーザーテーブルに最終操作時刻カラムを追加
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS last_activity DATETIME NULL DEFAULT NULL COMMENT '最終操作時刻'
AFTER updated_at;

-- インデックスを追加（オンラインユーザー検索用）
CREATE INDEX IF NOT EXISTS idx_users_last_activity ON users(last_activity);

-- テーブル作成完了メッセージ
SELECT 'User activity schema created successfully' AS status;
