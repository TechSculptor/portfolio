# Image de démonstration pour Render (Apache + PHP 8.4).
# Contexte de build : le dossier 5-Starshop/
FROM php:8.4-apache

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    PORT=10000 \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libpq-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" intl pdo_pgsql opcache zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Apache écoute sur le port fourni par Render ($PORT)
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf

WORKDIR /var/www/html

# 1. Dépendances (couche mise en cache tant que composer.lock ne change pas).
#    Les dépendances de dev restent installées : les fixtures (Foundry) n'existent
#    qu'en environnement dev et servent à peupler la démo au démarrage.
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist

# 2. Code + build des assets
COPY . .
RUN cp .env.example .env \
    && composer dump-autoload --optimize \
    && composer run-script auto-scripts \
    # Le binaire Tailwind (~130 Mo) est téléchargé en mémoire : on lève la limite PHP
    && php -d memory_limit=-1 bin/console tailwind:build --minify \
    && php bin/console asset-map:compile \
    && php bin/console cache:warmup \
    && APP_ENV=dev php bin/console cache:warmup \
    && chown -R www-data:www-data var

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 10000
ENTRYPOINT ["entrypoint.sh"]
