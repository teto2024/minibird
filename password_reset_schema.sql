-- パスワードリセット申請テーブル
CREATE TABLE IF NOT EXISTS password_reset_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    handle VARCHAR(16) NOT NULL,
    reason TEXT NOT NULL COMMENT '申請理由',
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_comment TEXT COMMENT '管理者コメント',
    new_password_hash VARCHAR(255) COMMENT '新しいパスワードのハッシュ（承認前に設定）',
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    reviewed_by INT UNSIGNED NULL COMMENT '審査した管理者ID',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_requested (requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='パスワードリセット申請';
