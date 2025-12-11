<?php
// ===============================================
// trend_calculator.php
// トレンドワード計算スクリプト（cron等で定期実行）
// ===============================================

require_once __DIR__ . '/config.php';
$pdo = db();

// 集計期間設定（過去48時間または最新200件）
$period_start = date('Y-m-d H:i:s', strtotime('-48 hours'));
$period_end = date('Y-m-d H:i:s');

// 投稿取得（最新200件または48時間以内）
$stmt = $pdo->prepare("
    SELECT id, content, 
           (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) as like_count,
           (SELECT COUNT(*) FROM posts AS reposts WHERE reposts.repost_of = posts.id) as repost_count
    FROM posts
    WHERE created_at >= ? AND deleted_at IS NULL
    ORDER BY created_at DESC
    LIMIT 200
");
$stmt->execute([$period_start]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ストップワード取得
$stmt = $pdo->query("SELECT word FROM stopwords");
$stopwords = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'word');

// 単語カウント
$word_stats = [];

foreach ($posts as $post) {
    $content = $post['content'];
    $like_count = intval($post['like_count']);
    $repost_count = intval($post['repost_count']);
    
    // 日本語と英語の単語を抽出
    $words = extract_words($content, $stopwords);
    
    foreach ($words as $word) {
        if (!isset($word_stats[$word])) {
            $word_stats[$word] = [
                'post_count' => 0,
                'occurrence_count' => 0,
                'total_likes' => 0,
                'total_reposts' => 0,
                'post_ids' => []
            ];
        }
        
        if (!in_array($post['id'], $word_stats[$word]['post_ids'])) {
            $word_stats[$word]['post_count']++;
            $word_stats[$word]['post_ids'][] = $post['id'];
        }
        
        $word_stats[$word]['occurrence_count']++;
        $word_stats[$word]['total_likes'] += $like_count;
        $word_stats[$word]['total_reposts'] += $repost_count;
    }
}

// トレンドスコア計算
$trend_words = [];

foreach ($word_stats as $word => $stats) {
    // スコア計算ロジック
    // 投稿数 × 2 + 登場回数 × 1 + いいね数 × 0.5 + リポスト数 × 1
    $score = ($stats['post_count'] * 2.0) +
             ($stats['occurrence_count'] * 1.0) +
             ($stats['total_likes'] * 0.5) +
             ($stats['total_reposts'] * 1.0);
    
    $trend_words[] = [
        'word' => $word,
        'post_count' => $stats['post_count'],
        'occurrence_count' => $stats['occurrence_count'],
        'total_likes' => $stats['total_likes'],
        'total_reposts' => $stats['total_reposts'],
        'trend_score' => $score
    ];
}

// スコア順にソート
usort($trend_words, function($a, $b) {
    return $b['trend_score'] <=> $a['trend_score'];
});

// 上位50件をDBに保存
$pdo->beginTransaction();

// 古いトレンドデータ削除（24時間以上前）
$stmt = $pdo->prepare("DELETE FROM trend_words WHERE calculated_at < ?");
$stmt->execute([date('Y-m-d H:i:s', strtotime('-24 hours'))]);

// 新しいトレンドデータ挿入
$stmt = $pdo->prepare("
    INSERT INTO trend_words (word, post_count, occurrence_count, total_likes, total_reposts, trend_score, calculated_at, period_start, period_end)
    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
");

$saved_count = 0;
foreach (array_slice($trend_words, 0, 50) as $trend) {
    $stmt->execute([
        $trend['word'],
        $trend['post_count'],
        $trend['occurrence_count'],
        $trend['total_likes'],
        $trend['total_reposts'],
        $trend['trend_score'],
        $period_start,
        $period_end
    ]);
    $saved_count++;
}

$pdo->commit();

echo "Trend calculation completed. {$saved_count} words saved.\n";

// ===============================================
// 単語抽出関数
// ===============================================
function extract_words($text, $stopwords) {
    $words = [];
    
    // URLを除去
    $text = preg_replace('#https?://[^\s]+#', '', $text);
    
    // ハッシュタグ、メンション抽出
    preg_match_all('/#([^\s#]+)/', $text, $hashtags);
    preg_match_all('/@([^\s@]+)/', $text, $mentions);
    
    // ハッシュタグを追加（#を除く）
    foreach ($hashtags[1] as $tag) {
        if (!in_array($tag, $stopwords) && mb_strlen($tag) >= 2) {
            $words[] = $tag;
        }
    }
    
    // 日本語：文字種で区切る（簡易形態素解析風）
    // ひらがな、カタカナ、漢字を分離
    $japanese_words = extract_japanese_words($text, $stopwords);
    $words = array_merge($words, $japanese_words);
    
    // 英数字：スペースで区切る
    preg_match_all('/[a-zA-Z0-9]+/', $text, $english_words);
    foreach ($english_words[0] as $word) {
        $word = strtolower($word);
        if (!in_array($word, $stopwords) && strlen($word) >= 3) {
            $words[] = $word;
        }
    }
    
    return $words;
}

function extract_japanese_words($text, $stopwords) {
    $words = [];
    
    // カタカナ抽出（2文字以上）
    preg_match_all('/[ァ-ヶー]{2,}/u', $text, $katakana_matches);
    foreach ($katakana_matches[0] as $word) {
        if (!in_array($word, $stopwords)) {
            $words[] = $word;
        }
    }
    
    // 漢字抽出（2文字以上）
    preg_match_all('/[一-龠々]{2,}/u', $text, $kanji_matches);
    foreach ($kanji_matches[0] as $word) {
        if (!in_array($word, $stopwords) && mb_strlen($word) <= 8) {
            $words[] = $word;
        }
    }
    
    // ひらがな+漢字の組み合わせ（3文字以上）
    preg_match_all('/[ぁ-ん一-龠々]{3,}/u', $text, $mixed_matches);
    foreach ($mixed_matches[0] as $word) {
        if (!in_array($word, $stopwords) && mb_strlen($word) <= 10) {
            $words[] = $word;
        }
    }
    
    return $words;
}
