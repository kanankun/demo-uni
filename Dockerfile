# Week 1 demo: Apache + PHP + MariaDB in one container.
#
# One image, one port, one URL to read out to a room. The database is
# seeded at build time and the data directory is baked in, so a fresh
# container is serving in about two seconds.
#
#   docker compose up -d --build
FROM php:8.2-apache

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli \
 && apt-get update \
 && apt-get install -y --no-install-recommends mariadb-server \
 && rm -rf /var/lib/apt/lists/*

RUN printf '[mysqld]\nbind-address=127.0.0.1\nskip-name-resolve\n\
innodb-buffer-pool-size=64M\ninnodb-log-buffer-size=4M\n\
performance-schema=OFF\nkey-buffer-size=8M\nmax-connections=150\n' \
    > /etc/mysql/conf.d/demo.cnf

# seed at build time, then bake the data directory into the image
COPY sql/ /tmp/seed/
RUN set -eux; \
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql > /dev/null; \
    mysqld_safe --user=mysql --skip-networking > /dev/null 2>&1 & \
    for i in $(seq 40); do mysqladmin ping --silent 2>/dev/null && break; sleep 1; done; \
    mysql -e "CREATE DATABASE uam_db CHARACTER SET utf8mb4;"; \
    for f in /tmp/seed/*.sql; do echo "seeding $(basename "$f")"; mysql uam_db < "$f"; done; \
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY 'root';"; \
    mysql -uroot -proot -e "CREATE USER 'root'@'127.0.0.1' IDENTIFIED BY 'root'; \
                            GRANT ALL ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION; \
                            FLUSH PRIVILEGES;"; \
    mysqladmin -uroot -proot shutdown; \
    rm -rf /tmp/seed

COPY src/     /var/www/html/
COPY uploads/ /var/www/uploads/
COPY bot/bot.php /opt/bot.php

# The application itself is read-only to the web server. Only the sandbox
# root is writable, so an uploaded shell can reach player directories and
# nothing else - it cannot rewrite the site for everybody.
RUN set -eux; \
    chown -R root:root /var/www/html; chmod -R 0755 /var/www/html; \
    mkdir -p /var/www/html/i; chown www-data:www-data /var/www/html/i; chmod 1777 /var/www/html/i; \
    chown -R www-data:www-data /var/www/uploads; \
    printf '<Directory /var/www/html/i>\n    Options -Indexes\n</Directory>\n' \
        > /etc/apache2/conf-enabled/sandbox.conf; \
    mkdir -p /var/log/demo; \
    sed -i 's|^\s*CustomLog.*$|CustomLog /var/log/demo/access.log combined|' \
        /etc/apache2/sites-available/000-default.conf

COPY --chmod=0755 entrypoint.sh /usr/local/bin/demo-start
EXPOSE 80
CMD ["/usr/local/bin/demo-start"]
