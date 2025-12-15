-- ===============================================
-- コミュニティに公開/非公開設定を追加
-- ===============================================

ALTER TABLE communities 
ADD COLUMN is_public TINYINT(1) DEFAULT 0 COMMENT '公開フラグ（0=非公開、1=公開）';

-- 既存のコミュニティはデフォルトで非公開
UPDATE communities SET is_public = 0 WHERE is_public IS NULL;
