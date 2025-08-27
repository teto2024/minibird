MiniBird - PHP/JS Microblog (Twitter-like)
=========================================

1) Create database and import schema.sql
---------------------------------------
  mysql -u root -p -e "CREATE DATABASE microblog CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
  mysql -u root -p microblog < schema.sql

2) Configure DB in config.php if needed.

3) Deploy all files to your PHP host (CoreServer compatible). Ensure /uploads is writable by PHP.

4) Access index.php in your browser.

Features implemented
--------------------
- Register/Login (handle + password only; user_hash for password change)
- Feeds: Global / Following / Recommended (3-day score = replies*3 + reposts*2 + likes; NSFW excluded)
- Posting with Markdown, image/video upload (<=10MB), NSFW blur, 1024 chars
- Like / Repost / Bookmark / Quote / Replies page (AJAX every 3s), infinite scroll by 50
- Repost creates a timeline copy and is toggleable
- Deletion leaves "削除済み" placeholder (kept in DB)
- Moderation: add banned words; mute (minutes); freeze account; delete posts (marks as mod-deleted)
- Communities (placeholder via "private via communities table" - extend as needed)
- Focus timer (fullscreen; success grants coins + crystals; leaving fullscreen fails)
- Frames shop: spend coins/crystals to buy and equip post frame styles
- Basic rewards: random coins on posting; invite bonus on signup

Security notes
--------------
- This is an MVP for learning. Add CSRF tokens, stricter validation, file type checks, thumbnailing, pagination, rate limiting, and spam filters for production.
- Set proper permissions on /uploads and consider serving media via a separate domain.

