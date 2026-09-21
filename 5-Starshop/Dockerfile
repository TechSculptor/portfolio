# Demo image for Render (Apache + PHP 8.4).
# Build context: the 5-Starshop/ folder
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

# Apache listens on the port provided by Render ($PORT)
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf

WORKDIR /var/www/html

# 1. Dependencies (cached layer as long as composer.lock does not change).
#    Dev dependencies stay installed: the fixtures (Foundry) only exist
#    in the dev environment and are used to populate the demo at startup.
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist

# 2. Code + assets build
COPY . .
RUN cp .env.example .env \
    && composer dump-autoload --optimize \
    && composer run-script auto-scripts \
    # The Tailwind binary (~130 MB) is downloaded into memory: raise the PHP limit
    && php -d memory_limit=-1 bin/console tailwind:build --minify \
    && php bin/console asset-map:compile \
    && php bin/console cache:warmup \
    && APP_ENV=dev php bin/console cache:warmup \
    && chown -R www-data:www-data var

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 10000
ENTRYPOINT ["entrypoint.sh"]
