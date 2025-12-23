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
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ランキング - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
    body { 
        background-color: var(--bg);
        margin: 0;
        padding: 0;
    }
    
    .ranking-container { 
        max-width: 900px; 
        margin: 0 auto; 
        padding: 20px;
    }
    
    .ranking-header {
        background: var(--card);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .ranking-header h1 {
        margin: 0;
        font-size: 28px;
        color: var(--text);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .ranking-header h1::before {
        content: '🏆';
        font-size: 32px;
    }
    
    .back-btn {
        padding: 10px 20px;
        background: var(--bg);
        color: var(--text);
        border: 1px solid var(--border);
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .back-btn:hover {
        background: var(--border);
        transform: translateY(-2px);
    }
    
    .ranking-tabs {
        background: var(--card);
        border-radius: 12px;
        padding: 8px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .ranking-tab {
        flex: 1;
        min-width: 120px;
        padding: 12px 16px;
        background: var(--bg);
        color: var(--muted);
        border: 1px solid var(--border);
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .ranking-tab:hover {
        background: var(--border);
        color: var(--text);
    }
    
    .ranking-tab.active {
        background: var(--blue);
        color: white;
        border-color: var(--blue);
        box-shadow: 0 2px 8px rgba(29, 155, 240, 0.3);
    }
    
    .ranking-content {
        background: var(--card);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    
    .ranking-item {
        display: flex;
        align-items: center;
        padding: 16px;
        margin-bottom: 12px;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 10px;
        transition: all 0.2s;
    }
    
    .ranking-item:hover {
        transform: translateX(4px);
        border-color: var(--blue);
        box-shadow: 0 4px 12px rgba(29, 155, 240, 0.2);
    }
    
    .ranking-position {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: bold;
        margin-right: 16px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    
    .ranking-position.gold {
        background: linear-gradient(135deg, #ffd700, #ffed4e);
        color: #000;
        box-shadow: 0 2px 8px rgba(255, 215, 0, 0.4);
    }
    
    .ranking-position.silver {
        background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
        color: #000;
        box-shadow: 0 2px 8px rgba(192, 192, 192, 0.4);
    }
    
    .ranking-position.bronze {
        background: linear-gradient(135deg, #cd7f32, #e9a968);
        color: #000;
        box-shadow: 0 2px 8px rgba(205, 127, 50, 0.4);
    }
    
    .ranking-position.normal {
        background: var(--border);
        color: var(--text);
    }
    
    .ranking-user {
        flex: 1;
        font-size: 16px;
        font-weight: 600;
        color: var(--text);
    }
    
    .ranking-score {
        font-size: 24px;
        font-weight: bold;
        color: var(--blue);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .ranking-score::after {
        content: '分';
        font-size: 14px;
        color: var(--muted);
        font-weight: normal;
    }
    
    .no-data {
        text-align: center;
        padding: 40px;
        color: var(--muted);
        font-size: 16px;
    }
    
    .loading {
        text-align: center;
        padding: 40px;
        color: var(--muted);
    }
    
    .loading::after {
        content: '...';
        animation: dots 1.5s infinite;
        display: inline-block;
    }
    
    @keyframes dots {
        0%, 20% { opacity: 0; }
        40% { opacity: 0.5; }
        60%, 100% { opacity: 1; }
    }
    
    @media (max-width: 768px) {
        .ranking-container {
            padding: 12px;
        }
        
        .ranking-header {
            padding: 16px;
        }
        
        .ranking-header h1 {
            font-size: 22px;
        }
        
        .ranking-tab {
            min-width: 100px;
            padding: 10px 12px;
            font-size: 13px;
        }
        
        .ranking-item {
            padding: 12px;
        }
        
        .ranking-position {
            width: 40px;
            height: 40px;
            font-size: 16px;
        }
        
        .ranking-user {
            font-size: 14px;
        }
        
        .ranking-score {
            font-size: 20px;
        }
    }
</style>
</head>
<body>
<div class="ranking-container">
    <div class="ranking-header">
        <h1>集中タスクランキング</h1>
        <a href="index.php" class="back-btn">← 戻る</a>
    </div>

    <!-- タブ -->
    <div class="ranking-tabs">
        <div class="ranking-tab active" data-type="total">累計合計</div>
        <div class="ranking-tab" data-type="day">直近1日</div>
        <div class="ranking-tab" data-type="week">直近1週間</div>
        <div class="ranking-tab" data-type="max_total">累計最長</div>
        <div class="ranking-tab" data-type="max_day">1日最長</div>
        <div class="ranking-tab" data-type="max_week">週間最長</div>
    </div>

    <!-- ランキング結果 -->
    <div class="ranking-content">
        <div id="rankingBody" class="loading">読み込み中</div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function loadRanking(type) {
    const body = $("#rankingBody");
    body.html('<div class="loading">読み込み中</div>');
    
    $.getJSON("ranking.php", {ajax:1, type:type}, function(data){
        if (!data || data.length === 0) {
            body.html('<div class="no-data">データがありません</div>');
            return;
        }
        
        let html = "";
        data.forEach((row, i) => {
            const position = i + 1;
            let positionClass = 'normal';
            if (position === 1) positionClass = 'gold';
            else if (position === 2) positionClass = 'silver';
            else if (position === 3) positionClass = 'bronze';
            
            html += `
                <div class="ranking-item">
                    <div class="ranking-position ${positionClass}">${position}</div>
                    <div class="ranking-user">${row.name}</div>
                    <div class="ranking-score">${row.score}</div>
                </div>
            `;
        });
        body.html(html);
    }).fail(function() {
        body.html('<div class="no-data">データの読み込みに失敗しました</div>');
    });
}

$(function(){
    // 初期ロード（累計合計）
    loadRanking("total");

    // タブ切り替え
    $(".ranking-tab").click(function(){
        $(".ranking-tab").removeClass("active");
        $(this).addClass("active");
        loadRanking($(this).data("type"));
    });
});
</script>
</body>
</html>
