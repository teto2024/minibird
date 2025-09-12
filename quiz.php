<?php
session_start();
require_once __DIR__ . '/config.php'; // db() と $me を提供

// ====== 設定 ======
$REWARD_PER_CORRECT_COIN   = 10;
$REWARD_PER_CORRECT_CRYSTAL = 1;
$NO_CORRECT_RATE           = 15; // 「この中にはない」問題の出題率
$MAX_QUESTIONS             = 20;
// ==================

$mode  = $_GET['mode'] ?? 'stage';
$stage = isset($_GET['stage']) ? (int)$_GET['stage'] : 1;
$isWeakMode = ($mode === 'weak');

$stageRanges = [
    1 => [1, 600],
    2 => [601, 1200],
    3 => [1201, 1700],
    4 => [1701, 2027]
];

if ($mode === 'stage' && !isset($stageRanges[$stage])) {
    die('不正なステージです。');
}

$prefix = $isWeakMode ? 'weak' : "stage{$stage}";

// ====== セッション初期化 ======
$_SESSION["{$prefix}_used"]     = $_SESSION["{$prefix}_used"] ?? [];
$_SESSION["{$prefix}_score"]    = $_SESSION["{$prefix}_score"] ?? 0;
$_SESSION["{$prefix}_finished"] = $_SESSION["{$prefix}_finished"] ?? false;
$_SESSION["{$prefix}_current"]  = $_SESSION["{$prefix}_current"] ?? null;

$uid = $me['id'] ?? $_SESSION['uid'] ?? null;

// ====== 再挑戦リセット ======
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    unset(
        $_SESSION["{$prefix}_used"],
        $_SESSION["{$prefix}_score"],
        $_SESSION["{$prefix}_finished"],
        $_SESSION["{$prefix}_current"]
    );
    if(isset($_GET['ajax'])){
        echo json_encode(['ok'=>true]);
        exit;
    } else {
        $url = $isWeakMode ? "?mode=weak" : "?stage={$stage}";
        header("Location: $url");
        exit;
    }
}

// ====== POST(回答) ハンドラ ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer'])) {
    header('Content-Type: application/json; charset=UTF-8');
    // POSTのstageを優先して$stageを上書き
    $stage = isset($_POST['stage']) ? (int)$_POST['stage'] : $stage;
    $prefix = $isWeakMode ? 'weak' : "stage{$stage}";
    $current = $_SESSION["{$prefix}_current"] ?? null;
    if (!$current) {
        echo json_encode(['ok'=>false, 'reason'=>'no-current-question']);
        exit;
    }
    if (!empty($current['done'])) {
        echo json_encode(['ok'=>false, 'reason'=>'already-answered']);
        exit;
    }

    $selected = (string)$_POST['answer'];
    $correctAnswer = (string)$current['correct'];
    $isCorrect = ($selected === $correctAnswer);

    if ($isCorrect) $_SESSION["{$prefix}_score"]++;

    // ====== user_word_stats 更新 ======
    if ($uid && !empty($current['word_id'])) {
        $sql = "INSERT INTO user_word_stats(user_id, word_id, correct_count, incorrect_count, last_attempt)
                VALUES(?,?,?,?,NOW())
                ON DUPLICATE KEY UPDATE 
                    correct_count = correct_count + VALUES(correct_count),
                    incorrect_count = incorrect_count + VALUES(incorrect_count),
                    last_attempt = NOW()";
        $stmt = db()->prepare($sql);
        $stmt->execute([
            $uid,
            $current['word_id'],
            $isCorrect ? 1 : 0,
            $isCorrect ? 0 : 1
        ]);
    }

    $_SESSION["{$prefix}_current"]['done'] = true;

    echo json_encode([
        'ok' => true,
        'correct' => $isCorrect,
        'correctAnswer' => $correctAnswer,
        'score' => $_SESSION["{$prefix}_score"],
        'usedCount' => count($_SESSION["{$prefix}_used"])
    ]);
    exit;
}

try {
    $pdo = db();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ====== 現在の問題を返す AJAX ======
    if(isset($_GET['ajax'])){
        // Ajaxでステージ指定があれば更新
        if(isset($_GET['stage'])){
            $stage = (int)$_GET['stage'];
            if(!isset($stageRanges[$stage])) die(json_encode(['ok'=>false,'reason'=>'invalid-stage']));
            $prefix = $isWeakMode ? 'weak' : "stage{$stage}";
            // セッション初期化（未使用の場合のみ）
            $_SESSION["{$prefix}_used"]     = $_SESSION["{$prefix}_used"] ?? [];
            $_SESSION["{$prefix}_score"]    = $_SESSION["{$prefix}_score"] ?? 0;
            $_SESSION["{$prefix}_finished"] = $_SESSION["{$prefix}_finished"] ?? false;
            $_SESSION["{$prefix}_current"]  = $_SESSION["{$prefix}_current"] ?? null;
        }

        if($_SESSION["{$prefix}_finished"]){
            echo json_encode(['ok'=>false,'reason'=>'finished']);
            exit;
        }

        // 現在の問題がなければ新しい問題を生成
        if(empty($_SESSION["{$prefix}_current"]) || $_SESSION["{$prefix}_current"]['done']){
            $_SESSION["{$prefix}_current"] = null;
        }

        // ====== 新しい問題の生成 ======
        if(empty($_SESSION["{$prefix}_current"])){

            // ====== 終了判定 ======
            if (count($_SESSION["{$prefix}_used"]) >= $MAX_QUESTIONS) {
                $_SESSION["{$prefix}_finished"] = true;

                // 報酬計算
                $correct = (int)$_SESSION["{$prefix}_score"];
                $rewardCoin    = $correct * $REWARD_PER_CORRECT_COIN;
                $rewardCrystal = $correct * $REWARD_PER_CORRECT_CRYSTAL;
                if ($correct === $MAX_QUESTIONS) { 
                    $rewardCoin *= 2; 
                    $rewardCrystal *= 2; 
                }

                // DB更新
                if ($uid) {
                    try {
                        $pdo->beginTransaction();
                        $stmt = $pdo->prepare("UPDATE users SET coins = coins + ?, crystals = crystals + ? WHERE id = ?");
                        $stmt->execute([$rewardCoin, $rewardCrystal, $uid]);

                        $kind = $isWeakMode ? 'quiz_weak_reward' : 'quiz_stage_reward';
                        $stmt2 = $pdo->prepare(
                            "INSERT INTO reward_events(user_id, kind, amount, meta) VALUES (?, ?, ?, JSON_OBJECT('mode', ?, 'stage', ?, 'correct', ?))"
                        );
                        $stmt2->execute([$uid, $kind, $rewardCoin, $mode, $stage, $correct]);

                        $pdo->commit();
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        error_log("Quiz reward error: " . $e->getMessage());
                    }
                }

                $_SESSION["{$prefix}_lastReward"] = ['coin'=>$rewardCoin, 'crystal'=>$rewardCrystal];

                echo json_encode([
                    'ok'=>false,
                    'reason'=>'finished',
                    'score'=>$correct,
                    'reward'=>$_SESSION["{$prefix}_lastReward"]
                ]);
                exit;
            }

            // ====== 新しい問題取得 ======
            if ($isWeakMode) {
                $placeholders = !empty($_SESSION["{$prefix}_used"]) ? str_repeat('?,', count($_SESSION["{$prefix}_used"])) : '';
                $placeholders = rtrim($placeholders, ',');

                $sql = "SELECT ew.id, ew.word, ew.meaning
                        FROM english_words ew
                        JOIN user_word_stats uws ON ew.id = uws.word_id
                        WHERE uws.user_id = ? AND uws.incorrect_count > uws.correct_count";
                $params = [$uid];
                if (!empty($placeholders)) $sql .= " AND ew.id NOT IN ($placeholders)";
                $sql .= " ORDER BY (uws.incorrect_count - uws.correct_count) DESC, uws.last_attempt ASC LIMIT 1";
                if (!empty($_SESSION["{$prefix}_used"])) $params = array_merge($params, $_SESSION["{$prefix}_used"]);

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $word = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$word) die(json_encode(['ok'=>false,'reason'=>'no-weak-word']));
            } else {
                list($minId, $maxId) = $stageRanges[$stage];
                $placeholders = !empty($_SESSION["{$prefix}_used"]) ? str_repeat('?,', count($_SESSION["{$prefix}_used"])) : '';
                $placeholders = rtrim($placeholders, ',');

                $sql = "SELECT id, word, meaning FROM english_words WHERE id BETWEEN ? AND ?";
                if (!empty($placeholders)) $sql .= " AND id NOT IN ($placeholders)";
                $sql .= " ORDER BY RAND() LIMIT 1";

                $params = [$minId, $maxId];
                if (!empty($_SESSION["{$prefix}_used"])) $params = array_merge($params, $_SESSION["{$prefix}_used"]);

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $word = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if (!$word) die(json_encode(['ok'=>false,'reason'=>'no-word']));

            $_SESSION["{$prefix}_used"][] = (int)$word['id'];

            // ====== 選択肢生成 ======
            $forceNoCorrect = (mt_rand(1,100) <= $NO_CORRECT_RATE);
            if ($forceNoCorrect) {
                $stmt2 = $pdo->prepare("SELECT meaning FROM english_words WHERE id != ? ORDER BY RAND() LIMIT 4");
                $stmt2->execute([$word['id']]);
                $choices = $stmt2->fetchAll(PDO::FETCH_COLUMN);
                $correctAnswer = "この中にはない";
            } else {
                $stmt2 = $pdo->prepare("SELECT meaning FROM english_words WHERE id != ? ORDER BY RAND() LIMIT 3");
                $stmt2->execute([$word['id']]);
                $choices = $stmt2->fetchAll(PDO::FETCH_COLUMN);
                $choices[] = $word['meaning'];
                $correctAnswer = $word['meaning'];
            }

            if (!in_array("この中にはない", $choices)) $choices[] = "この中にはない";
            shuffle($choices);

            $_SESSION["{$prefix}_current"] = [
                'word_id' => (int)$word['id'],
                'word' => $word['word'],
                'correct' => (string)$correctAnswer,
                'choices' => $choices,
                'done'    => false,
                'ts'      => time()
            ];
        }

        $current = $_SESSION["{$prefix}_current"];
        echo json_encode([
            'ok'=>true,
            'stage'=>$stage,
            'score'=>$_SESSION["{$prefix}_score"],
            'usedCount'=>count($_SESSION["{$prefix}_used"]),
            'maxQuestions'=>$MAX_QUESTIONS,
            'word'=>['word'=>$current['word']],
            'choices'=>$current['choices']
        ]);
        exit;
    }

    // ====== ページアクセス時の終了処理（HTML表示用） ======
    if (count($_SESSION["{$prefix}_used"]) >= $MAX_QUESTIONS && !$_SESSION["{$prefix}_finished"]) {
        $_SESSION["{$prefix}_finished"] = true;

        $correct = (int)$_SESSION["{$prefix}_score"];
        $rewardCoin    = $correct * $REWARD_PER_CORRECT_COIN;
        $rewardCrystal = $correct * $REWARD_PER_CORRECT_CRYSTAL;
        if ($correct === $MAX_QUESTIONS) { 
            $rewardCoin *= 2; 
            $rewardCrystal *= 2; 
        }

        if ($uid) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE users SET coins = coins + ?, crystals = crystals + ? WHERE id = ?");
                $stmt->execute([$rewardCoin, $rewardCrystal, $uid]);

                $kind = $isWeakMode ? 'quiz_weak_reward' : 'quiz_stage_reward';
                $stmt2 = $pdo->prepare(
                    "INSERT INTO reward_events(user_id, kind, amount, meta) VALUES (?, ?, ?, JSON_OBJECT('mode', ?, 'stage', ?, 'correct', ?))"
                );
                $stmt2->execute([$uid, $kind, $rewardCoin, $mode, $stage, $correct]);

                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Quiz reward error: " . $e->getMessage());
            }
        }

        $_SESSION["{$prefix}_lastReward"] = ['coin'=>$rewardCoin, 'crystal'=>$rewardCrystal];

        $last = $_SESSION["{$prefix}_lastReward"];
        $title = $isWeakMode ? "苦手モード終了！" : "ステージ{$stage}終了！";

        echo "<h2>{$title}</h2>";
        echo "<p>正答数: {$correct}/{$MAX_QUESTIONS}</p>";
        echo "<p>獲得報酬: ".(int)$last['coin']." コイン, ".(int)$last['crystal']." クリスタル</p>";
        $retry = $isWeakMode ? "?mode=weak&reset=1" : "?stage={$stage}&reset=1";
        echo "<a href='{$retry}'>もう一度挑戦</a>";
        exit;
    }

} catch (PDOException $e) {
    die('DB接続エラー: '.$e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>英単語ゲーム - ステージ<?php echo $stage; ?></title>
<style>
body { font-family:'Arial',sans-serif; background:#0f1419; color:#e6e6e6; padding:30px;}
h1 { margin:0 0 8px; color:#1d9bf0; text-shadow:0 0 5px #1d9bf0; }
h2 { margin:8px 0 20px; color:#9bd1ff; }
.card { background:#15202b; padding:24px; border-radius:12px; box-shadow:0 0 12px rgba(29,155,240,.3); max-width:680px; margin:auto; }
#stageSelect { margin-bottom:20px; }
.select { background:#0f1419; color:#e6e6e6; border:1px solid #1d9bf0; padding:8px 10px; border-radius:8px; }
.word { font-size:28px; letter-spacing:.5px; margin:8px 0 18px; text-align:center; }
.btn { display:block; margin:10px 0; padding:12px 20px; width:100%; font-size:16px; color:#fff;
    background:linear-gradient(90deg,#ff00c8,#1d9bf0); border:none; border-radius:10px; cursor:pointer;
    transition:transform .15s ease, box-shadow .15s ease; box-shadow:0 0 10px #1d9bf0; text-align:center; }
.btn:hover:not(:disabled) { transform:translateY(-1px); box-shadow:0 0 20px #ff00c8; }
.btn:disabled { opacity:.6; cursor:not-allowed; }
.correct { background:#00c97b !important; box-shadow:0 0 15px #00c97b; }
.wrong { background:#ff375f !important; box-shadow:0 0 15px #ff375f; }
.meta { margin-top:8px; font-size:14px; opacity:.85; text-align:center; }
#modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.8); z-index:9999; align-items:center; justify-content:center;}
#modal .inner { background:#15202b; padding:24px 28px; border-radius:14px; text-align:center; min-width:280px; box-shadow:0 0 16px rgba(255,0,200,.25); cursor:pointer; }
#modal .title { font-size:20px; margin-bottom:8px; }
#modal .answer { margin-top:6px; font-size:16px; opacity:.9; }
.stage-link { display:inline-block; background:#e67e22; color:#fff; padding:10px 16px; border-radius:8px; text-decoration:none; font-weight:bold; margin-bottom:20px; }
</style>
</head>
<body>

<div class="card" id="gameCard">
    <h1>英単語ゲーム</h1>

    <form id="stageSelect" method="get">
        <label for="stage">ステージ選択: </label>
        <select class="select" name="stage" id="stage" onchange="loadStage(this.value)">
            <?php foreach($stageRanges as $s => $range): ?>
                <option value="<?php echo $s ?>" <?php if($s==$stage) echo 'selected' ?>>
                    ステージ<?php echo $s ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <a href="quiz.php?mode=weak" class="stage-link" id="weakModeLink">⚡ 苦手モード</a>

    <h2 id="status">ステージ<?php echo $stage; ?>｜問題 <?php echo count($_SESSION["stage{$stage}_used"]); ?>/<?php echo $MAX_QUESTIONS; ?> ｜ 正答 <?php echo (int)$_SESSION["stage{$stage}_score"]; ?></h2>

    <div class="word" id="word"><?php echo htmlspecialchars($word['word'], ENT_QUOTES, 'UTF-8'); ?></div>

    <div id="choices">
    <?php foreach ($choices as $choice): ?>
        <button class="btn choice" type="button" data-value="<?php echo htmlspecialchars($choice, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($choice, ENT_QUOTES, 'UTF-8'); ?>
        </button>
    <?php endforeach; ?>
    </div>

    <div class="meta">※ まれに「この中にはない」が正解の問題が出ます。</div>
    <div style="margin-top:20px; text-align:right;">
        <a href="#" id="resetStage" style="color:#1d9bf0; text-decoration:none; font-size:14px;">🔄 このステージを最初からやり直す</a>
    </div>
</div>

<div id="modal">
  <div class="inner">
    <div class="title" id="modalTitle">判定中…</div>
    <div class="answer" id="modalAnswer"></div>
  </div>
</div>

<script>
(() => {
    const modal = document.getElementById('modal');
    const modalTitle = document.getElementById('modalTitle');
    const modalAnswer = document.getElementById('modalAnswer');
    const wordEl = document.getElementById('word');
    const choicesEl = document.getElementById('choices');
    const statusEl = document.getElementById('status');
    const resetEl = document.getElementById('resetStage');
    const stageSelect = document.getElementById('stage');

    function showModal(title, answer='') {
        modalTitle.textContent = title;
        modalAnswer.textContent = answer;
        modal.style.display = 'flex';
    }

    function hideModal() { modal.style.display = 'none'; }
    modal.addEventListener('click', hideModal);

    async function sendAnswer(value) {
    document.querySelectorAll('.choice').forEach(b => b.disabled = true);
    try {
        const res = await fetch(location.href, {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
            body: new URLSearchParams({
                answer: value,
                stage: stageSelect.value   // ここを追加
            }),
            credentials:'same-origin'
        });
        const data = await res.json();
        if(!data.ok){ showModal('エラー','再読み込みしてください'); return; }

        const btn = document.querySelector(`.choice[data-value="${value}"]`);
        if(data.correct){
            btn.classList.add('correct');
            showModal('正解！');
        } else {
            btn.classList.add('wrong');
            showModal('不正解！','正解は：'+(data.correctAnswer??'取得不可'));
        }
        setTimeout(loadNext, 1000);
    } catch(e){ showModal('通信エラー','ネットワークを確認してください'); }
}


    async function loadNext(){
        try {
            const res = await fetch(location.href+'?ajax=1&stage='+stageSelect.value,{
                method:'GET', credentials:'same-origin'
            });
            const data = await res.json();
            if(!data.ok){ showModal('エラー','再読み込みしてください'); return; }

            wordEl.textContent = data.word.word;
            choicesEl.innerHTML = '';
            data.choices.forEach(c=>{
                const b = document.createElement('button');
                b.className='btn choice';
                b.type='button';
                b.dataset.value=c;
                b.textContent=c;
                b.addEventListener('click',()=>sendAnswer(c));
                choicesEl.appendChild(b);
            });
            statusEl.textContent = `ステージ${data.stage}｜問題 ${data.usedCount}/${data.maxQuestions} ｜ 正答 ${data.score}`;
            hideModal();
        } catch(e){ showModal('通信エラー','次の問題を取得できません'); }
    }

    function loadStage(stage){
        stageSelect.value = stage;
        loadNext();
    }

    resetEl.addEventListener('click', async (e)=>{
        e.preventDefault();
        if(!confirm('このステージを最初からやり直しますか？')) return;
        try {
            const res = await fetch(location.href+'?reset=1&ajax=1&stage='+stageSelect.value,{method:'GET',credentials:'same-origin'});
            const data = await res.json();
            if(!data.ok){ showModal('エラー','再読み込みしてください'); return; }
            loadNext();
        } catch(e){ showModal('通信エラー','リセットできません'); }
    });

    document.querySelectorAll('.choice').forEach(b=>{ b.addEventListener('click',()=>sendAnswer(b.dataset.value)); });
    // ====== ここでグローバルに公開 ======
    window.loadStage = function(stage){
        stageSelect.value = stage;
        loadNext();
    }
})();
</script>
</body>
</html>