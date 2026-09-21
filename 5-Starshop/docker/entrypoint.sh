#!/bin/sh
# Démarrage de la démo : migrations, données de démonstration, puis Apache.
set -eu

cd /var/www/html

as_www() { runuser -u www-data -- "$@"; }
console() { as_www php -d memory_limit=512M bin/console "$@"; }

# La base peut mettre quelques secondes à être joignable au démarrage
attempt=0
until console doctrine:migrations:migrate --no-interaction --allow-no-migration; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 10 ]; then
        echo "Base de données injoignable après $attempt tentatives" >&2
        exit 1
    fi
    echo "En attente de la base de données ($attempt/10)..."
    sleep 3
done

# Remise à zéro des données à chaque démarrage (DEMO_RESET=0 pour désactiver).
# Les fixtures n'existent qu'en environnement dev ; le site, lui, tourne en prod.
if [ "${DEMO_RESET:-1}" = "1" ]; then
    as_www env APP_ENV=dev APP_DEBUG=0 php -d memory_limit=512M bin/console \
        doctrine:fixtures:load --no-interaction

    # Les mots de passe ne sont pas affichés dans les logs
    console app:user:create "${DEMO_EMAIL:-demo@starshop.dev}" \
        --password="${DEMO_PASSWORD:-starshop-demo}" > /dev/null

    if [ -n "${ADMIN_PASSWORD:-}" ]; then
        console app:user:create "${ADMIN_EMAIL:-admin@starshop.dev}" \
            --admin --password="$ADMIN_PASSWORD" > /dev/null
    fi
fi

exec apache2-foreground
