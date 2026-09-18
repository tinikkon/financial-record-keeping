# Образ для сквозных тестов.
#
# Официальный образ Playwright весит около двух гигабайт, а его зеркало браузеров
# бывает недоступно. Chromium берётся из репозитория Debian: тот же источник,
# что и для системных библиотек, и заметно быстрее.
FROM node:24

RUN apt-get update \
    && apt-get install -y --no-install-recommends chromium \
    && rm -rf /var/lib/apt/lists/*

ENV PLAYWRIGHT_CHROMIUM_PATH=/usr/bin/chromium

WORKDIR /e2e
