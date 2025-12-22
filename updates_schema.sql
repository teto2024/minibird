-- アップデート情報テーブル
CREATE TABLE IF NOT EXISTS updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    version VARCHAR(50),
    category ENUM('feature', 'bugfix', 'improvement', 'announcement') DEFAULT 'feature',
    is_published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- インデックス追加
CREATE INDEX idx_updates_published ON updates(is_published, created_at DESC);
CREATE INDEX idx_updates_category ON updates(category);
