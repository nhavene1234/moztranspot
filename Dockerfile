FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpq-dev \
    && docker-php-ext-install pdo_pgsql zip bcmath opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependências primeiro (melhor cache de build)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private/livretes \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-moztransport.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV PORT=10000
EXPOSE 10000
ENTRYPOINT ["entrypoint.sh"]
