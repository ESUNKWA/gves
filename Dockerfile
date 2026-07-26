# PHP-FPM runtime for the app. The application code itself is bind-mounted at
# runtime by docker-compose (see docker-compose.yml) rather than baked into the
# image, so this image only needs to provide the PHP runtime, its extensions,
# and the tooling (composer, node/npm) needed to install dependencies and build
# assets the first time the container boots (see docker/entrypoint.sh).
FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# Runtime shared libraries (kept) + their -dev headers (only needed to compile
# the PHP extensions below, removed afterwards to keep the image small).
RUN apk add --no-cache \
        bash git nodejs npm \
        libpng libjpeg-turbo freetype libzip icu-libs postgresql-libs \
    && apk add --no-cache --virtual .build-deps \
        libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev icu-dev postgresql-dev linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pgsql gd zip intl bcmath opcache exif pcntl \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
