<?php
// ===============================================
// word_master_game.php
// 英単語マスターゲーム（HAMARUスタイル）
// 上から落ちてくる単語に答える
// ===============================================

require_once __DIR__ . '/config.php';

// 英単語マスター報酬設定
define('WM_BASE_COINS_PER_CORRECT', 20);
define('WM_BASE_CRYSTALS_PER_CORRECT', 1);
define('WM_INPUT_MODE_BONUS', 1.8);
define('WM_TEST_MODE_BONUS', 2.5);
define('WM_PERFECT_SCORE_BONUS', 2.0);
define('WM_HIGH_SCORE_BONUS', 1.8);      // 90点以上
define('WM_MID_SCORE_BONUS', 1.4);       // 70点以上
define('WM_UNIQUE_DROP_RATE_HIGH', 70);  // 90点以上でのユニークドロップ率
define('WM_UNIQUE_DROP_RATE_MID', 30);   // 70点以上でのユニークドロップ率
define('WM_LEGEND_DROP_RATE_PERFECT', 50); // パーフェクトでのレジェンドドロップ率
define('WM_LEGEND_DROP_RATE_HIGH', 25);  // 90点以上でのレジェンドドロップ率

$me = user();
if (!$me) {
    header('Location: ./login.php');
    exit;
}

$pdo = db();

// パラメータ取得
$mode = $_GET['mode'] ?? 'section';
$section = (int)($_GET['section'] ?? 1);
$level = $_GET['level'] ?? 'selection';
$stage = (int)($_GET['stage'] ?? 1);

// バフ確認
$buffLevel = 0;
try {
    $stmt = $pdo->prepare("
        SELECT level FROM user_buffs 
        WHERE user_id = ? AND type = 'word_master_reward' AND end_time > NOW()
        ORDER BY level DESC LIMIT 1
    ");
    $stmt->execute([$me['id']]);
    $buffLevel = $stmt->fetchColumn() ?: 0;
} catch (PDOException $e) {
    // user_buffs テーブルがない場合は無視
}
$buffMultiplier = 1 + ($buffLevel * 0.2);

// ゲーム設定
define('WORDS_PER_SECTION', 20);
define('MAX_QUESTIONS_NORMAL', 20);
define('MAX_QUESTIONS_TEST', 50);

// ステージごとの単語範囲（テストモード用）
$STAGE_RANGES = [
    1 => [1, 600],
    2 => [601, 1200],
    3 => [1201, 1700],
    4 => [1701, 2027]
];

// セクションごとの単語範囲を計算
if ($mode === 'section') {
    $startId = ($section - 1) * WORDS_PER_SECTION + 1;
    $endId = $section * WORDS_PER_SECTION;
    $gameTitle = "セクション {$section}";
} elseif ($mode === 'test') {
    $startId = $STAGE_RANGES[$stage][0] ?? 1;
    $endId = $STAGE_RANGES[$stage][1] ?? 600;
    $gameTitle = "ステージ {$stage} テスト";
} else {
    $startId = 1;
    $endId = 99999;
    $gameTitle = "苦手克服モード";
}

$isInputMode = ($level === 'input');
$maxQuestions = ($mode === 'test') ? MAX_QUESTIONS_TEST : MAX_QUESTIONS_NORMAL;

// AJAX処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    // 問題取得
    if ($action === 'get_question') {
        try {
            $usedIds = $input['used_ids'] ?? [];
            $usedPlaceholders = !empty($usedIds) ? implode(',', array_fill(0, count($usedIds), '?')) : '';
            
            if ($mode === 'weak') {
                // 苦手モード: 間違いが多い単語から出題
                // まずユーザーが単語を試したことがあるか確認
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM user_word_stats WHERE user_id = ? AND incorrect_count > correct_count");
                $checkStmt->execute([$me['id']]);
                $hasWeakWords = $checkStmt->fetchColumn() > 0;
                
                if (!$hasWeakWords) {
                    echo json_encode(['ok' => false, 'reason' => 'no_weak_words', 'message' => '苦手な単語がありません。まず通常モードで練習してください。']);
                    exit;
                }
                
                $sql = "SELECT ew.id, ew.word, ew.meaning
                        FROM english_words ew
                        JOIN user_word_stats uws ON ew.id = uws.word_id
                        WHERE uws.user_id = ? AND uws.incorrect_count > uws.correct_count";
                $params = [$me['id']];
                if (!empty($usedPlaceholders)) {
                    $sql .= " AND ew.id NOT IN ($usedPlaceholders)";
                    $params = array_merge($params, $usedIds);
                }
                $sql .= " ORDER BY (uws.incorrect_count - uws.correct_count) DESC LIMIT 1";
            } else {
                $sql = "SELECT id, word, meaning FROM english_words WHERE id BETWEEN ? AND ?";
                $params = [$startId, $endId];
                if (!empty($usedPlaceholders)) {
                    $sql .= " AND id NOT IN ($usedPlaceholders)";
                    $params = array_merge($params, $usedIds);
                }
                $sql .= " ORDER BY RAND() LIMIT 1";
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $word = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$word) {
                echo json_encode(['ok' => false, 'reason' => 'no_more_words']);
                exit;
            }
            
            // 選択肢を生成（選択式の場合）
            $choices = [];
            if (!$isInputMode) {
                $stmt = $pdo->prepare("SELECT meaning FROM english_words WHERE id != ? ORDER BY RAND() LIMIT 3");
                $stmt->execute([$word['id']]);
                $choices = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $choices[] = $word['meaning'];
                shuffle($choices);
            }
            
            echo json_encode([
                'ok' => true,
                'question' => [
                    'id' => $word['id'],
                    'word' => $word['word'],
                    'meaning' => $word['meaning'],
                    'choices' => $choices
                ]
            ]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['ok' => false, 'reason' => 'db_error', 'message' => 'データベースエラーが発生しました。テーブルが存在しない可能性があります。']);
            exit;
        }
    }
    
    // 回答チェック
    if ($action === 'check_answer') {
        try {
            $wordId = (int)($input['word_id'] ?? 0);
            $answer = trim($input['answer'] ?? '');
            $isCorrect = false;
            
            $stmt = $pdo->prepare("SELECT word, meaning FROM english_words WHERE id = ?");
            $stmt->execute([$wordId]);
            $word = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($word) {
                if ($isInputMode) {
                    // 入力式: 英語を入力
                    $isCorrect = (strtolower(trim($word['word'])) === strtolower($answer));
                } else {
                    // 選択式: 意味を選択
                    // 空白の違いや前後の空白を無視して比較
                    $normalizedMeaning = trim(preg_replace('/\s+/', ' ', $word['meaning']));
                    $normalizedAnswer = trim(preg_replace('/\s+/', ' ', $answer));
                    $isCorrect = ($normalizedMeaning === $normalizedAnswer);
                }
                
                // 統計更新
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO user_word_stats (user_id, word_id, correct_count, incorrect_count, last_attempt)
                        VALUES (?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE 
                            correct_count = correct_count + VALUES(correct_count),
                            incorrect_count = incorrect_count + VALUES(incorrect_count),
                            last_attempt = NOW()
                    ");
                    $stmt->execute([
                        $me['id'],
                        $wordId,
                        $isCorrect ? 1 : 0,
                        $isCorrect ? 0 : 1
                    ]);
                } catch (PDOException $e) {
                    // 統計テーブルがない場合は無視
                }
            }
            
            echo json_encode([
                'ok' => true,
                'correct' => $isCorrect,
                'correct_answer' => $isInputMode ? ($word['word'] ?? '') : ($word['meaning'] ?? '')
            ]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['ok' => false, 'error' => 'データベースエラーが発生しました。']);
            exit;
        }
    }
    
    // ゲーム終了 & 報酬付与
    if ($action === 'finish_game') {
        $score = (float)($input['score'] ?? 0);
        $correctCount = (int)($input['correct_count'] ?? 0);
        $totalQuestions = (int)($input['total_questions'] ?? 0);
        $comboMax = (int)($input['combo_max'] ?? 0);
        
        // スコアを100点満点で計算（小数点3桁）
        $finalScore = round($score, 3);
        
        // 報酬計算
        $baseCoins = $correctCount * WM_BASE_COINS_PER_CORRECT;
        $baseCrystals = $correctCount * WM_BASE_CRYSTALS_PER_CORRECT;
        
        // 難易度ボーナス
        if ($isInputMode) {
            $baseCoins *= WM_INPUT_MODE_BONUS;
            $baseCrystals *= WM_INPUT_MODE_BONUS;
        }
        if ($mode === 'test') {
            $baseCoins *= WM_TEST_MODE_BONUS;
            $baseCrystals *= WM_TEST_MODE_BONUS;
        }
        
        // スコアボーナス
        if ($finalScore >= 100) {
            $baseCoins *= WM_PERFECT_SCORE_BONUS;
            $baseCrystals *= WM_PERFECT_SCORE_BONUS;
        } elseif ($finalScore >= 90) {
            $baseCoins *= WM_HIGH_SCORE_BONUS;
            $baseCrystals *= WM_HIGH_SCORE_BONUS;
        } elseif ($finalScore >= 70) {
            $baseCoins *= WM_MID_SCORE_BONUS;
            $baseCrystals *= WM_MID_SCORE_BONUS;
        }
        
        // バフ適用
        $rewardCoins = (int)floor($baseCoins * $buffMultiplier);
        $rewardCrystals = (int)floor($baseCrystals * $buffMultiplier);
        
        // トークン報酬（確定でノーマル・レア、確率でユニーク・レジェンド）
        $normalTokens = max(1, (int)floor($correctCount / 5));
        $rareTokens = max(1, (int)floor($correctCount / 10));
        $uniqueTokens = 0;
        $legendTokens = 0;
        
        // スコアに応じてトークン量を調整
        if ($finalScore >= 100) {
            // パーフェクト：ノーマル・レア増量、確定でユニーク、高確率でレジェンド
            $normalTokens += 5;
            $rareTokens += 3;
            $uniqueTokens = 2;
            if (mt_rand(1, 100) <= WM_LEGEND_DROP_RATE_PERFECT) {
                $legendTokens = 1;
            }
        } elseif ($finalScore >= 90) {
            // 90点以上：ノーマル・レア増量、高確率でユニーク、確率でレジェンド
            $normalTokens += 3;
            $rareTokens += 2;
            if (mt_rand(1, 100) <= WM_UNIQUE_DROP_RATE_HIGH) {
                $uniqueTokens = 1;
            }
            if (mt_rand(1, 100) <= WM_LEGEND_DROP_RATE_HIGH) {
                $legendTokens = 1;
            }
        } elseif ($finalScore >= 70) {
            // 70点以上：ノーマル・レア少量増量、確率でユニーク
            $normalTokens += 2;
            $rareTokens += 1;
            if (mt_rand(1, 100) <= WM_UNIQUE_DROP_RATE_MID) {
                $uniqueTokens = 1;
            }
        }
        
        // 報酬付与
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    coins = coins + ?,
                    crystals = crystals + ?,
                    normal_tokens = normal_tokens + ?,
                    rare_tokens = rare_tokens + ?,
                    unique_tokens = unique_tokens + ?,
                    legend_tokens = legend_tokens + ?
                WHERE id = ?
            ");
            $stmt->execute([$rewardCoins, $rewardCrystals, $normalTokens, $rareTokens, $uniqueTokens, $legendTokens, $me['id']]);
            
            // 進捗記録 - 進捗テーブルがなくてもユーザーの報酬付与は行う
            // （進捗記録は補助的機能のため、失敗しても報酬付与を中断しない）
            if ($mode === 'section') {
                $sectionId = $section;
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO user_word_master_progress (user_id, section_id, level, best_score, completed)
                        VALUES (?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            best_score = GREATEST(best_score, VALUES(best_score)),
                            completed = TRUE
                    ");
                    $stmt->execute([$me['id'], $sectionId, $level, $finalScore, $finalScore >= 60 ? 1 : 0]);
                } catch (PDOException $e) {
                    // 進捗テーブルがない場合は無視（報酬付与は継続）
                }
            }
            
            $pdo->commit();
            
            // 更新後の残高取得
            $stmt = $pdo->prepare("SELECT coins, crystals FROM users WHERE id = ?");
            $stmt->execute([$me['id']]);
            $balance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'ok' => true,
                'score' => $finalScore,
                'rewards' => [
                    'coins' => $rewardCoins,
                    'crystals' => $rewardCrystals,
                    'normal_tokens' => $normalTokens,
                    'rare_tokens' => $rareTokens,
                    'unique_tokens' => $uniqueTokens,
                    'legend_tokens' => $legendTokens
                ],
                'balance' => $balance,
                'buff_applied' => $buffLevel > 0,
                'buff_bonus' => ($buffLevel * 20) . '%'
            ]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['ok' => false, 'error' => 'データベースエラーが発生しました。']);
        }
        exit;
    }
    
    echo json_encode(['ok' => false, 'error' => 'invalid_action']);
    exit;
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($gameTitle) ?> - 英単語マスター</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
* {
    box-sizing: border-box;
}

body {
    background: linear-gradient(180deg, #0a0a1a 0%, #1a1a3e 50%, #0f0f2a 100%);
    min-height: 100vh;
    min-height: 100dvh; /* Dynamic viewport height for mobile */
    margin: 0;
    overflow: hidden;
}

.game-container {
    width: 100%;
    height: 100vh;
    height: 100dvh; /* Dynamic viewport height for mobile */
    position: relative;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* ヘッダー */
.game-header {
    background: linear-gradient(180deg, rgba(212, 175, 55, 0.3) 0%, rgba(0, 0, 0, 0.5) 100%);
    padding: 15px 20px;
    padding-left: 100px; /* 終了ボタン分の余白 */
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #ffd700;
    z-index: 100;
}

.game-title {
    color: #ffd700;
    font-size: 20px;
    font-weight: bold;
    text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
}

.game-stats {
    display: flex;
    gap: 20px;
    color: #fff;
    font-size: 16px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.stat-value {
    color: #ffd700;
    font-weight: bold;
}

.combo-display {
    position: absolute;
    top: 80px;
    right: 20px;
    font-size: 24px;
    font-weight: bold;
    color: #ff6b6b;
    text-shadow: 0 0 20px rgba(255, 107, 107, 0.8);
    animation: comboGlow 0.5s ease-in-out infinite alternate;
}

@keyframes comboGlow {
    from { transform: scale(1); }
    to { transform: scale(1.1); }
}

/* ゲームエリア */
.game-area {
    flex: 1;
    position: relative;
    overflow: hidden;
}

/* 落ちてくる単語 */
.falling-word {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    font-size: 36px;
    font-weight: bold;
    color: #fff;
    text-shadow: 0 0 20px rgba(255, 215, 0, 0.8), 0 0 40px rgba(255, 215, 0, 0.4);
    padding: 20px 40px;
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.3) 0%, rgba(255, 215, 0, 0.2) 100%);
    border: 3px solid #ffd700;
    border-radius: 15px;
    animation: fallDown var(--fall-duration, 8s) linear forwards;
    z-index: 10;
}

@keyframes fallDown {
    0% { top: -100px; }
    100% { top: 100%; }
}

.falling-word.correct {
    animation: correctExplode 0.5s ease-out forwards;
}

@keyframes correctExplode {
    0% { transform: translateX(-50%) scale(1); opacity: 1; }
    50% { transform: translateX(-50%) scale(1.5); opacity: 0.8; }
    100% { transform: translateX(-50%) scale(2); opacity: 0; }
}

.falling-word.wrong {
    animation: wrongShake 0.5s ease-out forwards;
    border-color: #ff6b6b;
}

@keyframes wrongShake {
    0%, 100% { transform: translateX(-50%) rotate(0deg); }
    25% { transform: translateX(-50%) rotate(-10deg); }
    75% { transform: translateX(-50%) rotate(10deg); }
}

/* 回答エリア */
.answer-area {
    background: linear-gradient(180deg, rgba(0, 0, 0, 0.7) 0%, rgba(26, 26, 62, 0.9) 100%);
    padding: 30px 20px;
    border-top: 2px solid #ffd700;
}

/* 選択式 */
.choices-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    max-width: 800px;
    margin: 0 auto;
}

.choice-btn {
    padding: 20px;
    background: linear-gradient(135deg, #2d2d44 0%, #3d3d5c 100%);
    border: 2px solid #ffd700;
    border-radius: 12px;
    color: #fff;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.3s;
}

.choice-btn:hover {
    background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%);
    color: #1a1a2e;
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(255, 215, 0, 0.4);
}

.choice-btn.correct {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    border-color: #48bb78;
}

.choice-btn.wrong {
    background: linear-gradient(135deg, #ff6b6b 0%, #e53e3e 100%);
    border-color: #ff6b6b;
}

/* 入力式 */
.input-area {
    max-width: 600px;
    margin: 0 auto;
}

.meaning-display {
    text-align: center;
    font-size: 24px;
    color: #ffd700;
    margin-bottom: 20px;
    padding: 15px;
    background: rgba(255, 215, 0, 0.1);
    border-radius: 10px;
}

.input-field {
    width: 100%;
    padding: 20px;
    font-size: 24px;
    text-align: center;
    background: rgba(255, 255, 255, 0.1);
    border: 3px solid #ffd700;
    border-radius: 12px;
    color: #fff;
    outline: none;
    margin-bottom: 20px;
}

.input-field:focus {
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
}

/* 簡易キーボード */
.simple-keyboard {
    display: grid;
    grid-template-columns: repeat(10, 1fr);
    gap: 8px;
    max-width: 600px;
    margin: 0 auto;
}

.key-btn {
    padding: 15px 10px;
    background: linear-gradient(135deg, #2d2d44 0%, #3d3d5c 100%);
    border: 2px solid #ffd700;
    border-radius: 8px;
    color: #ffd700;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
}

.key-btn:hover {
    background: #ffd700;
    color: #1a1a2e;
}

.key-btn.special {
    grid-column: span 2;
}

/* 結果モーダル */
.result-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.95);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
    overflow-y: auto;
}

.result-modal.hidden {
    display: none;
}

.result-content {
    background: linear-gradient(135deg, #1a1a2e 0%, #2d2d44 100%);
    border: 3px solid #ffd700;
    border-radius: 20px;
    padding: 50px;
    text-align: center;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    max-height: 90dvh;
    overflow-y: auto;
    animation: resultAppear 0.5s ease-out;
}

@keyframes resultAppear {
    from { transform: scale(0.5); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.result-title {
    font-size: 36px;
    color: #ffd700;
    margin-bottom: 20px;
    text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
}

.result-score {
    font-size: 72px;
    font-weight: bold;
    color: #fff;
    margin: 20px 0;
    text-shadow: 0 0 30px rgba(255, 255, 255, 0.5);
}

.result-score-label {
    color: #a0a0c0;
    font-size: 18px;
}

.result-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin: 30px 0;
}

.result-stat {
    background: rgba(255, 215, 0, 0.1);
    padding: 15px;
    border-radius: 10px;
}

.result-stat-value {
    font-size: 28px;
    font-weight: bold;
    color: #ffd700;
}

.result-stat-label {
    color: #a0a0c0;
    font-size: 14px;
}

.result-rewards {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 170, 0, 0.2) 100%);
    border: 2px solid #ffd700;
    border-radius: 15px;
    padding: 20px;
    margin: 20px 0;
}

.result-rewards-title {
    color: #ffd700;
    font-size: 20px;
    margin-bottom: 15px;
}

.rewards-grid {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.reward-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 18px;
    color: #fff;
}

.result-buttons {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    justify-content: center;
}

.result-btn {
    padding: 15px 30px;
    border: none;
    border-radius: 12px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.result-btn.retry {
    background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%);
    color: #1a1a2e;
}

.result-btn.back {
    background: #4a5568;
    color: #fff;
}

.result-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

/* 終了ボタン */
.exit-btn {
    position: fixed;
    top: 15px;
    left: 15px;
    padding: 10px 20px;
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid #ffd700;
    border-radius: 8px;
    color: #ffd700;
    font-size: 14px;
    cursor: pointer;
    z-index: 200;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

.exit-btn:hover {
    background: #ffd700;
    color: #1a1a2e;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .game-header {
        padding: 10px 15px;
        flex-direction: column;
        gap: 10px;
    }
    
    .game-title {
        font-size: 16px;
        text-align: center;
    }
    
    .game-stats {
        gap: 10px;
        font-size: 13px;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .combo-display {
        top: 130px;
        right: 15px;
        font-size: 18px;
    }
    
    .falling-word {
        font-size: 24px;
        padding: 15px 25px;
        max-width: 90%;
        text-align: center;
        overflow-wrap: break-word;
    }
    
    .answer-area {
        padding: 15px 10px;
    }
    
    .choices-grid {
        grid-template-columns: 1fr;
        gap: 10px;
        padding: 0 5px;
    }
    
    .choice-btn {
        padding: 15px 10px;
        font-size: 15px;
        overflow-wrap: break-word;
    }
    
    .input-area {
        padding: 0 10px;
    }
    
    .meaning-display {
        font-size: 18px;
        padding: 12px;
        margin-bottom: 15px;
    }
    
    .input-field {
        font-size: 18px;
        padding: 15px;
        margin-bottom: 15px;
    }
    
    /* キーボードレイアウト: 768px以下では7列に調整（QWERTY配列を維持しつつタッチ操作しやすいサイズに） */
    .simple-keyboard {
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
    }
    
    .key-btn {
        padding: 12px 5px;
        font-size: 14px;
    }
    
    .key-btn.special {
        grid-column: span 1;
    }
    
    .exit-btn {
        position: fixed;
        top: 10px;
        left: 10px;
        padding: 8px 14px;
        font-size: 12px;
        z-index: 200;
    }
    
    .game-header {
        padding-top: 50px; /* 終了ボタン分の余白 */
    }
    
    .result-content {
        padding: 30px 20px;
        max-width: 95%;
    }
    
    .result-title {
        font-size: 24px;
        margin-bottom: 15px;
    }
    
    .result-score {
        font-size: 48px;
    }
    
    .result-stats {
        gap: 10px;
        margin: 20px 0;
    }
    
    .result-stat {
        padding: 10px;
    }
    
    .result-stat-value {
        font-size: 22px;
    }
    
    .result-stat-label {
        font-size: 12px;
    }
    
    .result-rewards {
        padding: 15px;
        margin: 15px 0;
    }
    
    .result-rewards-title {
        font-size: 16px;
        margin-bottom: 10px;
    }
    
    .rewards-grid {
        gap: 10px;
    }
    
    .reward-item {
        font-size: 14px;
    }
    
    .result-buttons {
        flex-direction: column;
        gap: 10px;
    }
    
    .result-btn {
        padding: 12px 20px;
        font-size: 16px;
        width: 100%;
    }
}

@media (max-width: 480px) {
    .game-stats {
        font-size: 11px;
        gap: 8px;
    }
    
    .falling-word {
        font-size: 20px;
        padding: 12px 18px;
    }
    
    .choice-btn {
        padding: 12px 8px;
        font-size: 14px;
    }
    
    /* キーボードレイアウト: 480px以下では6列に調整（より小さい画面でも操作可能に） */
    .simple-keyboard {
        grid-template-columns: repeat(6, 1fr);
        gap: 4px;
    }
    
    .key-btn {
        padding: 10px 4px;
        font-size: 12px;
    }
    
    .result-score {
        font-size: 36px;
    }
}
</style>
</head>
<body>
<div class="game-container">
    <button class="exit-btn" onclick="confirmExit()">← 終了</button>
    
    <div class="game-header">
        <div class="game-title"><?= htmlspecialchars($gameTitle) ?> - <?= $isInputMode ? '入力式' : '選択式' ?></div>
        <div class="game-stats">
            <div class="stat-item">
                問題: <span class="stat-value" id="questionNum">1</span> / <?= $maxQuestions ?>
            </div>
            <div class="stat-item">
                正解: <span class="stat-value" id="correctCount">0</span>
            </div>
            <div class="stat-item">
                スコア: <span class="stat-value" id="currentScore">0.000</span>
            </div>
        </div>
    </div>
    
    <div class="combo-display" id="comboDisplay" style="display: none;">
        COMBO <span id="comboCount">0</span>
    </div>
    
    <div class="game-area" id="gameArea">
        <!-- 落ちてくる単語がここに表示される -->
    </div>
    
    <div class="answer-area">
        <?php if ($isInputMode): ?>
        <!-- 入力式 -->
        <div class="input-area">
            <div class="meaning-display" id="meaningDisplay">読み込み中...</div>
            <input type="text" class="input-field" id="inputField" placeholder="英語を入力" autocomplete="off">
            <div class="simple-keyboard" id="keyboard">
                <!-- キーボードはJSで生成 -->
            </div>
        </div>
        <?php else: ?>
        <!-- 選択式 -->
        <div class="choices-grid" id="choicesGrid">
            <!-- 選択肢はJSで生成 -->
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 結果モーダル -->
<div id="resultModal" class="result-modal hidden">
    <div class="result-content">
        <div class="result-title">🏆 ゲーム終了！</div>
        <div class="result-score-label">最終スコア</div>
        <div class="result-score" id="finalScore">0.000</div>
        <div class="result-stats">
            <div class="result-stat">
                <div class="result-stat-value" id="finalCorrect">0</div>
                <div class="result-stat-label">正解数</div>
            </div>
            <div class="result-stat">
                <div class="result-stat-value" id="finalCombo">0</div>
                <div class="result-stat-label">最大コンボ</div>
            </div>
        </div>
        <div class="result-rewards">
            <div class="result-rewards-title">🎁 獲得報酬</div>
            <div class="rewards-grid" id="rewardsGrid">
                <!-- 報酬はJSで生成 -->
            </div>
        </div>
        <div class="result-buttons">
            <button class="result-btn retry" onclick="retryGame()">もう一度</button>
            <button class="result-btn back" onclick="goBack()">戻る</button>
        </div>
    </div>
</div>

<script>
const IS_INPUT_MODE = <?= $isInputMode ? 'true' : 'false' ?>;
const MAX_QUESTIONS = <?= $maxQuestions ?>;
const FALL_DURATION = 8000; // 8秒で落下

let currentQuestion = null;
let usedIds = [];
let correctCount = 0;
let incorrectCount = 0;
let combo = 0;
let maxCombo = 0;
let questionNum = 0;
let score = 0;
let gameStartTime = null;
let questionStartTime = null;
let fallAnimationId = null;

// エラーメッセージを安全に表示するヘルパー関数
function showErrorMessage(title, message, buttonText, buttonAction) {
    const gameArea = document.getElementById('gameArea');
    
    // 安全にゲームエリアをクリア
    while (gameArea.firstChild) {
        gameArea.removeChild(gameArea.firstChild);
    }
    
    const container = document.createElement('div');
    container.style.cssText = 'text-align: center; padding: 50px; color: #ffd700;';
    
    const h2 = document.createElement('h2');
    h2.textContent = String(title || '');
    container.appendChild(h2);
    
    const p = document.createElement('p');
    p.textContent = String(message || '');
    container.appendChild(p);
    
    const button = document.createElement('button');
    button.textContent = buttonText;
    button.style.cssText = 'margin-top: 20px; padding: 12px 24px; background: #ffd700; color: #1a1a2e; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;';
    button.onclick = buttonAction;
    container.appendChild(button);
    
    gameArea.appendChild(container);
}

// ゲーム開始
async function startGame() {
    gameStartTime = Date.now();
    await nextQuestion();
}

// 次の問題
async function nextQuestion() {
    if (questionNum >= MAX_QUESTIONS) {
        finishGame();
        return;
    }
    
    try {
        const res = await fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'get_question',
                used_ids: usedIds
            })
        });
        const data = await res.json();
        
        if (!data.ok) {
            if (data.reason === 'no_more_words') {
                finishGame();
            } else if (data.reason === 'no_weak_words') {
                // 苦手な単語がない場合のエラー表示
                showErrorMessage(
                    '⚠️ ' + (data.message || '苦手な単語がありません'),
                    'まず通常モードで練習してください。',
                    '戻る',
                    goBack
                );
            } else {
                // その他のエラー
                showErrorMessage(
                    '⚠️ エラーが発生しました',
                    data.message || data.error || 'データを取得できませんでした。',
                    '戻る',
                    goBack
                );
            }
            return;
        }
        
        currentQuestion = data.question;
        usedIds.push(currentQuestion.id);
        questionNum++;
        questionStartTime = Date.now();
        
        document.getElementById('questionNum').textContent = questionNum;
        
        showFallingWord();
        
        if (IS_INPUT_MODE) {
            showInputMode();
        } else {
            showChoices();
        }
        
    } catch (err) {
        console.error('問題取得エラー', err);
        // ネットワークエラー等の場合にエラー表示
        showErrorMessage(
            '⚠️ 通信エラー',
            'サーバーとの通信に失敗しました。ページを再読み込みしてください。',
            '再読み込み',
            () => location.reload()
        );
    }
}

// 落ちてくる単語を表示
function showFallingWord() {
    const gameArea = document.getElementById('gameArea');
    gameArea.innerHTML = '';
    
    const wordEl = document.createElement('div');
    wordEl.className = 'falling-word';
    wordEl.id = 'fallingWord';
    wordEl.style.setProperty('--fall-duration', (FALL_DURATION / 1000) + 's');
    
    if (IS_INPUT_MODE) {
        // 入力式: 日本語を表示
        wordEl.textContent = currentQuestion.meaning;
    } else {
        // 選択式: 英語を表示
        wordEl.textContent = currentQuestion.word;
    }
    
    gameArea.appendChild(wordEl);
    
    // 落下完了時の処理
    setTimeout(() => {
        if (currentQuestion && !wordEl.classList.contains('correct')) {
            handleWrong();
        }
    }, FALL_DURATION);
}

// 選択肢を表示
function showChoices() {
    const grid = document.getElementById('choicesGrid');
    grid.innerHTML = '';
    
    currentQuestion.choices.forEach((choice, index) => {
        const btn = document.createElement('button');
        btn.className = 'choice-btn';
        btn.textContent = choice;
        btn.onclick = () => selectChoice(choice, btn);
        grid.appendChild(btn);
    });
}

// 選択肢を選択
async function selectChoice(answer, btn) {
    if (!currentQuestion) return;
    
    const buttons = document.querySelectorAll('.choice-btn');
    buttons.forEach(b => b.disabled = true);
    
    const res = await fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'check_answer',
            word_id: currentQuestion.id,
            answer: answer
        })
    });
    const data = await res.json();
    
    if (data.correct) {
        btn.classList.add('correct');
        handleCorrect();
    } else {
        btn.classList.add('wrong');
        // 正解を表示
        buttons.forEach(b => {
            if (b.textContent === data.correct_answer) {
                b.classList.add('correct');
            }
        });
        handleWrong();
    }
    
    setTimeout(nextQuestion, 1000);
}

// 入力式の表示
function showInputMode() {
    document.getElementById('meaningDisplay').textContent = currentQuestion.meaning;
    document.getElementById('inputField').value = '';
    document.getElementById('inputField').focus();
    
    generateKeyboard();
}

// 簡易キーボード生成
function generateKeyboard() {
    const keyboard = document.getElementById('keyboard');
    keyboard.innerHTML = '';
    
    const rows = [
        ['q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p'],
        ['a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', '-'],
        ['z', 'x', 'c', 'v', 'b', 'n', 'm', '⌫', '↵']
    ];
    
    rows.forEach(row => {
        row.forEach(key => {
            const btn = document.createElement('button');
            btn.className = 'key-btn' + (key === '⌫' || key === '↵' ? ' special' : '');
            btn.textContent = key;
            btn.onclick = () => handleKeyPress(key);
            keyboard.appendChild(btn);
        });
    });
}

// キー入力処理
function handleKeyPress(key) {
    const input = document.getElementById('inputField');
    
    if (key === '⌫') {
        input.value = input.value.slice(0, -1);
    } else if (key === '↵') {
        submitInput();
    } else {
        input.value += key;
    }
    
    input.focus();
}

// 入力を送信
async function submitInput() {
    if (!currentQuestion) return;
    
    const input = document.getElementById('inputField');
    const answer = input.value.trim();
    
    if (!answer) return;
    
    input.disabled = true;
    
    const res = await fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'check_answer',
            word_id: currentQuestion.id,
            answer: answer
        })
    });
    const data = await res.json();
    
    if (data.correct) {
        input.style.borderColor = '#48bb78';
        handleCorrect();
    } else {
        input.style.borderColor = '#ff6b6b';
        input.value = data.correct_answer;
        handleWrong();
    }
    
    setTimeout(() => {
        input.disabled = false;
        input.style.borderColor = '#ffd700';
        nextQuestion();
    }, 1000);
}

// Enterキーで送信
document.getElementById('inputField')?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        submitInput();
    }
});

// 正解処理
function handleCorrect() {
    correctCount++;
    combo++;
    if (combo > maxCombo) maxCombo = combo;
    
    // スコア計算
    const responseTime = (Date.now() - questionStartTime) / 1000;
    const timeBonus = Math.max(0, 1 - (responseTime / 8)) * 20; // 早いほど高得点
    const comboBonus = Math.min(combo * 2, 20); // コンボボーナス（最大20点）
    const baseScore = 60; // 基本点
    
    score += baseScore + timeBonus + comboBonus;
    
    document.getElementById('correctCount').textContent = correctCount;
    document.getElementById('currentScore').textContent = questionNum > 0 ? (score / questionNum).toFixed(3) : '0.000';
    
    // コンボ表示
    if (combo >= 3) {
        const comboDisplay = document.getElementById('comboDisplay');
        comboDisplay.style.display = 'block';
        document.getElementById('comboCount').textContent = combo;
    }
    
    // 落下単語のアニメーション
    const wordEl = document.getElementById('fallingWord');
    if (wordEl) {
        wordEl.classList.add('correct');
    }
}

// 不正解処理
function handleWrong() {
    incorrectCount++;
    combo = 0;
    
    document.getElementById('comboDisplay').style.display = 'none';
    
    const wordEl = document.getElementById('fallingWord');
    if (wordEl) {
        wordEl.classList.add('wrong');
    }
}

// ゲーム終了
async function finishGame() {
    const finalScore = questionNum > 0 ? (score / questionNum) : 0;
    
    try {
        const res = await fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'finish_game',
                score: finalScore,
                correct_count: correctCount,
                total_questions: questionNum,
                combo_max: maxCombo
            })
        });
        const data = await res.json();
        
        if (data.ok) {
            showResults(data);
        }
    } catch (err) {
        console.error('ゲーム終了エラー', err);
    }
}

// 結果表示
function showResults(data) {
    document.getElementById('finalScore').textContent = data.score.toFixed(3);
    document.getElementById('finalCorrect').textContent = correctCount + '/' + questionNum;
    document.getElementById('finalCombo').textContent = maxCombo;
    
    const rewardsGrid = document.getElementById('rewardsGrid');
    rewardsGrid.innerHTML = `
        <div class="reward-item">🪙 ${data.rewards.coins}</div>
        <div class="reward-item">💎 ${data.rewards.crystals}</div>
        ${data.rewards.normal_tokens > 0 ? `<div class="reward-item">⚪ ノーマル×${data.rewards.normal_tokens}</div>` : ''}
        ${data.rewards.rare_tokens > 0 ? `<div class="reward-item">🟢 レア×${data.rewards.rare_tokens}</div>` : ''}
        ${data.rewards.unique_tokens > 0 ? `<div class="reward-item">🔵 ユニーク×${data.rewards.unique_tokens}</div>` : ''}
        ${data.rewards.legend_tokens > 0 ? `<div class="reward-item">🟡 レジェンド×${data.rewards.legend_tokens}</div>` : ''}
        ${data.buff_applied ? `<div class="reward-item" style="color: #ff6b6b;">🔥 バフ+${data.buff_bonus}</div>` : ''}
    `;
    
    document.getElementById('resultModal').classList.remove('hidden');
}

function retryGame() {
    location.reload();
}

function goBack() {
    location.href = 'word_master.php';
}

function confirmExit() {
    if (confirm('ゲームを終了しますか？\n進行中のスコアは保存されません。')) {
        goBack();
    }
}

// ゲーム開始
startGame();
</script>
</body>
</html>
