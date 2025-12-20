-- ===============================================
-- MiniBird 削除フラグ機能追加スキーマ
-- 投稿とコミュニティ投稿に削除フラグを追加
-- ===============================================

USE microblog;

-- 通常投稿テーブルに削除フラグを追加
ALTER TABLE posts 
ADD COLUMN IF NOT EXISTS is_deleted BOOLEAN NOT NULL DEFAULT FALSE COMMENT '削除フラグ' AFTER nsfw,
ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL COMMENT '削除日時' AFTER is_deleted,
ADD INDEX IF NOT EXISTS idx_is_deleted (is_deleted);

-- コミュニティ投稿テーブルに削除フラグを追加
ALTER TABLE community_posts 
ADD COLUMN IF NOT EXISTS is_deleted BOOLEAN NOT NULL DEFAULT FALSE COMMENT '削除フラグ' AFTER is_nsfw,
ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL COMMENT '削除日時' AFTER is_deleted,
ADD INDEX IF NOT EXISTS idx_is_deleted (is_deleted);

SELECT 'Delete flags added to posts and community_posts tables' AS status;
