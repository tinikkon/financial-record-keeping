# Лёгкий образ для сквозных тестов: официальный образ Playwright весит около двух
# гигабайт, а нужны из него только системные библиотеки для Chromium.
FROM node:24

RUN npx -y playwright@1.63.0 install-deps chromium \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /e2e
