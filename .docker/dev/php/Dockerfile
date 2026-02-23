FROM php:8.5-fpm-alpine

ARG UID=1000
ARG GID=1000

RUN apk add --no-cache \
    bash \
    git \
    curl \
    nano \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    mariadb-client \
    mariadb-dev \
    linux-headers \
    make \
    $PHPIZE_DEPS

RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    intl \
    zip \
    pcntl \
    bcmath

RUN pecl install xdebug redis \
    && docker-php-ext-enable xdebug redis \
    && pecl clear-cache

RUN addgroup -g ${GID} appgroup \
 && adduser -D -G appgroup -u ${UID} appuser

COPY ./.docker/dev/php/php.ini /usr/local/etc/php/conf.d/php.ini
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

USER appuser

EXPOSE 9000

CMD ["php-fpm"]
