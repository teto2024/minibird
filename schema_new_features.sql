-- ===============================================
-- SQL Schema for new features
-- 英単語マスター、天使フレーム、英単語報酬バフ
-- ===============================================

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
);

-- user_buffsテーブルにlevelカラムを追加
-- MySQL 5.7以下の場合は、カラムが存在しないことを確認してから実行してください
-- ALTER TABLE user_buffs ADD COLUMN level INT DEFAULT 1;
-- MySQL 8.0以上の場合:
-- ALTER TABLE user_buffs ADD COLUMN IF NOT EXISTS level INT DEFAULT 1;

-- 天使フレームを追加
INSERT INTO frames (name, css_token, price_coins, price_crystals, price_diamonds, preview_css) 
VALUES ('天使', 'frame-angel', 35000, 80, 2, '')
ON DUPLICATE KEY UPDATE name = name;

-- ===============================================
-- バージョン情報
-- ===============================================
-- このスキーマはMySQL 5.7以降に対応しています
-- MySQL 8.0では IF NOT EXISTS 構文が使用可能です

-- ===============================================
-- 以下は参考用（テーブルが存在しない場合のフルスキーマ）
-- ===============================================

-- user_word_stats テーブル（既存の英単語クイズで使用）
-- CREATE TABLE IF NOT EXISTS user_word_stats (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     user_id INT NOT NULL,
--     word_id INT NOT NULL,
--     correct_count INT DEFAULT 0,
--     incorrect_count INT DEFAULT 0,
--     last_attempt DATETIME,
--     UNIQUE KEY unique_word_stat (user_id, word_id),
--     INDEX idx_user_id (user_id),
--     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
-- );

-- english_words テーブル（既存）
-- CREATE TABLE IF NOT EXISTS english_words (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     word VARCHAR(100) NOT NULL,
--     meaning VARCHAR(255) NOT NULL
-- );

-- user_buffs テーブル（個人バフ用）
-- CREATE TABLE IF NOT EXISTS user_buffs (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     user_id INT NOT NULL,
--     type VARCHAR(50) NOT NULL,
--     level INT DEFAULT 1,
--     start_time DATETIME NOT NULL,
--     end_time DATETIME NOT NULL,
--     INDEX idx_user_type (user_id, type),
--     INDEX idx_end_time (end_time),
--     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
-- );
