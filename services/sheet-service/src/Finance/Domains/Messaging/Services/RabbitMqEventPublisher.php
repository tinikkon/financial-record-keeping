<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Services;

use Finance\Domains\Messaging\Contracts\EventPublisherContract;
use Finance\Domains\Messaging\Contracts\OutgoingMessage;
use Finance\Domains\Messaging\Data\RabbitMqSettings;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

/**
 * Публикует события в RabbitMQ.
 *
 * Обменник типа topic: ключ маршрутизации вида sheet.cells.changed позволяет
 * подписчику выбирать, что именно слушать, — сервис истории берёт всё по маске
 * sheet.#, а будущий подписчик сможет взять только своё.
 *
 * Соединение открывается при первой отправке и держится до конца запроса:
 * открывать его на каждое сообщение дорого, а держать в пуле незачем — правки
 * приходят редко.
 */
final class RabbitMqEventPublisher implements EventPublisherContract
{
    private ?AMQPStreamConnection $connection = null;

    private ?AMQPChannel $channel = null;

    public function __construct(private readonly RabbitMqSettings $settings)
    {
    }

    public function publish(OutgoingMessage $message): void
    {
        $routingKey = $message->routingKey();

        try {
            $channel = $this->channel();

            $amqpMessage = new AMQPMessage(
                (string) json_encode($message->body(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                [
                    'content_type' => 'application/json',
                    'message_id' => $message->messageIdentifier(),
                    // Сообщение переживает перезапуск брокера. Без этого очередь
                    // теряет содержимое ровно тогда, когда она нужнее всего.
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                ],
            );

            $channel->basic_publish($amqpMessage, $this->settings->exchange, $routingKey);
        } catch (Throwable $exception) {
            $this->forgetConnection();

            throw new MessagePublishingFailedException($routingKey, $exception);
        }
    }

    public function __destruct()
    {
        $this->forgetConnection();
    }

    private function channel(): AMQPChannel
    {
        if ($this->channel instanceof AMQPChannel) {
            return $this->channel;
        }

        $this->connection = new AMQPStreamConnection(
            host: $this->settings->host,
            port: $this->settings->port,
            user: $this->settings->user,
            password: $this->settings->password,
            vhost: $this->settings->virtualHost,
            connection_timeout: $this->settings->connectionTimeoutSeconds,
        );

        $channel = $this->connection->channel();
        $channel->exchange_declare(
            $this->settings->exchange,
            AMQPExchangeType::TOPIC,
            passive: false,
            durable: true,
            auto_delete: false,
        );

        return $this->channel = $channel;
    }

    private function forgetConnection(): void
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (Throwable) {
            // Соединение уже разорвано — закрывать нечего.
        }

        $this->channel = null;
        $this->connection = null;
    }
}
