-- Community posts media enhancement
-- Add support for multiple images, videos, and audio files

ALTER TABLE community_posts 
ADD COLUMN media_paths JSON DEFAULT NULL COMMENT '複数メディアファイルのパス（最大4つ）' AFTER media_path,
ADD COLUMN media_type VARCHAR(20) DEFAULT NULL COMMENT 'メディアタイプ: image, video, audio' AFTER media_paths;

-- Add indexes for better performance
ALTER TABLE community_posts
ADD INDEX idx_media_type (media_type);

SELECT 'Community media enhancement completed' AS status;
