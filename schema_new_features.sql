-- ===============================================
-- SQL Schema for new features
-- 英単語マスター、天使フレーム、英単語報酬バフ
-- ===============================================

-- ===============================================
-- 英単語マスター用テーブル
-- ===============================================

-- english_words テーブル（英単語データ）
CREATE TABLE IF NOT EXISTS english_words (
    id INT AUTO_INCREMENT PRIMARY KEY,
    word VARCHAR(100) NOT NULL,
    meaning VARCHAR(255) NOT NULL,
    INDEX idx_word (word)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='英単語マスター用英単語データ';

-- user_word_stats テーブル（ユーザーの単語学習統計）
CREATE TABLE IF NOT EXISTS user_word_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    word_id INT NOT NULL,
    correct_count INT DEFAULT 0,
    incorrect_count INT DEFAULT 0,
    last_attempt DATETIME,
    UNIQUE KEY unique_word_stat (user_id, word_id),
    INDEX idx_user_id (user_id),
    INDEX idx_word_id (word_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (word_id) REFERENCES english_words(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザーの英単語学習統計';

-- 英単語マスターの進捗テーブル
CREATE TABLE IF NOT EXISTS user_word_master_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    section_id INT NOT NULL,
    level VARCHAR(20) NOT NULL COMMENT 'selection or input',
    best_score DECIMAL(7,3) DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_progress (user_id, section_id, level),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='英単語マスター進捗';

-- ===============================================
-- 個人バフ用テーブル
-- ===============================================

-- user_buffs テーブル（個人バフ用）
CREATE TABLE IF NOT EXISTS user_buffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    level INT DEFAULT 1,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    INDEX idx_user_type (user_id, type),
    INDEX idx_end_time (end_time),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー個人バフ';

-- ===============================================
-- 天使フレームを追加
-- ===============================================
INSERT INTO frames (name, css_token, price_coins, price_crystals, price_diamonds, preview_css) 
VALUES ('天使', 'frame-angel', 35000, 80, 2, '')
ON DUPLICATE KEY UPDATE name = name;

-- ===============================================
-- サンプル英単語データ（テスト用）
-- ===============================================
-- 実際の運用ではより多くの単語をインポートしてください
INSERT IGNORE INTO english_words (id, word, meaning) VALUES
(1, 'apple', 'りんご'),
(2, 'book', '本'),
(3, 'cat', '猫'),
(4, 'dog', '犬'),
(5, 'elephant', '象'),
(6, 'flower', '花'),
(7, 'garden', '庭'),
(8, 'house', '家'),
(9, 'ice', '氷'),
(10, 'juice', 'ジュース'),
(11, 'key', '鍵'),
(12, 'lemon', 'レモン'),
(13, 'mountain', '山'),
(14, 'night', '夜'),
(15, 'ocean', '海'),
(16, 'pencil', '鉛筆'),
(17, 'queen', '女王'),
(18, 'river', '川'),
(19, 'sun', '太陽'),
(20, 'tree', '木'),
(21, 'umbrella', '傘'),
(22, 'violin', 'バイオリン'),
(23, 'water', '水'),
(24, 'xylophone', '木琴'),
(25, 'yellow', '黄色'),
(26, 'zebra', 'シマウマ'),
(27, 'airplane', '飛行機'),
(28, 'bridge', '橋'),
(29, 'cloud', '雲'),
(30, 'desk', '机'),
(31, 'earth', '地球'),
(32, 'forest', '森'),
(33, 'grass', '草'),
(34, 'hospital', '病院'),
(35, 'island', '島'),
(36, 'jungle', 'ジャングル'),
(37, 'kitchen', '台所'),
(38, 'library', '図書館'),
(39, 'mirror', '鏡'),
(40, 'notebook', 'ノート');

-- ===============================================
-- バージョン情報
-- ===============================================
-- このスキーマはMySQL 5.7以降に対応しています
-- MySQL 8.0では IF NOT EXISTS 構文が使用可能です
