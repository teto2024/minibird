-- ===============================================
-- MiniBird 投稿機能拡張用スキーマ
-- 複数画像対応のための変更
-- ===============================================

USE microblog;

-- posts テーブルに複数画像対応カラムを追加
ALTER TABLE posts 
ADD COLUMN IF NOT EXISTS media_paths JSON NULL COMMENT '複数画像パス (最大4枚)' AFTER media_type;

-- 既存の media_path を持つレコードを media_paths に移行（必要に応じて手動実行）
-- UPDATE posts SET media_paths = JSON_ARRAY(media_path) WHERE media_path IS NOT NULL AND media_paths IS NULL;

SELECT 'Posts table enhanced for multiple images (up to 4)' AS status;
