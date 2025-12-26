<?php
// ===============================================
// focus_analytics.php
// 集中タイマー記録分析機能
// ===============================================

require_once __DIR__ . '/config.php';

$me = user();
if (!$me) {
    header('Location: ./login.php');
    exit;
}

$pdo = db();
$uid = $_SESSION['uid'];

// 各種統計データを取得

// 1. 日別の集中時間（直近30日）
$daily_stats = [];
$st = $pdo->prepare("
    SELECT 
        DATE(started_at) as date,
        SUM(minutes) as total_minutes,
        COUNT(*) as session_count,
        SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
        SUM(CASE WHEN status = 'fail' THEN 1 ELSE 0 END) as fail_count,
        SUM(coins) as total_coins,
        SUM(crystals) as total_crystals
    FROM focus_tasks
    WHERE user_id = ? AND started_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(started_at)
    ORDER BY date DESC
");
$st->execute([$uid]);
$daily_stats = $st->fetchAll(PDO::FETCH_ASSOC);

// 2. 週別の集中時間（直近12週）
$st = $pdo->prepare("
    SELECT 
        YEARWEEK(started_at, 1) as yearweek,
        MIN(DATE(started_at)) as week_start,
        SUM(minutes) as total_minutes,
        COUNT(*) as session_count,
        SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count
    FROM focus_tasks
    WHERE user_id = ? AND started_at >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
    GROUP BY YEARWEEK(started_at, 1)
    ORDER BY yearweek DESC
");
$st->execute([$uid]);
$weekly_stats = $st->fetchAll(PDO::FETCH_ASSOC);

// 3. 時間帯別の集中傾向
$st = $pdo->prepare("
    SELECT 
        HOUR(started_at) as hour,
        COUNT(*) as session_count,
        SUM(minutes) as total_minutes,
        AVG(minutes) as avg_minutes,
        SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count
    FROM focus_tasks
    WHERE user_id = ?
    GROUP BY HOUR(started_at)
    ORDER BY hour
");
$st->execute([$uid]);
$hourly_stats = $st->fetchAll(PDO::FETCH_ASSOC);

// 4. タスク別の統計
$st = $pdo->prepare("
    SELECT 
        task,
        COUNT(*) as session_count,
        SUM(minutes) as total_minutes,
        AVG(minutes) as avg_minutes,
        SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
        SUM(coins) as total_coins,
        SUM(crystals) as total_crystals
    FROM focus_tasks
    WHERE user_id = ?
    GROUP BY task
    ORDER BY total_minutes DESC
    LIMIT 10
");
$st->execute([$uid]);
$task_stats = $st->fetchAll(PDO::FETCH_ASSOC);

// 5. 総合統計
$st = $pdo->prepare("
    SELECT 
        COUNT(*) as total_sessions,
        SUM(minutes) as total_minutes,
        AVG(minutes) as avg_minutes,
        SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
        SUM(CASE WHEN status = 'fail' THEN 1 ELSE 0 END) as fail_count,
        SUM(coins) as total_coins,
        SUM(crystals) as total_crystals,
        MAX(minutes) as max_session
    FROM focus_tasks
    WHERE user_id = ?
");
$st->execute([$uid]);
$overall_stats = $st->fetch(PDO::FETCH_ASSOC);

// 6. 連続記録
$st = $pdo->prepare("SELECT * FROM focus_statistics WHERE user_id = ?");
$st->execute([$uid]);
$streak_stats = $st->fetch(PDO::FETCH_ASSOC);

// JSON形式でチャートデータを準備
$chart_daily = array_reverse($daily_stats);
$chart_weekly = array_reverse($weekly_stats);

// 時間帯別データを0-23時間で埋める
$hourly_filled = [];
for ($h = 0; $h < 24; $h++) {
    $found = false;
    foreach ($hourly_stats as $stat) {
        if ((int)$stat['hour'] === $h) {
            $hourly_filled[] = [
                'hour' => $h,
                'session_count' => (int)$stat['session_count'],
                'total_minutes' => (int)$stat['total_minutes'],
                'avg_minutes' => round((float)$stat['avg_minutes'], 1),
                'success_rate' => $stat['session_count'] > 0 ? round(($stat['success_count'] / $stat['session_count']) * 100, 1) : 0
            ];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $hourly_filled[] = ['hour' => $h, 'session_count' => 0, 'total_minutes' => 0, 'avg_minutes' => 0, 'success_rate' => 0];
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>集中タイマー分析 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body {
    background: linear-gradient(135deg, #0d0d0d 0%, #1a1a2e 50%, #16213e 100%);
    min-height: 100vh;
    color: #fff;
}

.analytics-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.analytics-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.analytics-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
}

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 20px;
    background: rgba(255,255,255,0.1);
    color: #667eea;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
}

.back-link:hover {
    background: #667eea;
    color: white;
}

/* 統計カード */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, rgba(30, 30, 50, 0.95) 0%, rgba(20, 20, 35, 0.95) 100%);
    border-radius: 16px;
    padding: 24px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.stat-card .icon {
    font-size: 36px;
    margin-bottom: 10px;
}

.stat-card .value {
    font-size: 32px;
    font-weight: bold;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-card .label {
    color: #a0a0c0;
    font-size: 14px;
    margin-top: 5px;
}

/* チャートセクション */
.chart-section {
    background: linear-gradient(135deg, rgba(30, 30, 50, 0.95) 0%, rgba(20, 20, 35, 0.95) 100%);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.chart-section h3 {
    margin: 0 0 20px 0;
    color: #fff;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.chart-container {
    position: relative;
    height: 300px;
}

/* タスク別統計テーブル */
.task-table {
    width: 100%;
    border-collapse: collapse;
}

.task-table th,
.task-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.task-table th {
    color: #a0a0c0;
    font-weight: 600;
}

.task-table tr:hover {
    background: rgba(102, 126, 234, 0.1);
}

.success-rate {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.success-rate.high {
    background: rgba(72, 187, 120, 0.2);
    color: #48bb78;
}

.success-rate.medium {
    background: rgba(237, 137, 54, 0.2);
    color: #ed8936;
}

.success-rate.low {
    background: rgba(245, 101, 101, 0.2);
    color: #f56565;
}

/* レスポンシブ */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-container {
        height: 250px;
    }
}
</style>
</head>
<body>
<div class="analytics-container">
    <a href="focus.php" class="back-link">← 集中モードに戻る</a>
    
    <div class="analytics-header">
        <h1>📊 集中タイマー分析</h1>
        <p>あなたの集中傾向を分析します</p>
    </div>
    
    <!-- 総合統計 -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon">⏱️</div>
            <div class="value"><?= number_format($overall_stats['total_minutes'] ?? 0) ?>分</div>
            <div class="label">累計集中時間</div>
        </div>
        <div class="stat-card">
            <div class="icon">📋</div>
            <div class="value"><?= number_format($overall_stats['total_sessions'] ?? 0) ?></div>
            <div class="label">総セッション数</div>
        </div>
        <div class="stat-card">
            <div class="icon">✅</div>
            <div class="value"><?php 
                $total = $overall_stats['total_sessions'] ?? 0;
                $success = $overall_stats['success_count'] ?? 0;
                echo $total > 0 ? round(($success / $total) * 100, 1) : 0;
            ?>%</div>
            <div class="label">成功率</div>
        </div>
        <div class="stat-card">
            <div class="icon">📏</div>
            <div class="value"><?= round($overall_stats['avg_minutes'] ?? 0, 1) ?>分</div>
            <div class="label">平均セッション</div>
        </div>
        <div class="stat-card">
            <div class="icon">🔥</div>
            <div class="value"><?= $streak_stats['current_streak'] ?? 0 ?>日</div>
            <div class="label">連続日数</div>
        </div>
        <div class="stat-card">
            <div class="icon">🪙</div>
            <div class="value"><?= number_format($overall_stats['total_coins'] ?? 0) ?></div>
            <div class="label">獲得コイン</div>
        </div>
    </div>
    
    <!-- 日別チャート -->
    <div class="chart-section">
        <h3>📅 日別集中時間（直近30日）</h3>
        <div class="chart-container">
            <canvas id="dailyChart"></canvas>
        </div>
    </div>
    
    <!-- 時間帯別チャート -->
    <div class="chart-section">
        <h3>⏰ 時間帯別集中傾向</h3>
        <div class="chart-container">
            <canvas id="hourlyChart"></canvas>
        </div>
    </div>
    
    <!-- 週別チャート -->
    <div class="chart-section">
        <h3>📈 週別集中時間（直近12週）</h3>
        <div class="chart-container">
            <canvas id="weeklyChart"></canvas>
        </div>
    </div>
    
    <!-- タスク別統計 -->
    <div class="chart-section">
        <h3>📝 タスク別統計（上位10件）</h3>
        <?php if (empty($task_stats)): ?>
            <p style="color: #a0a0c0; text-align: center; padding: 20px;">まだデータがありません</p>
        <?php else: ?>
        <table class="task-table">
            <thead>
                <tr>
                    <th>タスク名</th>
                    <th>回数</th>
                    <th>合計時間</th>
                    <th>平均時間</th>
                    <th>成功率</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($task_stats as $task): ?>
                <tr>
                    <td><?= htmlspecialchars($task['task']) ?></td>
                    <td><?= $task['session_count'] ?>回</td>
                    <td><?= number_format($task['total_minutes']) ?>分</td>
                    <td><?= round($task['avg_minutes'], 1) ?>分</td>
                    <td>
                        <?php 
                        $rate = $task['session_count'] > 0 ? round(($task['success_count'] / $task['session_count']) * 100, 1) : 0;
                        $class = $rate >= 80 ? 'high' : ($rate >= 50 ? 'medium' : 'low');
                        ?>
                        <span class="success-rate <?= $class ?>"><?= $rate ?>%</span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
// チャートデータ
const dailyData = <?= json_encode($chart_daily) ?>;
const weeklyData = <?= json_encode($chart_weekly) ?>;
const hourlyData = <?= json_encode($hourly_filled) ?>;

// 共通チャート設定
Chart.defaults.color = '#a0a0c0';
Chart.defaults.borderColor = 'rgba(102, 126, 234, 0.2)';

// 日別チャート
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: dailyData.map(d => d.date),
        datasets: [{
            label: '集中時間（分）',
            data: dailyData.map(d => d.total_minutes),
            backgroundColor: 'rgba(102, 126, 234, 0.6)',
            borderColor: 'rgba(102, 126, 234, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// 時間帯別チャート
new Chart(document.getElementById('hourlyChart'), {
    type: 'line',
    data: {
        labels: hourlyData.map(d => d.hour + '時'),
        datasets: [{
            label: 'セッション数',
            data: hourlyData.map(d => d.session_count),
            borderColor: 'rgba(118, 75, 162, 1)',
            backgroundColor: 'rgba(118, 75, 162, 0.2)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// 週別チャート
new Chart(document.getElementById('weeklyChart'), {
    type: 'bar',
    data: {
        labels: weeklyData.map(d => d.week_start),
        datasets: [{
            label: '集中時間（分）',
            data: weeklyData.map(d => d.total_minutes),
            backgroundColor: 'rgba(72, 187, 120, 0.6)',
            borderColor: 'rgba(72, 187, 120, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
</body>
</html>
