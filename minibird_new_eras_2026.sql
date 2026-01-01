-- ===============================================
-- MiniBird 新時代追加スキーマ 2026
-- ⑤ 原子力時代 → 現代Ⅱ → 現代Ⅲ → 量子革命時代 → 現代Ⅳ → 現代Ⅴ → 宇宙時代
-- 各時代に対応した資源、研究、建築物、兵士、兵種スキルを追加
-- ===============================================

USE microblog;

-- ===============================================
-- 新しい時代の追加
-- ===============================================
INSERT IGNORE INTO civilization_eras (era_key, name, icon, description, era_order, unlock_population, unlock_research_points, color) VALUES
('atomic_age', '原子力時代', '☢️', '核エネルギーの発見。人類は新たな力を手に入れた。', 8, 8000, 50000, '#00FF00'),
('modern_2', '現代Ⅱ', '🌐', 'インターネットの時代。情報革命が始まる。', 9, 12000, 80000, '#0080FF'),
('modern_3', '現代Ⅲ', '📱', 'スマートフォンとSNSの時代。世界がつながる。', 10, 18000, 120000, '#FF69B4'),
('quantum_revolution', '量子革命時代', '⚛️', '量子コンピューターの実用化。計算の限界を超える。', 11, 25000, 180000, '#8A2BE2'),
('modern_4', '現代Ⅳ', '🤖', 'AI革命の時代。人工知能が社会を変える。', 12, 35000, 250000, '#FF4500'),
('modern_5', '現代Ⅴ', '🧬', 'バイオテクノロジーの時代。生命の設計が可能に。', 13, 50000, 350000, '#32CD32'),
('space_age', '宇宙時代', '🚀', '宇宙への進出。人類は新たなフロンティアを目指す。', 14, 75000, 500000, '#4B0082');

-- ===============================================
-- 新しい資源タイプの追加
-- ===============================================
INSERT IGNORE INTO civilization_resource_types (resource_key, name, icon, description, unlock_order, color) VALUES
('plutonium', 'プルトニウム', '☢️', '核兵器と原子力発電に必要', 10, '#7CFC00'),
('silicon', 'シリコン', '🔲', '半導体と電子機器に必要', 11, '#C0C0C0'),
('rare_earth', 'レアアース', '💫', 'ハイテク機器に必要な希少資源', 12, '#FFD700'),
('quantum_crystal', '量子結晶', '🔮', '量子コンピューターに必要な特殊資源', 13, '#9400D3'),
('ai_core', 'AIコア', '🧠', 'AIシステムの中核となる処理装置', 14, '#FF6347'),
('gene_sample', '遺伝子サンプル', '🧬', 'バイオテクノロジー研究に必要', 15, '#00FA9A'),
('dark_matter', 'ダークマター', '🌌', '宇宙技術に必要な謎の物質', 16, '#191970'),
('antimatter', '反物質', '💥', '宇宙船の燃料となる究極のエネルギー源', 17, '#FF00FF');

-- ===============================================
-- 原子力時代の建物
-- ===============================================
INSERT IGNORE INTO civilization_building_types (building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) VALUES
-- 生産系
('nuclear_plant', '原子力発電所', '🏭', '核分裂エネルギーで大量発電', 'production', NULL, 0, 10, 8, 100000, '{"iron": 2000, "uranium": 100, "oil": 500}', 43200, 0, 50),
('plutonium_refinery', 'プルトニウム精製所', '☢️', 'プルトニウムを精製する', 'production', NULL, 0, 10, 8, 150000, '{"uranium": 200, "iron": 1500}', 57600, 0, 0),
-- 軍事系
('missile_silo', 'ミサイル基地', '🚀', 'ICBMを格納', 'military', NULL, 0, 5, 8, 200000, '{"iron": 3000, "oil": 800}', 72000, 0, 2000),
('nuclear_bunker', '核シェルター', '🛡️', '核攻撃から市民を守る', 'housing', NULL, 0, 5, 8, 80000, '{"stone": 3000, "iron": 1000}', 36000, 200, 100),
-- 研究系
('nuclear_lab', '核研究所', '🔬', '核技術の研究', 'research', NULL, 0, 10, 8, 120000, '{"knowledge": 500, "uranium": 50}', 43200, 0, 0);

-- ===============================================
-- 現代Ⅱの建物（インターネット時代）
-- ===============================================
INSERT IGNORE INTO civilization_building_types (building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) VALUES
('data_center', 'データセンター', '🖥️', 'インターネットの中核', 'production', NULL, 0, 15, 9, 180000, '{"iron": 2500, "silicon": 500}', 54000, 0, 50),
('silicon_foundry', 'シリコン精錬所', '🔲', 'シリコンを精製', 'production', NULL, 0, 10, 9, 200000, '{"stone": 2000, "oil": 1000}', 48000, 0, 0),
('smart_city_hub', 'スマートシティハブ', '🏙️', '都市をスマート化', 'housing', NULL, 0, 10, 9, 250000, '{"iron": 3000, "silicon": 800}', 72000, 500, 100),
('cyber_command', 'サイバー司令部', '💻', 'サイバー戦争の指揮', 'military', NULL, 0, 5, 9, 220000, '{"silicon": 1000, "knowledge": 500}', 64800, 0, 1500),
('tech_university', 'テクノロジー大学', '🎓', 'IT人材を育成', 'research', NULL, 0, 10, 9, 150000, '{"knowledge": 800, "gold": 500}', 43200, 0, 0);

-- ===============================================
-- 現代Ⅲの建物（スマートフォン時代）
-- ===============================================
INSERT IGNORE INTO civilization_building_types (building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) VALUES
('rare_earth_mine', 'レアアース鉱山', '💫', 'レアアースを採掘', 'production', NULL, 0, 10, 10, 300000, '{"stone": 3000, "iron": 2000}', 64800, 0, 0),
('smartphone_factory', 'スマートフォン工場', '📱', 'スマートフォンを製造', 'production', NULL, 0, 15, 10, 350000, '{"silicon": 1500, "rare_earth": 300}', 72000, 0, 100),
('social_media_center', 'ソーシャルメディアセンター', '📲', '情報戦の拠点', 'military', NULL, 0, 10, 10, 280000, '{"silicon": 1200, "knowledge": 600}', 57600, 0, 800),
('eco_tower', 'エコタワー', '🌿', '環境に優しい高層住宅', 'housing', NULL, 0, 10, 10, 400000, '{"iron": 4000, "glass": 1500}', 86400, 800, 50);

-- ===============================================
-- 量子革命時代の建物
-- ===============================================
INSERT IGNORE INTO civilization_building_types (building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) VALUES
('quantum_lab', '量子研究所', '⚛️', '量子コンピューターを研究', 'research', NULL, 0, 10, 11, 500000, '{"silicon": 2000, "rare_earth": 500, "knowledge": 1000}', 86400, 0, 0),
('quantum_crystal_mine', '量子結晶鉱山', '🔮', '量子結晶を採掘', 'production', NULL, 0, 10, 11, 600000, '{"stone": 5000, "diamond": 200}', 100800, 0, 0),
('quantum_computer_center', '量子コンピューターセンター', '💾', '超高速計算処理', 'production', NULL, 0, 5, 11, 800000, '{"silicon": 3000, "quantum_crystal": 100}', 129600, 0, 200),
('quantum_shield_generator', '量子シールド発生装置', '🛡️', '量子力学的防御', 'military', NULL, 0, 5, 11, 700000, '{"quantum_crystal": 200, "iron": 5000}', 115200, 0, 5000);

-- ===============================================
-- 現代Ⅳの建物（AI時代）
-- ===============================================
INSERT IGNORE INTO civilization_building_types (building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) VALUES
('ai_research_center', 'AI研究センター', '🤖', 'AIの開発と研究', 'research', NULL, 0, 10, 12, 600000, '{"silicon": 2500, "quantum_crystal": 150, "knowledge": 1500}', 100800, 0, 100),
('ai_core_factory', 'AIコア製造工場', '🧠', 'AIコアを製造', 'production', NULL, 0, 10, 12, 700000, '{"silicon": 3000, "rare_earth": 800}', 115200, 0, 0),
('autonomous_drone_base', '自律ドローン基地', '🚁', 'AI制御ドローン軍を運用', 'military', NULL, 0, 10, 12, 800000, '{"ai_core": 100, "iron": 4000}', 129600, 0, 3000),
('robot_city', 'ロボットシティ', '🏙️', 'AI管理の完全自動都市', 'housing', NULL, 0, 5, 12, 1000000, '{"ai_core": 200, "silicon": 5000, "iron": 8000}', 172800, 2000, 500);

-- ===============================================
-- 現代Ⅴの建物（バイオテクノロジー時代）
-- ===============================================
INSERT IGNORE INTO civilization_building_types (building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) VALUES
('biolab', 'バイオ研究所', '🧬', '遺伝子工学の研究', 'research', NULL, 0, 10, 13, 800000, '{"knowledge": 2000, "gene_sample": 50}', 129600, 0, 0),
('gene_vault', '遺伝子バンク', '🏦', '遺伝子サンプルを保存', 'production', NULL, 0, 10, 13, 700000, '{"iron": 5000, "diamond": 300}', 115200, 0, 0),
('biome_dome', 'バイオームドーム', '🌐', '人工生態系の住居', 'housing', NULL, 0, 5, 13, 1200000, '{"gene_sample": 150, "glass": 5000, "iron": 6000}', 172800, 3000, 200),
('bio_soldier_lab', 'バイオソルジャー研究所', '🧫', '遺伝子強化兵士の開発', 'military', NULL, 0, 5, 13, 900000, '{"gene_sample": 200, "ai_core": 100}', 144000, 0, 4000);

-- ===============================================
-- 宇宙時代の建物
-- ===============================================
INSERT IGNORE INTO civilization_building_types (building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) VALUES
('space_port', '宇宙港', '🚀', '宇宙船の発着場', 'special', NULL, 0, 5, 14, 2000000, '{"iron": 10000, "oil": 5000, "diamond": 500}', 259200, 0, 1000),
('dark_matter_harvester', 'ダークマター収集装置', '🌌', 'ダークマターを収集', 'production', NULL, 0, 10, 14, 1500000, '{"quantum_crystal": 500, "iron": 8000}', 216000, 0, 0),
('antimatter_reactor', '反物質リアクター', '💥', '反物質を生成', 'production', NULL, 0, 5, 14, 2500000, '{"dark_matter": 100, "uranium": 1000}', 302400, 0, 500),
('orbital_station', '軌道ステーション', '🛰️', '宇宙空間の住居', 'housing', NULL, 0, 10, 14, 3000000, '{"iron": 15000, "silicon": 5000, "dark_matter": 200}', 345600, 5000, 2000),
('space_battleship_dock', '宇宙戦艦ドック', '⚔️', '宇宙艦隊を建造', 'military', NULL, 0, 5, 14, 5000000, '{"iron": 20000, "antimatter": 50, "ai_core": 500}', 432000, 0, 10000),
('dyson_sphere_project', 'ダイソン球計画', '☀️', '恒星エネルギーを利用する究極の建造物', 'special', NULL, 0, 1, 14, 10000000, '{"iron": 50000, "dark_matter": 500, "antimatter": 200}', 604800, 0, 50000);

-- ===============================================
-- 新しい兵種の追加（原子力時代〜宇宙時代）
-- ===============================================
INSERT IGNORE INTO civilization_troop_types (troop_key, name, icon, description, unlock_era_id, attack_power, defense_power, train_cost_coins, train_cost_resources, train_time_seconds) VALUES
-- 原子力時代
('nuclear_soldier', '核対応歩兵', '☢️', '放射能環境で活動できる特殊部隊', 8, 120, 100, 5000, '{"food": 200, "oil": 50}', 300),
('stealth_bomber', 'ステルス爆撃機', '✈️', 'レーダーに映らない爆撃機', 8, 250, 80, 15000, '{"iron": 500, "oil": 200}', 900),
('nuclear_submarine', '原子力潜水艦', '🛥️', '核搭載の潜水艦', 8, 300, 200, 25000, '{"iron": 800, "uranium": 50}', 1800),

-- 現代Ⅱ
('cyber_operative', 'サイバー工作員', '💻', 'デジタル戦争の専門家', 9, 100, 50, 4000, '{"food": 100, "silicon": 30}', 240),
('drone_swarm', 'ドローン群', '🚁', '小型ドローンの群れ', 9, 180, 60, 8000, '{"silicon": 100, "oil": 80}', 480),
('network_defender', 'ネットワーク防衛隊', '🛡️', 'サイバー攻撃から守る', 9, 80, 150, 6000, '{"silicon": 80, "knowledge": 50}', 360),

-- 現代Ⅲ
('influencer_unit', 'インフルエンサー部隊', '📲', '情報戦で敵の士気を下げる', 10, 60, 40, 3000, '{"food": 80}', 180),
('smart_soldier', 'スマートソルジャー', '🎯', 'AR/VR技術で強化された兵士', 10, 200, 120, 10000, '{"food": 150, "silicon": 100, "rare_earth": 20}', 600),
('electronic_warfare_unit', '電子戦部隊', '📡', '敵の通信を妨害', 10, 100, 80, 7000, '{"silicon": 120, "rare_earth": 30}', 420),

-- 量子革命時代
('quantum_hacker', '量子ハッカー', '⚛️', '量子暗号を解読する', 11, 150, 70, 15000, '{"food": 200, "quantum_crystal": 10}', 720),
('teleport_commando', 'テレポートコマンドー', '🌀', '瞬間移動で奇襲攻撃', 11, 300, 100, 25000, '{"food": 250, "quantum_crystal": 30}', 1200),
('quantum_tank', '量子戦車', '🔮', '量子シールド搭載の戦車', 11, 400, 350, 40000, '{"iron": 1000, "quantum_crystal": 50}', 1800),

-- 現代Ⅳ（AI時代）
('ai_soldier', 'AI兵士', '🤖', '完全自律型のロボット兵', 12, 250, 200, 20000, '{"ai_core": 5, "iron": 300}', 900),
('autonomous_tank', '自律戦車', '🛡️', 'AI制御の無人戦車', 12, 450, 400, 50000, '{"ai_core": 10, "iron": 600}', 1500),
('hunter_killer_drone', 'ハンターキラードローン', '🎯', '標的を自動追尾する致死ドローン', 12, 350, 150, 30000, '{"ai_core": 8, "silicon": 200}', 1080),

-- 現代Ⅴ（バイオテクノロジー時代）
('super_soldier', 'スーパーソルジャー', '💪', '遺伝子強化された超人兵士', 13, 400, 300, 35000, '{"food": 300, "gene_sample": 20}', 1200),
('bio_beast', 'バイオビースト', '🦖', '遺伝子改変された戦闘生物', 13, 500, 350, 45000, '{"food": 500, "gene_sample": 40}', 1500),
('healing_squad', '治癒部隊', '💊', '戦場で味方を回復する', 13, 100, 200, 25000, '{"food": 200, "gene_sample": 15}', 900),

-- 宇宙時代
('space_marine', 'スペースマリーン', '👨‍🚀', '宇宙空間で戦闘できる精鋭', 14, 500, 400, 50000, '{"food": 400, "iron": 500, "dark_matter": 5}', 1800),
('orbital_mech', '軌道メック', '🤖', '宇宙用巨大ロボット', 14, 700, 600, 80000, '{"iron": 1000, "ai_core": 20, "dark_matter": 15}', 2700),
('antimatter_bomber', '反物質爆撃機', '💥', '反物質爆弾を投下する', 14, 1000, 300, 120000, '{"iron": 800, "antimatter": 10}', 3600),
('starship_fighter', 'スターシップファイター', '🛸', '宇宙戦闘機', 14, 800, 500, 100000, '{"iron": 700, "dark_matter": 20, "antimatter": 5}', 3000);

-- ===============================================
-- 新しい研究の追加
-- ===============================================
INSERT IGNORE INTO civilization_researches (research_key, name, icon, description, era_id, unlock_building_id, unlock_resource_id, research_cost_points, research_time_seconds, prerequisite_research_id) VALUES
-- 原子力時代（前提研究は「電気」を想定、存在しない場合はNULLになる）
('nuclear_fission', '核分裂', '☢️', '原子核を分裂させてエネルギーを得る', 8, NULL, NULL, 2000, 10800, NULL),
('nuclear_weapons', '核兵器', '💣', '究極の破壊兵器', 8, NULL, NULL, 3000, 14400, NULL),
('radiation_protection', '放射線防護', '🛡️', '放射能から身を守る技術', 8, NULL, NULL, 2500, 12600, NULL),

-- 現代Ⅱ
('internet_protocols', 'インターネットプロトコル', '🌐', 'グローバルネットワークの基盤', 9, NULL, NULL, 4000, 18000, NULL),
('semiconductor_technology', '半導体技術', '🔲', 'コンピューターの核心技術', 9, NULL, NULL, 4500, 19800, NULL),
('cyber_security', 'サイバーセキュリティ', '🔒', 'デジタル空間を守る', 9, NULL, NULL, 3500, 16200, NULL),

-- 現代Ⅲ
('mobile_computing', 'モバイルコンピューティング', '📱', 'いつでもどこでもコンピューター', 10, NULL, NULL, 5500, 21600, NULL),
('social_networks', 'ソーシャルネットワーク', '🔗', '世界をつなぐ', 10, NULL, NULL, 5000, 19800, NULL),
('renewable_energy', '再生可能エネルギー', '🌿', '持続可能なエネルギー源', 10, NULL, NULL, 6000, 23400, NULL),

-- 量子革命時代
('quantum_mechanics', '量子力学', '⚛️', '量子レベルの物理学', 11, NULL, NULL, 8000, 28800, NULL),
('quantum_computing', '量子コンピューティング', '💻', '量子効果を利用した計算', 11, NULL, NULL, 10000, 36000, NULL),
('quantum_cryptography', '量子暗号', '🔐', '解読不可能な暗号', 11, NULL, NULL, 9000, 32400, NULL),

-- 現代Ⅳ
('machine_learning', '機械学習', '🧠', 'AIの基礎技術', 12, NULL, NULL, 12000, 43200, NULL),
('artificial_general_intelligence', '汎用人工知能', '🤖', '人間レベルのAI', 12, NULL, NULL, 15000, 54000, NULL),
('autonomous_systems', '自律システム', '⚙️', '自律的に動く機械', 12, NULL, NULL, 13000, 46800, NULL),

-- 現代Ⅴ
('genetic_engineering', '遺伝子工学', '🧬', 'DNAを編集する', 13, NULL, NULL, 18000, 64800, NULL),
('synthetic_biology', '合成生物学', '🧫', '生命を設計する', 13, NULL, NULL, 20000, 72000, NULL),
('life_extension', '寿命延長', '♾️', '人間の寿命を延ばす', 13, NULL, NULL, 25000, 86400, NULL),

-- 宇宙時代
('space_propulsion', '宇宙推進技術', '🚀', '宇宙を航行する', 14, NULL, NULL, 30000, 100800, NULL),
('dark_matter_manipulation', 'ダークマター操作', '🌌', '謎の物質を制御する', 14, NULL, NULL, 40000, 129600, NULL),
('antimatter_engineering', '反物質工学', '💥', '反物質を利用する', 14, NULL, NULL, 50000, 172800, NULL),
('dyson_sphere_technology', 'ダイソン球技術', '☀️', '恒星のエネルギーを収穫する', 14, NULL, NULL, 100000, 259200, NULL);

-- ===============================================
-- 新しい兵種スキルの追加
-- ===============================================
INSERT IGNORE INTO battle_special_skills (skill_key, name, icon, description, effect_type, effect_target, effect_value, duration_turns, activation_chance) VALUES
-- 原子力時代スキル
('radiation_attack', '放射能攻撃', '☢️', '敵に放射能ダメージを与える', 'damage_over_time', 'enemy', 20, 3, 15),
('stealth_approach', 'ステルス接近', '👁️', 'レーダーを回避して奇襲', 'buff', 'self', 40, 1, 25),
('nuclear_deterrent', '核抑止力', '💣', '敵の攻撃力を大幅に下げる', 'debuff', 'enemy', 30, 2, 10),

-- 現代Ⅱスキル
('cyber_attack', 'サイバー攻撃', '💻', '敵のシステムを麻痺させる', 'debuff', 'enemy', 25, 2, 20),
('drone_barrage', 'ドローン一斉攻撃', '🚁', 'ドローンによる集中攻撃', 'damage', 'enemy', 50, 0, 18),
('firewall', 'ファイアウォール', '🛡️', 'サイバー攻撃を無効化', 'buff', 'self', 35, 2, 22),

-- 現代Ⅲスキル
('viral_propaganda', 'バイラルプロパガンダ', '📲', '敵の士気を下げる', 'debuff', 'enemy', 20, 3, 25),
('smart_targeting', 'スマート照準', '🎯', 'クリティカル率大幅上昇', 'critical', 'self', 30, 2, 20),
('electronic_jamming', '電子妨害', '📡', '敵のスキル発動率を下げる', 'debuff', 'enemy', 15, 2, 18),

-- 量子革命時代スキル
('quantum_tunneling', '量子トンネル効果', '🌀', '防御を無視してダメージ', 'damage', 'enemy', 60, 0, 12),
('superposition', '重ね合わせ', '⚛️', '攻撃を確率的に回避', 'buff', 'self', 50, 2, 15),
('quantum_entanglement', '量子もつれ', '🔮', '味方全体の能力を一時的に共有', 'buff', 'ally_all', 25, 2, 10),

-- 現代Ⅳスキル
('ai_prediction', 'AI予測', '🤖', '敵の行動を予測して回避', 'buff', 'self', 40, 2, 20),
('swarm_intelligence', '群知能', '🧠', '仲間が多いほど攻撃力上昇', 'buff', 'self', 35, 3, 18),
('auto_repair', '自動修復', '🔧', 'ダメージを自動で回復', 'heal', 'self', 15, 3, 22),

-- 現代Ⅴスキル
('gene_enhancement', '遺伝子強化', '💪', '一時的に能力を大幅強化', 'buff', 'self', 50, 2, 15),
('bio_regeneration', '生体再生', '🧬', '大量のHPを回復', 'heal', 'self', 30, 2, 12),
('plague_release', '疫病散布', '🦠', '敵に継続ダメージと弱体化', 'damage_over_time', 'enemy', 25, 4, 10),

-- 宇宙時代スキル
('zero_gravity_combat', '無重力戦闘', '🌌', '宇宙空間での優位性', 'buff', 'self', 45, 2, 20),
('antimatter_explosion', '反物質爆発', '💥', '巨大なダメージを与える', 'damage', 'enemy', 100, 0, 8),
('warp_strike', 'ワープストライク', '🛸', '瞬間移動で奇襲攻撃', 'damage', 'enemy', 80, 0, 15),
('energy_shield', 'エネルギーシールド', '🔰', 'ダメージを大幅カット', 'buff', 'self', 60, 2, 18);

-- ===============================================
-- 兵種とスキルの関連付け更新（特殊スキルIDを設定）
-- ===============================================

-- 原子力時代の兵種にスキルを設定
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'radiation_attack' LIMIT 1) WHERE troop_key = 'nuclear_soldier';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'stealth_approach' LIMIT 1) WHERE troop_key = 'stealth_bomber';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'nuclear_deterrent' LIMIT 1) WHERE troop_key = 'nuclear_submarine';

-- 現代Ⅱの兵種にスキルを設定
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'cyber_attack' LIMIT 1) WHERE troop_key = 'cyber_operative';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'drone_barrage' LIMIT 1) WHERE troop_key = 'drone_swarm';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'firewall' LIMIT 1) WHERE troop_key = 'network_defender';

-- 現代Ⅲの兵種にスキルを設定
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'viral_propaganda' LIMIT 1) WHERE troop_key = 'influencer_unit';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'smart_targeting' LIMIT 1) WHERE troop_key = 'smart_soldier';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'electronic_jamming' LIMIT 1) WHERE troop_key = 'electronic_warfare_unit';

-- 量子革命時代の兵種にスキルを設定
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'quantum_tunneling' LIMIT 1) WHERE troop_key = 'quantum_hacker';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'superposition' LIMIT 1) WHERE troop_key = 'teleport_commando';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'quantum_entanglement' LIMIT 1) WHERE troop_key = 'quantum_tank';

-- 現代Ⅳの兵種にスキルを設定
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'ai_prediction' LIMIT 1) WHERE troop_key = 'ai_soldier';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'swarm_intelligence' LIMIT 1) WHERE troop_key = 'autonomous_tank';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'auto_repair' LIMIT 1) WHERE troop_key = 'hunter_killer_drone';

-- 現代Ⅴの兵種にスキルを設定
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'gene_enhancement' LIMIT 1) WHERE troop_key = 'super_soldier';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'bio_regeneration' LIMIT 1) WHERE troop_key = 'bio_beast';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'plague_release' LIMIT 1) WHERE troop_key = 'healing_squad';

-- 宇宙時代の兵種にスキルを設定
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'zero_gravity_combat' LIMIT 1) WHERE troop_key = 'space_marine';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'energy_shield' LIMIT 1) WHERE troop_key = 'orbital_mech';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'antimatter_explosion' LIMIT 1) WHERE troop_key = 'antimatter_bomber';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'warp_strike' LIMIT 1) WHERE troop_key = 'starship_fighter';

-- ===============================================
-- 兵種カテゴリーの設定
-- 注意: troop_categoryカラムはcivilization_extended_schema.sqlで既に定義されている想定
-- 存在しない場合はこのスクリプトの前にcivilization_extended_schema.sqlを適用してください
-- ===============================================

-- 新しい兵種のカテゴリー設定（カラムが存在する場合のみ更新）
UPDATE civilization_troop_types SET troop_category = 'infantry' WHERE troop_key IN ('nuclear_soldier', 'super_soldier', 'space_marine') AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civilization_troop_types' AND COLUMN_NAME = 'troop_category');
UPDATE civilization_troop_types SET troop_category = 'air' WHERE troop_key IN ('stealth_bomber', 'drone_swarm', 'hunter_killer_drone', 'antimatter_bomber', 'starship_fighter') AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civilization_troop_types' AND COLUMN_NAME = 'troop_category');
UPDATE civilization_troop_types SET troop_category = 'naval' WHERE troop_key = 'nuclear_submarine' AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civilization_troop_types' AND COLUMN_NAME = 'troop_category');
UPDATE civilization_troop_types SET troop_category = 'cyber' WHERE troop_key IN ('cyber_operative', 'network_defender', 'influencer_unit', 'electronic_warfare_unit') AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civilization_troop_types' AND COLUMN_NAME = 'troop_category');
UPDATE civilization_troop_types SET troop_category = 'infantry' WHERE troop_key = 'smart_soldier' AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civilization_troop_types' AND COLUMN_NAME = 'troop_category');
UPDATE civilization_troop_types SET troop_category = 'ranged' WHERE troop_key IN ('quantum_hacker', 'teleport_commando') AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civilization_troop_types' AND COLUMN_NAME = 'troop_category');
UPDATE civilization_troop_types SET troop_category = 'siege' WHERE troop_key IN ('quantum_tank', 'ai_soldier', 'autonomous_tank', 'orbital_mech') AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civilization_troop_types' AND COLUMN_NAME = 'troop_category');
UPDATE civilization_troop_types SET troop_category = 'cavalry' WHERE troop_key IN ('bio_beast', 'healing_squad') AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civilization_troop_types' AND COLUMN_NAME = 'troop_category');

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'MiniBird new eras 2026 schema applied successfully' AS status;
SELECT CONCAT('Added ', COUNT(*), ' new eras') AS eras_count FROM civilization_eras WHERE era_order >= 8;
SELECT CONCAT('Added ', COUNT(*), ' new troop types') AS troops_count FROM civilization_troop_types WHERE unlock_era_id >= 8;
SELECT CONCAT('Added ', COUNT(*), ' new buildings') AS buildings_count FROM civilization_building_types WHERE unlock_era_id >= 8;
SELECT CONCAT('Added ', COUNT(*), ' new researches') AS researches_count FROM civilization_researches WHERE era_id >= 8;
