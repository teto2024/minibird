<?php
require_once __DIR__ . '/config.php';
$me = user();
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>使い方 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
.how-to-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}
.how-to-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    text-align: center;
}
.how-to-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
}
.section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.section h2 {
    margin: 0 0 15px 0;
    color: #2d3748;
    font-size: 24px;
    border-bottom: 2px solid #667eea;
    padding-bottom: 10px;
}
.section h3 {
    margin: 20px 0 10px 0;
    color: #4a5568;
    font-size: 18px;
}
.section p {
    line-height: 1.8;
    color: #4a5568;
    margin-bottom: 15px;
}
.section ul {
    margin: 10px 0 15px 20px;
    line-height: 1.8;
}
.section li {
    margin-bottom: 8px;
    color: #4a5568;
}
.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin: 20px 0;
}
.feature-card {
    background: #f7fafc;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 15px;
    transition: all 0.3s;
}
.feature-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 6px rgba(102, 126, 234, 0.1);
}
.feature-icon {
    font-size: 32px;
    margin-bottom: 10px;
}
.feature-title {
    font-weight: bold;
    color: #2d3748;
    margin-bottom: 5px;
}
.feature-desc {
    font-size: 14px;
    color: #718096;
}
.back-button {
    display: inline-block;
    padding: 12px 30px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    margin-top: 20px;
}
.back-button:hover {
    background: #5568d3;
}
</style>
</head>
<body>
<div class="how-to-container">
    <div class="how-to-header">
        <h1>📚 MiniBirdの使い方</h1>
        <p>MiniBirdをより楽しく使うための完全ガイド</p>
    </div>

    <!-- 基本機能 -->
    <div class="section">
        <h2>🐦 基本機能</h2>
        
        <h3>投稿する</h3>
        <p>トップページの投稿欄から、テキストや画像・動画を投稿できます。</p>
        <ul>
            <li><strong>Markdown対応：</strong>**太字**、*斜体*、`コード`などが使えます</li>
            <li><strong>最大1024文字：</strong>長文もOK</li>
            <li><strong>画像・動画：</strong>最大4枚まで同時アップロード可能</li>
            <li><strong>NSFW設定：</strong>センシティブな内容はNSFWにチェック</li>
            <li><strong>メンション：</strong>@ユーザー名で他のユーザーに通知</li>
        </ul>

        <h3>フィード</h3>
        <p>4つのフィードで投稿を楽しめます：</p>
        <ul>
            <li><strong>おすすめ：</strong>人気の投稿を表示</li>
            <li><strong>全体：</strong>すべての投稿を時系列で表示</li>
            <li><strong>フォロー中：</strong>フォローしているユーザーの投稿のみ</li>
            <li><strong>ブースト：</strong>ブーストされた注目の投稿</li>
        </ul>

        <h3>投稿へのアクション</h3>
        <ul>
            <li><strong>❤️ いいね：</strong>投稿に共感を示す</li>
            <li><strong>♻️ リポスト：</strong>投稿をシェアする</li>
            <li><strong>💬 返信：</strong>投稿にコメントする</li>
            <li><strong>❝ 引用：</strong>コメント付きでシェアする</li>
            <li><strong>📑 ブックマーク：</strong>後で見返せるように保存</li>
            <li><strong>🔥 ブースト：</strong>コイン200+クリスタル20で2日間ブーストフィードに表示</li>
        </ul>
    </div>

    <!-- ゲーミフィケーション -->
    <div class="section">
        <h2>🎮 ゲーミフィケーション機能</h2>

        <h3>通貨システム</h3>
        <p>MiniBirdには3種類の通貨があります：</p>
        <ul>
            <li><strong>🪙 コイン：</strong>クエストや活動で獲得。ショップやブーストで使用</li>
            <li><strong>💎 クリスタル：</strong>貴重な通貨。VIP昇格やプレミアムアイテムに使用</li>
            <li><strong>💠 ダイヤモンド：</strong>最も貴重な通貨。高級アイテムや限定機能に使用</li>
        </ul>

        <h3>クエストシステム</h3>
        <p>様々なクエストをクリアして報酬を獲得しましょう：</p>
        
        <h4>📅 デイリークエスト（毎日リセット）</h4>
        <ul>
            <li>投稿、いいね、リポストなど日常的なアクション</li>
            <li>全クリアボーナス：<strong>コイン3000 + クリスタル15</strong></li>
        </ul>

        <h4>📆 ウィークリークエスト（毎週日曜リセット）</h4>
        <ul>
            <li>週単位での大きな目標</li>
            <li>全クリアボーナス：<strong>コイン10000 + クリスタル50 + ダイヤモンド2</strong></li>
        </ul>

        <h4>🔗 リレークエスト（順番にクリア）</h4>
        <ul>
            <li>前のクエストをクリアすると次が解放</li>
            <li>全クリアボーナス：<strong>コイン2000 + ダイヤモンド1</strong></li>
        </ul>

        <h3>集中タスク機能</h3>
        <p>集中して作業した時間を記録し、報酬を獲得できます：</p>
        <ul>
            <li>タスクを設定して集中時間を計測</li>
            <li>集中時間に応じてコインを獲得</li>
            <li>ランキングで他のユーザーと競争</li>
            <li>バフを使って報酬をアップ</li>
        </ul>
    </div>

    <!-- VIPシステム -->
    <div class="section">
        <h2>👑 VIPシステム</h2>
        <p>VIPになると特別な特典が得られます。VIP昇格には以下の条件をすべて満たす必要があります：</p>
        <ul>
            <li><strong>招待人数：</strong>レベル × 2人</li>
            <li><strong>累計集中時間：</strong>レベル × 100分</li>
            <li><strong>クリスタル：</strong>レベル × 100</li>
            <li><strong>コイン：</strong>必要クリスタルの100倍</li>
            <li><strong>デイリークエストコンプリート：</strong>レベル × 2回</li>
            <li><strong>ウィークリークエストコンプリート：</strong>1 + (レベル - 1) × 2回</li>
            <li><strong>リレークエストコンプリート：</strong>レベル × 1回</li>
        </ul>
        <p>例：VIP2になるには、招待4人、集中時間200分、クリスタル200、コイン20000、デイリー4回、ウィークリー3回、リレー2回が必要</p>
    </div>

    <!-- ショップ -->
    <div class="section">
        <h2>🛍️ ショップ</h2>
        
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">🖼️</div>
                <div class="feature-title">フレームショップ</div>
                <div class="feature-desc">投稿に装飾的なフレームを追加</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <div class="feature-title">バフショップ</div>
                <div class="feature-desc">一定時間、報酬を増やすバフを購入</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📦</div>
                <div class="feature-title">パッケージショップ</div>
                <div class="feature-desc">絵文字や称号などのパッケージを購入</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👑</div>
                <div class="feature-title">VIPショップ</div>
                <div class="feature-desc">VIPレベルの昇格</div>
            </div>
        </div>
    </div>

    <!-- コミュニティ -->
    <div class="section">
        <h2>👥 コミュニティ機能（β版）</h2>
        <p>興味のあるテーマごとにコミュニティを作成・参加できます：</p>
        <ul>
            <li><strong>コミュニティ作成：</strong>自分のコミュニティを立ち上げる</li>
            <li><strong>メンバー招待：</strong>他のユーザーを招待</li>
            <li><strong>専用フィード：</strong>コミュニティ内での投稿と交流</li>
            <li><strong>公開・非公開設定：</strong>プライバシーをコントロール</li>
        </ul>
    </div>

    <!-- トレンド -->
    <div class="section">
        <h2>🔥 トレンド機能</h2>
        <p>MiniBirdで今話題のワードやトピックをチェック：</p>
        <ul>
            <li>投稿の中から注目のワードを自動抽出</li>
            <li>いいね数やリポスト数も考慮したスコアリング</li>
            <li>定期的に更新されるホットなトピック</li>
        </ul>
    </div>

    <!-- その他の機能 -->
    <div class="section">
        <h2>🎯 その他の便利な機能</h2>
        
        <h3>通知システム</h3>
        <p>重要な更新をリアルタイムで受け取れます：</p>
        <ul>
            <li>メンション通知</li>
            <li>いいね・リポスト通知</li>
            <li>返信通知</li>
            <li>フォロー通知</li>
            <li>コミュニティ招待通知</li>
        </ul>

        <h3>検索機能</h3>
        <p>投稿やユーザーを簡単に検索できます</p>

        <h3>プロフィールカスタマイズ</h3>
        <ul>
            <li>アイコン設定</li>
            <li>表示名変更</li>
            <li>称号の装備</li>
            <li>フレームの適用</li>
        </ul>
    </div>

    <!-- コミュニティガイドライン -->
    <div class="section">
        <h2>🤝 コミュニティガイドライン</h2>
        <p>MiniBirdを楽しく安全に使うための基本ルール：</p>
        <ul>
            <li><strong>相互尊重：</strong>他のユーザーを尊重し、丁寧なコミュニケーションを心がけましょう</li>
            <li><strong>適切なコンテンツ：</strong>センシティブな内容はNSFW設定を使用してください</li>
            <li><strong>スパム禁止：</strong>過度な宣伝や繰り返し投稿は控えましょう</li>
            <li><strong>プライバシー保護：</strong>他人の個人情報を無断で公開しないでください</li>
            <li><strong>著作権尊重：</strong>他人の作品を投稿する際は権利に配慮しましょう</li>
            <li><strong>建設的な対話：</strong>批判的な意見も建設的に表現しましょう</li>
        </ul>
        <p>違反を見つけた場合は、モデレーターに報告してください。</p>
    </div>

    <!-- Tips -->
    <div class="section">
        <h2>💡 便利なTips</h2>
        <ul>
            <li><strong>Enterで投稿：</strong>投稿欄の「Enterで投稿」をチェックすると、Enterキーで投稿できます</li>
            <li><strong>バフの活用：</strong>集中タスクをする前にバフを使うと、報酬が増えます</li>
            <li><strong>毎日ログイン：</strong>デイリークエストをクリアして、毎日報酬を獲得しましょう</li>
            <li><strong>フレームで目立つ：</strong>お気に入りのフレームを装備して、投稿を目立たせましょう</li>
            <li><strong>コミュニティで交流：</strong>同じ興味を持つユーザーとコミュニティで深く交流できます</li>
            <li><strong>ブースト機能：</strong>重要な投稿はブーストして多くの人に見てもらいましょう</li>
        </ul>
    </div>

    <div style="text-align: center;">
        <a href="index.php" class="back-button">フィードに戻る</a>
    </div>
</div>
</body>
</html>
