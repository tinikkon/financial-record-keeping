<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Data;

/**
 * Настройки подключения к RabbitMQ.
 *
 * Раздел конфигурации приводится к типам в одном месте, и издатель дальше
 * работает с полями, а не с ключами массива и приведениями на каждом шаге.
 */
final readonly class RabbitMqSettings
{
    public function __construct(
        public string $host,
        public int $port,
        public string $user,
        public string $password,
        public string $virtualHost,
        public string $exchange,
        public float $connectionTimeoutSeconds,
    ) {
    }

    /**
     * @param array<array-key, mixed> $config раздел messaging.rabbitmq
     */
    public static function fromConfig(array $config): self
    {
        return new self(
            host: self::scalar($config, 'host'),
            port: (int) self::scalar($config, 'port'),
            user: self::scalar($config, 'user'),
            password: self::scalar($config, 'password'),
            virtualHost: self::scalar($config, 'vhost'),
            exchange: self::scalar($config, 'exchange'),
            connectionTimeoutSeconds: (float) self::scalar($config, 'connection_timeout'),
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private static function scalar(array $config, string $key): string
    {
        $value = $config[$key] ?? null;

        return is_scalar($value) ? (string) $value : '';
    }
}
