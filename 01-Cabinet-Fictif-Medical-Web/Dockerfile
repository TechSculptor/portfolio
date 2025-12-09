FROM php:8.1-apache

# Install system dependencies including msmtp for email
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    msmtp \
    msmtp-mta \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure msmtp for MailHog
RUN echo "account default" > /etc/msmtprc && \
    echo "host mailhog" >> /etc/msmtprc && \
    echo "port 1025" >> /etc/msmtprc && \
    echo "from noreply@cabinet-medical.com" >> /etc/msmtprc && \
    chmod 644 /etc/msmtprc

# Configure PHP to use msmtp
RUN echo "sendmail_path = /usr/bin/msmtp -t" > /usr/local/etc/php/conf.d/sendmail.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy composer files (if they exist)
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi

# Configure PHP for development
RUN echo "display_errors = On" >> /usr/local/etc/php/php.ini-development \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/php.ini-development
