<?php
// debug.php
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Debug Feed</title>
</head>
<body>
<div id="posts"></div>
<script>
const data = /* JSON の配列 */;

const container = document.getElementById('posts');
data.forEach(post => {
  const div = document.createElement('div');
  div.innerHTML = `
    <strong>${post.display_name}</strong> (VIP: ${post.vip_level})<br>
    ${post.content_html}<hr>
  `;
  container.appendChild(div);
});
</script>
<h1>Feed Debug</h1>
<pre id="debug">Loading...</pre>

<script>
async function fetchFeed() {
    const res = await fetch('feed.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fetch', feed: 'global', limit: 5 })
    });
    const data = await res.json();

    // ここで VIP が入っているか確認
    document.getElementById('debug').innerText = JSON.stringify(data.items, null, 2);
}
fetchFeed();
</script>
</body>
</html>