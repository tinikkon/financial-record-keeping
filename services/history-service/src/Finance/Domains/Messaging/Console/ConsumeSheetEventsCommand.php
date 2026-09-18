<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Console;

use Finance\Domains\Messaging\Actions\HandleSheetEventAction;
use Illuminate\Console\Command;
use JsonException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

/**
 * Долгоживущий потребитель очереди.
 *
 * Подтверждение отправляется только после успешной записи в журнал: если сервис
 * упадёт посреди обработки, сообщение вернётся в очередь и придёт заново.
 * Повторную доставку отсекает отметка об обработанном сообщении.
 */
final class ConsumeSheetEventsCommand extends Command
{
    private const string ROUTING_PATTERN = 'sheet.#';

    protected $signature = 'finance:consume-sheet-events';

    protected $description = 'Слушать очередь изменений и вести журнал';

    public function handle(HandleSheetEventAction $handleEvent): int
    {
        /** @var array<string, mixed> $settings */
        $settings = config('messaging.rabbitmq');
        $queueName = (string) env('RABBITMQ_QUEUE', 'history.sheet-events');

        $connection = new AMQPStreamConnection(
            host: (string) $settings['host'],
            port: (int) $settings['port'],
            user: (string) $settings['user'],
            password: (string) $settings['password'],
            vhost: (string) $settings['vhost'],
        );

        $channel = $connection->channel();
        $channel->exchange_declare((string) $settings['exchange'], AMQPExchangeType::TOPIC, false, true, false);
        $channel->queue_declare($queueName, false, true, false, false);
        $channel->queue_bind($queueName, (string) $settings['exchange'], self::ROUTING_PATTERN);

        // По одному сообщению за раз: обработка идёт в базу, и набирать пачку
        // в память незачем — узким местом будет не сеть.
        $channel->basic_qos(0, 1, false);

        $channel->basic_consume($queueName, '', false, false, false, false, function (AMQPMessage $message) use ($handleEvent): void {
            try {
                /** @var array<string, mixed> $payload */
                $payload = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);

                $recorded = $handleEvent->execute($payload);
                $message->ack();

                $this->line("лист {$payload['sheetName']}: записей в журнал — {$recorded}");
            } catch (JsonException $exception) {
                // Нечитаемое сообщение возвращать в очередь бессмысленно:
                // оно будет ломаться снова и снова, заткнув собой всю очередь.
                $message->reject(false);
                $this->error("Сообщение нечитаемо и отброшено: {$exception->getMessage()}");
            } catch (Throwable $exception) {
                $message->nack(true);
                $this->error("Не удалось обработать сообщение, вернул в очередь: {$exception->getMessage()}");
            }
        });

        $this->info("Слушаю очередь {$queueName}");

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        return self::SUCCESS;
    }
}
