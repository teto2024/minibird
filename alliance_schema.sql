-- ===============================================
-- MiniBird 同盟システム（Alliance System）
-- 指定した相手と不戦状態にし、兵や資源を援助可能にする
-- ===============================================

USE microblog;

-- ===============================================
-- 同盟テーブル
-- ステータス: pending（申請中）, accepted（締結済み）, rejected（拒否）
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_alliances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_user_id INT UNSIGNED NOT NULL COMMENT '同盟申請者',
    target_user_id INT UNSIGNED NOT NULL COMMENT '同盟対象者',
    status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responded_at DATETIME NULL COMMENT '応答日時',
    ended_at DATETIME NULL COMMENT '同盟解消日時',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT '同盟が有効かどうか',
    FOREIGN KEY (requester_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_requester (requester_user_id),
    INDEX idx_target (target_user_id),
    INDEX idx_status (status),
    INDEX idx_active (is_active),
    UNIQUE KEY unique_alliance (requester_user_id, target_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='同盟テーブル';

-- ===============================================
-- 兵士援助ログ（送兵）
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_troop_transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_user_id INT UNSIGNED NOT NULL COMMENT '送信者',
    receiver_user_id INT UNSIGNED NOT NULL COMMENT '受信者',
    troop_type_id INT UNSIGNED NOT NULL COMMENT '兵種ID',
    count INT UNSIGNED NOT NULL COMMENT '送った数',
    transferred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (troop_type_id) REFERENCES civilization_troop_types(id) ON DELETE CASCADE,
    INDEX idx_sender (sender_user_id),
    INDEX idx_receiver (receiver_user_id),
    INDEX idx_transferred_at (transferred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='兵士援助ログ';

-- ===============================================
-- 資源援助ログ（物資援助）
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_resource_transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_user_id INT UNSIGNED NOT NULL COMMENT '送信者',
    receiver_user_id INT UNSIGNED NOT NULL COMMENT '受信者',
    resource_type_id INT UNSIGNED NOT NULL COMMENT '資源ID',
    amount DECIMAL(20,2) NOT NULL COMMENT '送った量',
    transferred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_type_id) REFERENCES civilization_resource_types(id) ON DELETE CASCADE,
    INDEX idx_sender (sender_user_id),
    INDEX idx_receiver (receiver_user_id),
    INDEX idx_transferred_at (transferred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='資源援助ログ';

-- テーブル作成完了メッセージ
SELECT 'Alliance system tables created successfully' AS status;
