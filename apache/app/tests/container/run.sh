#!/bin/sh
# -----------------------------------------------------------
#  [*] run.sh — the test container's entrypoint
#
#  Brings up inside the one throwaway container what the
#  production stack gives the app — a MySQL 5.7.42 server
#  holding the schema of mysql/init.sql, and Apache serving
#  the app on port 8080 the way the image does in
#  production — then runs PHPUnit over the suite; every
#  argument goes to PHPUnit (a suite, a file, a --filter).
#  The app's environment is the one docker-compose.yml
#  gives the production container, with test values: a
#  fixed throwaway key and salt, the local database, and
#  the outside services (reCAPTCHA, Mailjet) left
#  unreachable, since the container has no network. MySQL
#  and Apache write only to the tmpfs mounts runTests.sh
#  gives the container; when PHPUnit fails, what the server
#  side logged is printed under its report — the tail of
#  Apache's error log, the first line of the latest Laravel
#  log entries, MySQL's errors. Dockerfile.tests makes this
#  script the image's entrypoint.
# -----------------------------------------------------------
set -e

CNF=/var/www/html/tests/container/my.cnf
LOGS=/var/www/logs

export APP_NAME=CYBERPHISH
export APP_KEY=base64:Y3liZXJwaGlzaC1yZWdyZXNzaW9uLXRlc3RzLWtleSE=
export APP_SALT=cyberphish-regression-salt
export APP_DEBUG=false
export APP_URL=https://cyberphish.knf.vu.lt
export APP_URL_ADMIN=https://cyberphish.knf.vu.lt/admin-panel
export DB_HOST=127.0.0.1
export DB_DATABASE=cyberphish
export DB_USERNAME=cyberphish
export DB_PASSWORD=cyberphish-tests
export NOCAPTCHA_SECRET=test-secret
export NOCAPTCHA_SITEKEY=test-sitekey
export MAIL_MAILER=smtp
export MAIL_HOST=in-v3.mailjet.com
export MAIL_PORT=587
export MAIL_ENCRYPTION=tls
export MAIL_FROM_ADDRESS=noreply@cyberphish.knf.vu.lt
export MAIL_FROM_NAME=CYBERPHISH
export MAIL_USERNAME=test
export MAIL_PASSWORD=test
export ADOBE_EMBED_API=test-adobe-key

if [ ! -f /schema/init.sql ]; then
    echo "run.sh: /schema/init.sql is missing — runTests.sh mounts mysql/init.sql there" >&2
    exit 2
fi


# MySQL: a fresh data directory on tmpfs, the database and
# user docker-compose.yml creates, then the schema
mysqld --defaults-file=$CNF --initialize-insecure
mysqld --defaults-file=$CNF &
for i in $(seq 1 60); do
    mysqladmin --defaults-file=$CNF -uroot ping >/dev/null 2>&1 && break
    sleep 0.5
done
mysql --defaults-file=$CNF -uroot <<SQL
CREATE DATABASE $DB_DATABASE;
CREATE USER '$DB_USERNAME'@'%' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL ON $DB_DATABASE.* TO '$DB_USERNAME'@'%';
SQL
mysql --defaults-file=$CNF -uroot $DB_DATABASE < /schema/init.sql


# Apache, started the way the production image starts it
apache2-foreground > $LOGS/apache.out 2>&1 &
for i in $(seq 1 60); do
    php -r 'exit(@fsockopen("127.0.0.1", 8080) ? 0 : 1);' && break
    sleep 0.5
done


# The suite; its failures come with the server side's logs
cd /var/www/html
status=0
php /opt/test-tools/vendor/bin/phpunit "$@" || status=$?
if [ $status -ne 0 ]; then
    if [ -s $LOGS/error.log ]; then
        echo
        echo "----- Apache error log, last lines"
        tail -n 20 $LOGS/error.log
    fi
    if [ -s storage/logs/laravel.log ]; then
        echo
        echo "----- Laravel log, the latest entries (first line of each)"
        grep -E '^\[[0-9]{4}-' storage/logs/laravel.log | tail -n 10 | cut -c1-400
    fi
    if grep -q '\[ERROR\]' $LOGS/mysql.err 2>/dev/null; then
        echo
        echo "----- MySQL errors"
        grep '\[ERROR\]' $LOGS/mysql.err | tail -n 10
    fi
fi
exit $status
