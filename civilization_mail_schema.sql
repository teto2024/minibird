-- ===============================================
-- 文明育成ゲーム メールシステム スキーマ
-- データベース: 使用前に適切なデータベースを選択してください
-- ===============================================

-- USE syugetsu2025_clone;  -- 環境に応じて変更してください

-- ===============================================
-- メールテーブル（全般的なメール保存）
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_mails (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- メールタイプ: info(情報), war(戦争), conquest(占領戦), reconnaissance(偵察)
    mail_type ENUM('info', 'war', 'conquest', 'reconnaissance') NOT NULL DEFAULT 'info',
    -- 差出人: システムの場合はNULL（「システム」と表示）、管理人の場合はユーザーID
    sender_user_id INT NULL,
    -- 受取人: 全員の場合はNULL（「プレイヤー」と表示）、個別の場合はユーザーID
    recipient_user_id INT NULL,
    -- メールタイトル
    subject VARCHAR(255) NOT NULL,
    -- メール本文（戦闘ログなどの詳細情報を含むJSON可）
    body TEXT NOT NULL,
    -- 添付詳細データ（戦闘ログ、部隊情報など）
    extra_data JSON NULL,
    -- 補填資源（管理人からの一斉送信時）
    compensation JSON NULL COMMENT '{"coins": 100, "crystals": 10, "diamonds": 5, "resources": {"food": 100}}',
    -- 既読フラグ（全体メールは各ユーザーの既読状態を別テーブルで管理）
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mail_type (mail_type),
    INDEX idx_sender (sender_user_id),
    INDEX idx_recipient (recipient_user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='文明育成ゲームのメールシステム';

-- ===============================================
-- メール既読状態テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_mail_read_status (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mail_id BIGINT UNSIGNED NOT NULL,
    user_id INT NOT NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    read_at DATETIME NULL,
    -- 補填受取済みフラグ
    compensation_claimed BOOLEAN NOT NULL DEFAULT FALSE,
    compensation_claimed_at DATETIME NULL,
    UNIQUE KEY unique_mail_user (mail_id, user_id),
    FOREIGN KEY (mail_id) REFERENCES civilization_mails(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_unread (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='メール既読状態';

-- ===============================================
-- 偵察システム用テーブル
-- ===============================================

-- 偵察レート制限テーブル（戦争/占領戦別に追跡）
CREATE TABLE IF NOT EXISTS civilization_reconnaissance_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    -- 偵察タイプ: war(戦争), conquest(占領戦)
    reconnaissance_type ENUM('war', 'conquest') NOT NULL,
    -- 偵察対象: 戦争の場合はユーザーID、占領戦の場合は城ID
    target_user_id INT NULL,
    target_castle_id BIGINT UNSIGNED NULL,
    -- 成功したかどうか
    is_successful BOOLEAN NOT NULL DEFAULT TRUE,
    -- 結果メールID
    result_mail_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (result_mail_id) REFERENCES civilization_mails(id) ON DELETE SET NULL,
    INDEX idx_user_type (user_id, reconnaissance_type),
    INDEX idx_created_at (created_at),
    INDEX idx_rate_limit (user_id, reconnaissance_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='偵察ログ（レート制限用）';

-- ===============================================
-- 戦争・占領戦メールログテーブル（詳細版戦闘ログ保存用）
-- ===============================================
-- 既存のcivilization_war_logsとconquest_battle_logsにmail_id列を追加

-- civilization_war_logsにmail_id列を追加
ALTER TABLE civilization_war_logs 
ADD COLUMN IF NOT EXISTS attacker_mail_id BIGINT UNSIGNED NULL AFTER loot_resources,
ADD COLUMN IF NOT EXISTS defender_mail_id BIGINT UNSIGNED NULL AFTER attacker_mail_id;

-- conquest_battle_logsにmail_id列を追加
ALTER TABLE conquest_battle_logs 
ADD COLUMN IF NOT EXISTS attacker_mail_id BIGINT UNSIGNED NULL,
ADD COLUMN IF NOT EXISTS defender_mail_id BIGINT UNSIGNED NULL;

-- ===============================================
-- 初期データ: システムからの歓迎メール用
-- ===============================================
-- （初期データは必要に応じてAPIから投入）

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'メールシステムのスキーマが作成されました。' AS status;
