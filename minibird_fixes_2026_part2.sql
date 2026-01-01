-- ===============================================
-- MiniBird 機能追加・修正スキーマ 2026 Part 2
-- ① コイン投資エラー修正（PHP側で対応済み）
-- ② ポータルボス獲得アイテム表示修正（JS側で対応済み）
-- ③ スペシャルイベントのアイテムドロップを上方修正
-- ④ 交換ショップの引換レートを改善
-- ⑤ 経験値付与修正（PHP側で対応済み）
-- ⑥ デイリータスク進捗修正（PHP側で対応済み）
-- ===============================================

USE microblog;

-- ===============================================
-- ③ スペシャルイベントのアイテムドロップを上方修正
-- ドロップ率を大幅に増加
-- ===============================================

-- 既存のドロップ率を大幅アップ（新春イベント2026用）
UPDATE special_event_items sei
JOIN civilization_events ce ON sei.event_id = ce.id
SET sei.drop_rate = 
    CASE 
        WHEN sei.item_key = 'new_year_coin' THEN 70.00    -- 30% → 70%
        WHEN sei.item_key = 'lucky_charm' THEN 45.00      -- 15% → 45%
        WHEN sei.item_key = 'golden_dragon' THEN 25.00    -- 5% → 25%
        WHEN sei.item_key = 'phoenix_feather' THEN 15.00  -- 2% → 15%
        ELSE sei.drop_rate
    END
WHERE ce.event_key = 'new_year_2026';

-- ポータルボスのloot_tableも大幅改善（より多く、複数種類）
UPDATE special_event_portal_bosses sepb
JOIN civilization_events ce ON sepb.event_id = ce.id
SET sepb.loot_table = (
    SELECT CONCAT(
        '[',
        '{"item_id":', COALESCE((SELECT id FROM special_event_items WHERE item_key = 'new_year_coin' AND event_id = ce.id LIMIT 1), 0), 
            ',"chance":90,"min_count":3,"max_count":10},',
        '{"item_id":', COALESCE((SELECT id FROM special_event_items WHERE item_key = 'lucky_charm' AND event_id = ce.id LIMIT 1), 0), 
            ',"chance":70,"min_count":2,"max_count":6},',
        '{"item_id":', COALESCE((SELECT id FROM special_event_items WHERE item_key = 'golden_dragon' AND event_id = ce.id LIMIT 1), 0), 
            ',"chance":50,"min_count":1,"max_count":4},',
        '{"item_id":', COALESCE((SELECT id FROM special_event_items WHERE item_key = 'phoenix_feather' AND event_id = ce.id LIMIT 1), 0), 
            ',"chance":30,"min_count":1,"max_count":2}',
        ']'
    )
)
WHERE ce.event_key = 'new_year_2026';

-- ===============================================
-- ④ 交換ショップの引換レートを改善（還元率大幅アップ）
-- 必要アイテム数を減らし、報酬額を増加
-- ===============================================

-- 既存の交換レートを改善
UPDATE special_event_exchange see
JOIN civilization_events ce ON see.event_id = ce.id
SET 
    see.required_count = 
        CASE 
            WHEN see.reward_type = 'coins' AND see.required_count = 10 THEN 3      -- 10個 → 3個で1000コイン
            WHEN see.reward_type = 'crystals' AND see.required_count = 30 THEN 5   -- 30個 → 5個で5クリスタル
            WHEN see.reward_type = 'diamonds' AND see.required_count = 5 THEN 2    -- 5個 → 2個で3ダイヤ
            WHEN see.reward_type = 'diamonds' AND see.required_count = 3 THEN 1    -- 3個 → 1個で10ダイヤ
            WHEN see.reward_type = 'diamonds' AND see.required_count = 1 THEN 1    -- 1個 → 1個で50ダイヤ（変更なし）
            ELSE see.required_count
        END,
    see.reward_amount = 
        CASE 
            WHEN see.reward_type = 'coins' AND see.reward_amount = 1000 THEN 3000       -- 1000 → 3000コイン
            WHEN see.reward_type = 'crystals' AND see.reward_amount = 5 THEN 15         -- 5 → 15クリスタル
            WHEN see.reward_type = 'diamonds' AND see.reward_amount = 3 THEN 10         -- 3 → 10ダイヤ
            WHEN see.reward_type = 'diamonds' AND see.reward_amount = 10 THEN 30        -- 10 → 30ダイヤ
            WHEN see.reward_type = 'diamonds' AND see.reward_amount = 50 THEN 100       -- 50 → 100ダイヤ
            ELSE see.reward_amount
        END
WHERE ce.event_key = 'new_year_2026';

-- 交換上限も増加（より多く交換できるように）
UPDATE special_event_exchange see
JOIN civilization_events ce ON see.event_id = ce.id
SET see.exchange_limit = 
    CASE 
        WHEN see.exchange_limit IS NOT NULL THEN see.exchange_limit * 3
        ELSE see.exchange_limit
    END
WHERE ce.event_key = 'new_year_2026';

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'MiniBird fixes 2026 Part 2 schema applied successfully' AS status;
