#!/bin/bash
# テストスクリプト - データベーススキーマの確認

echo "==================================="
echo "MiniBird データベーススキーマ確認"
echo "==================================="
echo ""

# データベース接続情報（config.phpから取得する必要があります）
DB_NAME="microblog"

echo "1. postsテーブルのis_deletedカラムを確認..."
mysql -u root -p -D $DB_NAME -e "SHOW COLUMNS FROM posts LIKE 'is_deleted';"

echo ""
echo "2. postsテーブルのdeleted_atカラムを確認..."
mysql -u root -p -D $DB_NAME -e "SHOW COLUMNS FROM posts LIKE 'deleted_at';"

echo ""
echo "3. community_postsテーブルのis_deletedカラムを確認..."
mysql -u root -p -D $DB_NAME -e "SHOW COLUMNS FROM community_posts LIKE 'is_deleted';"

echo ""
echo "4. community_postsテーブルのdeleted_atカラムを確認..."
mysql -u root -p -D $DB_NAME -e "SHOW COLUMNS FROM community_posts LIKE 'deleted_at';"

echo ""
echo "5. usersテーブルのdiamondsカラムを確認..."
mysql -u root -p -D $DB_NAME -e "SHOW COLUMNS FROM users LIKE 'diamonds';"

echo ""
echo "==================================="
echo "確認完了"
echo "==================================="
