# Базовый слой: PHP 8.4 с сервером FrankenPHP и расширениями, нужными обоим сервисам.
FROM dunglas/frankenphp:1.12-php8.4-trixie AS base

ARG UID=1000
ARG GID=1000

RUN install-php-extensions \
        mongodb \
        bcmath \
        pcntl \
        sockets \
        intl \
        zip \
        opcache \
        @composer \
    && rm -rf /tmp/* /var/lib/apt/lists/*

# Контейнер работает не от root: файлы, создаваемые внутри примонтированного
# исходного кода, должны принадлежать пользователю хоста.
RUN groupadd --gid ${GID} app \
    && useradd --uid ${UID} --gid ${GID} --create-home --shell /bin/bash app \
    && mkdir -p /data/caddy /config/caddy \
    && chown -R app:app /data/caddy /config/caddy /app

COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/Caddyfile /etc/frankenphp/Caddyfile

WORKDIR /app
USER app

# Порт непривилегированный, потому что процесс работает не от root.
ENV SERVER_NAME=":8000"
EXPOSE 8000

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]

# Слой разработки: исходники монтируются снаружи, зависимости ставятся командой make.
FROM base AS development

ENV APP_ENV=local

# Слой для продакшена: код и зависимости запекаются в образ.
FROM base AS production

ENV APP_ENV=production

COPY --chown=app:app . /app

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && php artisan config:cache \
    && php artisan route:cache
