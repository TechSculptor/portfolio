#!/bin/sh
# Demo startup: migrations, demo data, then Apache.
set -eu

cd /var/www/html

as_www() { runuser -u www-data -- "$@"; }
console() { as_www php -d memory_limit=512M bin/console "$@"; }

# The database may take a few seconds to become reachable at startup
attempt=0
until console doctrine:migrations:migrate --no-interaction --allow-no-migration; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 10 ]; then
        echo "Database unreachable after $attempt attempts" >&2
        exit 1
    fi
    echo "Waiting for the database ($attempt/10)..."
    sleep 3
done

# Reset the data at every startup (DEMO_RESET=0 to disable).
# Fixtures only exist in the dev environment; the site itself runs in prod.
if [ "${DEMO_RESET:-1}" = "1" ]; then
    as_www env APP_ENV=dev APP_DEBUG=0 php -d memory_limit=512M bin/console \
        doctrine:fixtures:load --no-interaction

    # Passwords are not printed in the logs
    console app:user:create "${DEMO_EMAIL:-demo@starshop.dev}" \
        --password="${DEMO_PASSWORD:-starshop-demo}" > /dev/null

    if [ -n "${ADMIN_PASSWORD:-}" ]; then
        console app:user:create "${ADMIN_EMAIL:-admin@starshop.dev}" \
            --admin --password="$ADMIN_PASSWORD" > /dev/null
    fi
fi

exec apache2-foreground
