<?php

declare(strict_types=1);

return [

    /*
     * Токены подписываются парой ключей: закрытый есть только у сервиса таблиц,
     * открытым сервис истории проверяет подпись самостоятельно, не обращаясь
     * к соседу по сети.
     */
    'private_key_path' => env('JWT_PRIVATE_KEY_PATH', '/run/secrets/jwt-private.pem'),
    'public_key_path' => env('JWT_PUBLIC_KEY_PATH', '/run/secrets/jwt-public.pem'),
    'algorithm' => 'RS256',
    'issuer' => env('JWT_ISSUER', 'sheet-service'),

    /*
     * Токен доступа живёт недолго: если его перехватят, окно использования мало.
     * Токен обновления живёт долго, чтобы на телефоне не приходилось входить заново.
     */
    'access_token_minutes' => (int) env('JWT_ACCESS_TOKEN_MINUTES', 15),
    'refresh_token_days' => (int) env('JWT_REFRESH_TOKEN_DAYS', 60),

];
