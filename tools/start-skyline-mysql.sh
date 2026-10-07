#!/usr/bin/env bash
# Starts Skyline Hotel's own MySQL and creates its databases.
# Does not connect to BeyondTechWorld, AlphaBridge, or any other local database.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DATADIR="$ROOT/mysql-data"
SOCKET="$DATADIR/mysql.sock"
PIDFILE="$DATADIR/mysql.pid"
LOG="$DATADIR/error.log"
PORT=3316
MYSQLD="$(command -v mysqld)"
BASEDIR="$(cd "$(dirname "$MYSQLD")/.." && pwd)"

mkdir -p "$DATADIR"

if [[ ! -d "$DATADIR/mysql" ]]; then
  echo "Initializing a new MySQL data directory for Skyline Hotel."
  "$MYSQLD" --initialize-insecure --datadir="$DATADIR" --basedir="$BASEDIR"
fi

if ! mysqladmin --socket="$SOCKET" ping >/dev/null 2>&1; then
  nohup "$MYSQLD" \
    --datadir="$DATADIR" \
    --basedir="$BASEDIR" \
    --port="$PORT" \
    --bind-address=127.0.0.1 \
    --socket="$SOCKET" \
    --pid-file="$PIDFILE" \
    --log-error="$LOG" \
    --mysqlx=OFF \
    >/dev/null 2>&1 &
  disown || true
  for _ in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20; do
    if mysqladmin --socket="$SOCKET" ping >/dev/null 2>&1; then
      break
    fi
    sleep 1
  done
fi

if ! mysqladmin --socket="$SOCKET" ping >/dev/null 2>&1; then
  echo "Skyline Hotel MySQL did not start. See $LOG" >&2
  exit 1
fi

SQL="
CREATE DATABASE IF NOT EXISTS skyline_hotel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS skyline_praise CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'skyline'@'127.0.0.1' IDENTIFIED BY 'skyline_local';
CREATE USER IF NOT EXISTS 'skyline'@'localhost' IDENTIFIED BY 'skyline_local';
ALTER USER 'skyline'@'127.0.0.1' IDENTIFIED BY 'skyline_local';
ALTER USER 'skyline'@'localhost' IDENTIFIED BY 'skyline_local';
GRANT ALL PRIVILEGES ON skyline_hotel.* TO 'skyline'@'127.0.0.1';
GRANT ALL PRIVILEGES ON skyline_hotel.* TO 'skyline'@'localhost';
GRANT ALL PRIVILEGES ON skyline_praise.* TO 'skyline'@'127.0.0.1';
GRANT ALL PRIVILEGES ON skyline_praise.* TO 'skyline'@'localhost';
FLUSH PRIVILEGES;
"

if mysql --socket="$SOCKET" -u root -e "SELECT 1" >/dev/null 2>&1; then
  mysql --socket="$SOCKET" -u root -e "$SQL"
  mysql --socket="$SOCKET" -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED BY 'skyline_root_local';" >/dev/null 2>&1 || true
else
  mysql --socket="$SOCKET" -u root -pskyline_root_local -e "$SQL"
fi

echo "Skyline Hotel MySQL is ready on 127.0.0.1:${PORT}"
echo "Databases: skyline_hotel, skyline_praise"
echo "User: skyline"
