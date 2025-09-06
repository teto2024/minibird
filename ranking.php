<?php
require 'config.php';
$pdo = db();

// Ajaxリクエストの場合（JSONを返す）
if (isset($_GET['ajax']) && isset($_GET['type'])) {
    $type = $_GET['type'];
    $sql = "";

    switch ($type) {
        case 'total': // 累計合計
            $sql = "SELECT COALESCE(NULLIF(u.display_name,''), u.handle) AS name, SUM(f.minutes) AS score
                    FROM focus_tasks f
                    JOIN users u ON u.id = f.user_id
                    WHERE f.status='success'
                    GROUP BY f.user_id
                    ORDER BY score DESC LIMIT 10";
            break;
        case 'day': // 直近1日合計
            $sql = "SELECT COALESCE(NULLIF(u.display_name,''), u.handle) AS name, SUM(f.minutes) AS score
                    FROM focus_tasks f
                    JOIN users u ON u.id = f.user_id
                    WHERE f.status='success' AND f.started_at >= NOW() - INTERVAL 1 DAY
                    GROUP BY f.user_id
                    ORDER BY score DESC LIMIT 10";
            break;
        case 'week': // 直近1週間合計
            $sql = "SELECT COALESCE(NULLIF(u.display_name,''), u.handle) AS name, SUM(f.minutes) AS score
                    FROM focus_tasks f
                    JOIN users u ON u.id = f.user_id
                    WHERE f.status='success' AND f.started_at >= NOW() - INTERVAL 7 DAY
                    GROUP BY f.user_id
                    ORDER BY score DESC LIMIT 10";
            break;
        case 'max_total': // 累計最長
            $sql = "SELECT COALESCE(NULLIF(u.display_name,''), u.handle) AS name, MAX(f.minutes) AS score
                    FROM focus_tasks f
                    JOIN users u ON u.id = f.user_id
                    WHERE f.status='success'
                    GROUP BY f.user_id
                    ORDER BY score DESC LIMIT 10";
            break;
        case 'max_day': // 直近1日最長
            $sql = "SELECT COALESCE(NULLIF(u.display_name,''), u.handle) AS name, MAX(f.minutes) AS score
                    FROM focus_tasks f
                    JOIN users u ON u.id = f.user_id
                    WHERE f.status='success' AND f.started_at >= NOW() - INTERVAL 1 DAY
                    GROUP BY f.user_id
                    ORDER BY score DESC LIMIT 10";
            break;
        case 'max_week': // 直近1週間最長
            $sql = "SELECT COALESCE(NULLIF(u.display_name,''), u.handle) AS name, MAX(f.minutes) AS score
                    FROM focus_tasks f
                    JOIN users u ON u.id = f.user_id
                    WHERE f.status='success' AND f.started_at >= NOW() - INTERVAL 7 DAY
                    GROUP BY f.user_id
                    ORDER BY score DESC LIMIT 10";
            break;
    }

    $st = $pdo->query($sql);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($rows);
    exit;
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>ランキング - MiniBird</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
    body { background-color: #f8f9fa; }
    .ranking-container { max-width: 800px; margin: 40px auto; }
    .nav-tabs .nav-link { cursor: pointer; }
</style>
</head>
<body>
<div class="ranking-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>集中タスクランキング</h1>
        <a href="index.php" class="btn btn-secondary">← 戻る</a>
    </div>

    <!-- タブ -->
    <ul class="nav nav-tabs" id="rankingTabs">
        <li class="nav-item"><a class="nav-link active" data-type="total">累計合計</a></li>
        <li class="nav-item"><a class="nav-link" data-type="day">直近1日合計</a></li>
        <li class="nav-item"><a class="nav-link" data-type="week">直近1週間合計</a></li>
        <li class="nav-item"><a class="nav-link" data-type="max_total">累計最長</a></li>
        <li class="nav-item"><a class="nav-link" data-type="max_day">直近1日最長</a></li>
        <li class="nav-item"><a class="nav-link" data-type="max_week">直近1週間最長</a></li>
    </ul>

    <!-- ランキング結果 -->
    <div class="mt-3">
        <table class="table table-striped">
            <thead><tr><th>#</th><th>ユーザー</th><th>時間(分)</th></tr></thead>
            <tbody id="rankingBody"></tbody>
        </table>
    </div>
</div>

<script>
function loadRanking(type) {
    $.getJSON("ranking.php", {ajax:1, type:type}, function(data){
        let html = "";
        data.forEach((row, i) => {
            html += `<tr><td>${i+1}</td><td>${row.name}</td><td>${row.score}</td></tr>`;
        });
        $("#rankingBody").html(html);
    });
}

$(function(){
    // 初期ロード（累計合計）
    loadRanking("total");

    // タブ切り替え
    $("#rankingTabs .nav-link").click(function(){
        $("#rankingTabs .nav-link").removeClass("active");
        $(this).addClass("active");
        loadRanking($(this).data("type"));
    });
});
</script>
</body>
</html>
