-- ===============================================
-- MiniBird 兵種スキル復元スキーマ
-- battle_turn_system_schema.sqlで定義された元のスキル割り当てを復元
-- conquest_durability_schema.sqlで上書きされた場合に実行してください
-- ===============================================

USE microblog;

-- ===============================================
-- 前提条件: battle_turn_system_schema.sqlが実行済みであること
-- このスキーマはbattle_special_skillsテーブルに基本スキルが
-- 存在していることを前提としています。
-- 
-- スキルが存在しない場合、該当するUPDATE文はNULLを設定します。
-- これは意図した動作であり、後でbattle_turn_system_schema.sqlを
-- 実行することでスキルが正しく設定されます。
-- ===============================================

-- ===============================================
-- 元々の12種類の基本スキル（battle_turn_system_schema.sqlで定義）
-- 1: burn (燃焼)
-- 2: poison (毒)
-- 3: freeze (凍結)
-- 4: vulnerable (無防備)
-- 5: attack_up (攻撃力上昇)
-- 6: armor_harden (アーマー硬化)
-- 7: attack_down (攻撃低下)
-- 8: acceleration (加速)
-- 9: heal (回復)
-- 10: stun (スタン)
-- 11: critical (クリティカル)
-- 12: defense_break (防御破壊)
-- ===============================================

-- 兵種に元々の特殊スキルを復元
-- 狩人 - 毒攻撃
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'poison' LIMIT 1) WHERE troop_key = 'hunter';
-- 戦士 - 攻撃力上昇
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'attack_up' LIMIT 1) WHERE troop_key = 'warrior';
-- 槍兵 - 防御破壊
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'defense_break' LIMIT 1) WHERE troop_key = 'spearman';
-- 戦車 - 加速
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'acceleration' LIMIT 1) WHERE troop_key = 'chariot';
-- 剣士 - クリティカル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'critical' LIMIT 1) WHERE troop_key = 'swordsman';
-- 騎兵 - 加速
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'acceleration' LIMIT 1) WHERE troop_key = 'cavalry';
-- 弓兵 - 燃焼
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'burn' LIMIT 1) WHERE troop_key = 'archer';
-- 騎士 - アーマー硬化
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'armor_harden' LIMIT 1) WHERE troop_key = 'knight';
-- クロスボウ兵 - 無防備
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'vulnerable' LIMIT 1) WHERE troop_key = 'crossbowman';
-- カタパルト - 防御破壊
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'defense_break' LIMIT 1) WHERE troop_key = 'catapult';
-- マスケット銃兵 - 攻撃低下
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'attack_down' LIMIT 1) WHERE troop_key = 'musketeer';
-- 大砲 - 燃焼
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'burn' LIMIT 1) WHERE troop_key = 'cannon';
-- ガレオン船 - スタン
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'stun' LIMIT 1) WHERE troop_key = 'galleon';
-- 歩兵 - 回復
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'heal' LIMIT 1) WHERE troop_key = 'infantry';
-- 砲兵 - 攻撃低下
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'attack_down' LIMIT 1) WHERE troop_key = 'artillery';
-- 装甲艦 - アーマー硬化
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'armor_harden' LIMIT 1) WHERE troop_key = 'ironclad';
-- 戦車(tank) - クリティカル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'critical' LIMIT 1) WHERE troop_key = 'tank';
-- 戦闘機 - 加速
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'acceleration' LIMIT 1) WHERE troop_key = 'fighter';
-- 爆撃機 - 燃焼
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'burn' LIMIT 1) WHERE troop_key = 'bomber';
-- 潜水艦 - 毒
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'poison' LIMIT 1) WHERE troop_key = 'submarine';

-- 追加兵種への設定
-- 斥候 - 加速
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'acceleration' LIMIT 1) WHERE troop_key = 'scout';
-- 民兵 - 回復
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'heal' LIMIT 1) WHERE troop_key = 'militia';
-- ファランクス - アーマー硬化
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'armor_harden' LIMIT 1) WHERE troop_key = 'phalanx';
-- 長槍兵 - 防御破壊
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'defense_break' LIMIT 1) WHERE troop_key = 'pikeman';
-- 長弓兵 - 凍結
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'freeze' LIMIT 1) WHERE troop_key = 'longbowman';
-- トレビュシェット - 無防備
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'vulnerable' LIMIT 1) WHERE troop_key = 'trebuchet';
-- 戦象 - スタン
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'stun' LIMIT 1) WHERE troop_key = 'war_elephant';
-- ライフル兵 - クリティカル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'critical' LIMIT 1) WHERE troop_key = 'rifleman';
-- 竜騎兵 - 攻撃力上昇
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'attack_up' LIMIT 1) WHERE troop_key = 'dragoon';
-- フリゲート艦 - 凍結
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'freeze' LIMIT 1) WHERE troop_key = 'frigate';
-- 海兵隊 - 攻撃力上昇
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'attack_up' LIMIT 1) WHERE troop_key = 'marine';
-- 空挺部隊 - 加速
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'acceleration' LIMIT 1) WHERE troop_key = 'paratroopers';
-- 特殊部隊 - クリティカル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'critical' LIMIT 1) WHERE troop_key = 'special_forces';
-- ミサイル発射機 - 燃焼
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'burn' LIMIT 1) WHERE troop_key = 'missile_launcher';
-- ステルス戦闘機 - 無防備
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'vulnerable' LIMIT 1) WHERE troop_key = 'stealth_fighter';
-- 航空母艦 - アーマー硬化
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'armor_harden' LIMIT 1) WHERE troop_key = 'aircraft_carrier';
-- 原子力潜水艦 - 毒
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'poison' LIMIT 1) WHERE troop_key = 'nuclear_submarine';

-- 医療ユニット - 回復
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'heal' LIMIT 1) WHERE troop_key = 'medic';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'heal' LIMIT 1) WHERE troop_key = 'field_surgeon';
-- 攻城兵器
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'vulnerable' LIMIT 1) WHERE troop_key = 'siege_tower';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'defense_break' LIMIT 1) WHERE troop_key = 'battering_ram';
-- 特殊ユニット
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'armor_harden' LIMIT 1) WHERE troop_key = 'royal_guard';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'attack_up' LIMIT 1) WHERE troop_key = 'berserker';

-- スキーマ復元完了メッセージ
SELECT 'Original troop skills restored successfully' AS status;
