#!/usr/bin/env bash
set -euo pipefail

# نصب Horizon (Redis) + scheduler روی لینوکس
# اجرا: sudo bash deploy/setup-queue-linux.sh /var/www/signal-telegram www-data
# پیش‌نیاز: Redis، QUEUE_CONNECTION=redis در .env

APP_DIR="${1:-/var/www/signal-telegram}"
APP_USER="${2:-www-data}"
PHP_BIN="${PHP_BIN:-$(command -v php)}"

if [[ ! -f "${APP_DIR}/artisan" ]]; then
  echo "artisan پیدا نشد در: ${APP_DIR}"
  exit 1
fi

if ! command -v supervisorctl >/dev/null 2>&1; then
  echo "Supervisor نصب نیست. در حال نصب..."
  if command -v apt-get >/dev/null 2>&1; then
    apt-get update
    apt-get install -y supervisor
  else
    echo "لطفاً Supervisor را دستی نصب کنید و دوباره اجرا کنید."
    exit 1
  fi
fi

mkdir -p "${APP_DIR}/storage/logs"
chown -R "${APP_USER}:${APP_USER}" "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" || true

HORIZON_CONF="/etc/supervisor/conf.d/signal-telegram-horizon.conf"
SCHEDULER_CONF="/etc/supervisor/conf.d/signal-telegram-scheduler.conf"

sed "s|/var/www/signal-telegram|${APP_DIR}|g; s|user=www-data|user=${APP_USER}|g" \
  "${APP_DIR}/deploy/supervisor/signal-telegram-horizon.conf" > "${HORIZON_CONF}"

sed "s|/var/www/signal-telegram|${APP_DIR}|g; s|user=www-data|user=${APP_USER}|g" \
  "${APP_DIR}/deploy/supervisor/signal-telegram-scheduler.conf" > "${SCHEDULER_CONF}"

sed -i "s|command=php |command=${PHP_BIN} |g" "${HORIZON_CONF}"
sed -i "s|php ${APP_DIR}|${PHP_BIN} ${APP_DIR}|g" "${SCHEDULER_CONF}"

# توقف workerهای قدیمی database در صورت وجود
supervisorctl stop signal-telegram-worker-fa:* 2>/dev/null || true
supervisorctl stop signal-telegram-worker-en:* 2>/dev/null || true

supervisorctl reread
supervisorctl update
supervisorctl start signal-telegram-horizon || supervisorctl restart signal-telegram-horizon
supervisorctl start signal-telegram-scheduler || supervisorctl restart signal-telegram-scheduler

echo ""
echo "وضعیت:"
supervisorctl status | grep signal-telegram || true
echo ""
echo "تمام. Horizon (Redis: telegram-fa/en/default) + scheduler فعال شدند."
echo "داشبورد: {APP_URL}/horizon (نیاز به لاگین ادمین)"
