# Развёртывание

Приложение рассчитано на одну виртуальную машину с Docker. Описано развёртывание
на бесплатной машине Oracle Cloud, но подойдёт любая.

## Почему именно Oracle Cloud

Из бесплатных вариантов это единственный, где помещается всё целиком. Тариф
Always Free даёт машину на четырёх ядрах ARM с 24 гигабайтами памяти без
ограничения по времени.

Альтернативы отпадают по делу. Бесплатные тарифы вроде Render усыпляют сервис
после четверти часа простоя: первое открытие с телефона ждало бы до минуты,
а постоянное соединение WebSocket там живёт плохо. Мозаика из управляемых сервисов
собирается, но за живое соединение всё равно пришлось бы платить.

Оговорка: машины на ARM в Oracle Cloud разбирают, и с первой попытки её может
не оказаться в наличии. Помогает выбрать другую зону доступности.

## Подготовка машины

Образ — Ubuntu 24.04, архитектура ARM64. Образы проекта собираются под обе
архитектуры, так что переделывать ничего не нужно.

```bash
sudo apt update && sudo apt install -y docker.io docker-compose-v2 make git
sudo usermod -aG docker "$USER"
```

Открыть в межсетевом экране порты 80 и 443 — и в правилах подсети в панели Oracle,
и в самой машине:

```bash
sudo iptables -I INPUT 1 -p tcp --dport 80 -j ACCEPT
sudo iptables -I INPUT 1 -p tcp --dport 443 -j ACCEPT
sudo netfilter-persistent save
```

Второй шаг легко пропустить: в образах Oracle входящие соединения закрыты
правилами внутри машины, а не только в панели управления.

## Домен

Нужно доменное имя — без него не выпустить сертификат. Бесплатный поддомен даёт,
например, DuckDNS: заводится за минуту и указывает на внешний адрес машины.

## Запуск

```bash
git clone https://github.com/tinikkon/financial-record-keeping.git
cd financial-record-keeping
cp .env.example .env
```

В `.env` поправить:

```
APP_DOMAIN=ваш-домен.duckdns.org
HTTP_PORT=80
HTTPS_PORT=443
MONGO_ROOT_PASSWORD=<длинный случайный пароль>
RABBITMQ_PASSWORD=<длинный случайный пароль>
REVERB_APP_SECRET=<длинный случайный ключ>
```

Пароли из примера годятся только для разработки. Сгенерировать:

```bash
openssl rand -base64 32
```

Дальше:

```bash
make keys      # пара ключей подписи токенов, в репозиторий они не попадают
make init      # сборка образов и зависимости
make up
make indexes
```

Завести пользователей:

```bash
docker compose exec sheet-app php artisan finance:create-user почта@пример.рф Имя
```

## Сертификат

Traefik умеет получать сертификат Let's Encrypt сам. Для этого в его настройки
добавляется разрешающий сервер и хранилище сертификатов, а маршруты переводятся
на защищённый вход. В файле `docker-compose.yml` это пока не включено: локально
сертификат не нужен, а на машине домен известен только на месте.

Что добавить в описание traefik:

```yaml
    command:
      - --entryPoints.websecure.address=:443
      - --certificatesResolvers.letsencrypt.acme.tlschallenge=true
      - --certificatesResolvers.letsencrypt.acme.email=ваша@почта.рф
      - --certificatesResolvers.letsencrypt.acme.storage=/certificates/acme.json
    ports:
      - "443:443"
    volumes:
      - certificates:/certificates
```

И к каждому маршруту:

```yaml
      traefik.http.routers.<имя>.entrypoints: websecure
      traefik.http.routers.<имя>.tls.certresolver: letsencrypt
```

## Обновление

```bash
git pull
docker compose build
docker compose up -d
make indexes
```

Индексы пересоздавать безопасно: существующие с теми же полями заново не создаются.

## Резервное копирование

Данные лежат в томе MongoDB. Снимок базы:

```bash
docker compose exec mongo mongodump \
  --uri "mongodb://$MONGO_ROOT_USER:$MONGO_ROOT_PASSWORD@localhost:27017/?authSource=admin" \
  --archive=/tmp/копия.archive --gzip
docker compose cp mongo:/tmp/копия.archive ./копия-$(date +%F).archive
```

Хранить копии стоит вне машины. Журнал правок в сервисе истории позволяет
восстановить состояние листа на любой момент, но только пока цела сама база.

## Чего не хватает для настоящего продакшена

Честный список, чтобы не создавать ложного впечатления:

- сертификат настраивается вручную, а не приходит из репозитория;
- нет сбора метрик и оповещений о падении;
- резервное копирование не автоматизировано;
- образы собираются на машине, а не приходят готовыми из реестра.

Для двух пользователей этого достаточно, для чего-то большего — нет.
