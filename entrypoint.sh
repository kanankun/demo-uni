#!/bin/sh
# Database, then the member-traffic bot, then Apache in the foreground.
set -e

mysqld_safe --user=mysql > /dev/null 2>&1 &

for i in $(seq 40); do
    mysqladmin -uroot -proot ping --silent 2>/dev/null && break
    sleep 1
done

# Members signing in all day. Invisible until a player makes it visible.
php /opt/bot.php > /dev/null 2>&1 &

exec apache2-foreground
