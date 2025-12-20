-- ===============================================
-- MiniBird フレーム機能拡張用スキーマ
-- 期間限定フレーム、ユーザー考案フレーム対応
-- ===============================================

USE microblog;

-- frames テーブルに期間限定とユーザー考案対応カラムを追加
ALTER TABLE frames 
ADD COLUMN IF NOT EXISTS sale_start_date DATETIME NULL COMMENT '販売開始日（期間限定フレーム用）',
ADD COLUMN IF NOT EXISTS sale_end_date DATETIME NULL COMMENT '販売終了日（期間限定フレーム用）',
ADD COLUMN IF NOT EXISTS is_limited BOOLEAN NOT NULL DEFAULT FALSE COMMENT '期間限定フレームかどうか',
ADD COLUMN IF NOT EXISTS designed_by_user_id INT UNSIGNED NULL COMMENT 'ユーザー考案フレームの場合、考案者ID',
ADD COLUMN IF NOT EXISTS is_user_designed BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'ユーザー考案フレームかどうか',
ADD CONSTRAINT fk_frames_designer FOREIGN KEY (designed_by_user_id) REFERENCES users(id) ON DELETE SET NULL;

-- ユーザー考案フレーム提出テーブル
CREATE TABLE IF NOT EXISTS user_designed_frames (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT '提案者のユーザーID',
    name VARCHAR(100) NOT NULL COMMENT 'フレーム名',
    css_token VARCHAR(100) NOT NULL COMMENT 'CSSトークン',
    preview_css TEXT COMMENT 'プレビュー用CSS',
    description TEXT COMMENT 'フレームの説明',
    proposed_price_coins INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '提案価格（コイン）',
    proposed_price_crystals INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '提案価格（クリスタル）',
    proposed_price_diamonds INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '提案価格（ダイヤ）',
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' COMMENT '審査状況',
    admin_comment TEXT COMMENT '管理者からのコメント',
    approved_frame_id INT UNSIGNED NULL COMMENT '承認された場合のframe_id',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL COMMENT '審査日時',
    reviewed_by INT UNSIGNED NULL COMMENT '審査した管理者ID',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_frame_id) REFERENCES frames(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー考案フレーム提出・審査管理';

SELECT 'Frames table enhanced for limited-time and user-designed frames' AS status;
