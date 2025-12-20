-- コミュニティ通知機能のためのSQLマイグレーション
-- notificationsテーブルにfrom_user_idカラムを追加（既存のactor_idの代わりに使用）

-- from_user_idカラムを追加（まだ存在しない場合）
ALTER TABLE notifications 
ADD COLUMN IF NOT EXISTS from_user_id INT NULL AFTER actor_id,
ADD CONSTRAINT fk_notifications_from_user 
  FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE;

-- 新しい通知タイプを追加
-- community_like: コミュニティ投稿へのいいね通知
-- community_reply: コミュニティ投稿へのリプライ通知
-- これらは既存のtype列（VARCHAR等）で管理するため、新しいカラムは不要

-- インデックスの追加（パフォーマンス向上）
CREATE INDEX IF NOT EXISTS idx_notifications_from_user_id ON notifications(from_user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_type ON notifications(type);
