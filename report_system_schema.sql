-- ===============================================
-- 通報機能・異議申し立て機能用スキーマ
-- ===============================================

USE microblog;

-- 通報テーブル
CREATE TABLE IF NOT EXISTS reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL COMMENT '通報対象の投稿ID',
    reporter_id INT UNSIGNED NOT NULL COMMENT '通報者のユーザーID',
    reason VARCHAR(100) NOT NULL COMMENT '通報理由（カテゴリ）',
    details TEXT COMMENT '詳細説明（任意）',
    status ENUM('pending', 'reviewed', 'resolved', 'dismissed') NOT NULL DEFAULT 'pending' COMMENT '処理状況',
    admin_comment TEXT COMMENT '管理者のコメント',
    reviewed_by INT UNSIGNED NULL COMMENT '対応した管理者のID',
    reviewed_at DATETIME NULL COMMENT '対応日時',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_post (post_id),
    INDEX idx_reporter (reporter_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='投稿通報管理';

-- 異議申し立てテーブル
CREATE TABLE IF NOT EXISTS appeals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT '異議申し立てを行うユーザーID',
    reason TEXT NOT NULL COMMENT '異議申し立ての理由',
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' COMMENT '審査状況',
    admin_comment TEXT COMMENT '管理者のコメント',
    reviewed_by INT UNSIGNED NULL COMMENT '審査した管理者のID',
    reviewed_at DATETIME NULL COMMENT '審査日時',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ミュート異議申し立て管理';

SELECT 'Report and Appeal system tables created successfully' AS status;
