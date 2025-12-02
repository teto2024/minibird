-- ===============================================
-- A Dark Room ミニゲーム用テーブル定義
-- ===============================================
-- このファイルをインポートして、darkroom.phpの機能を有効化します
-- 実行コマンド例:
-- mysql -u root -p microblog < darkroom_schema.sql
-- ===============================================

USE microblog;

-- ゲームのセーブデータを保存するテーブル
CREATE TABLE IF NOT EXISTS darkroom_saves (
    user_id INT UNSIGNED NOT NULL,
    game_state JSON NOT NULL COMMENT 'ゲームの状態をJSON形式で保存',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='暗い部屋ゲームのセーブデータ';

-- ゲームの統計情報を記録するテーブル（オプション）
CREATE TABLE IF NOT EXISTS darkroom_stats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    stat_type VARCHAR(50) NOT NULL COMMENT '統計の種類: total_actions, max_population, etc.',
    stat_value INT NOT NULL DEFAULT 0,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_type (user_id, stat_type),
    INDEX idx_recorded (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='暗い部屋ゲームの統計情報';

-- 既存のreward_eventsテーブルにdarkroom関連のkind値を使用
-- 'darkroom_action' が追加される想定
-- 既存テーブルの変更は不要

-- インデックスの追加（パフォーマンス向上）
ALTER TABLE reward_events 
ADD INDEX IF NOT EXISTS idx_kind (kind);

-- サンプルデータの初期化（必要に応じてコメント解除）
-- INSERT INTO darkroom_stats (user_id, stat_type, stat_value) 
-- SELECT id, 'games_played', 0 FROM users WHERE id = 1;

-- テーブルが正しく作成されたか確認
SELECT 'darkroom_saves table created successfully' AS status;
SELECT 'darkroom_stats table created successfully' AS status;
