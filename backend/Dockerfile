FROM php:8.3-apache-bookworm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates curl gnupg unzip git libpq-dev libzip-dev libicu-dev \
    libpng-dev libfreetype6-dev libjpeg-dev \
    # cliente pg_dump compatível com o servidor 18 (necessário para spatie/laravel-backup)
    && install -d /usr/share/postgresql-common/pgdg \
    && curl -fsSL -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc https://www.postgresql.org/media/keys/ACCC4CF8.asc \
    && echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] https://apt.postgresql.org/pub/repos/apt bookworm-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update && apt-get install -y --no-install-recommends postgresql-client-18 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql zip intl bcmath pcntl gd opcache \
    && pecl install redis && docker-php-ext-enable redis \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN sed -ri 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf && a2enconf servername
WORKDIR /var/www/html

# ---------- dev ----------
FROM base AS dev
ARG UID=1000
ARG GID=1000
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# Xdebug (depuração) e pcov (cobertura): SOMENTE no ambiente de desenvolvimento
RUN pecl install xdebug pcov && docker-php-ext-enable xdebug pcov
COPY docker/php-dev.ini /usr/local/etc/php/conf.d/zz-dev.ini
RUN usermod -u ${UID} www-data && groupmod -g ${GID} www-data \
    && chown -R www-data:www-data /var/run/apache2 /var/log/apache2 /var/lock/apache2
USER www-data
EXPOSE 8080

# ---------- dependências de produção ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# ---------- prod ----------
FROM base AS prod
COPY --from=vendor /app /var/www/html
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chown -R www-data:www-data /var/run/apache2 /var/log/apache2 /var/lock/apache2
USER www-data
EXPOSE 8080
