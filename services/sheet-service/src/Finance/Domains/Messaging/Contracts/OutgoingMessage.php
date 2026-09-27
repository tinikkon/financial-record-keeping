<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Contracts;

/**
 * Сообщение, готовое к отправке в очередь.
 *
 * Издателю и хранилищу неотправленного неважно, что именно внутри: им нужны
 * ключ маршрутизации, идентификатор для отсева повторов и тело. Поэтому новое
 * событие и событие, досылаемое из хранилища, для них выглядят одинаково.
 */
interface OutgoingMessage
{
    public function routingKey(): string;

    public function messageIdentifier(): string;

    /**
     * @return array<string, mixed> тело сообщения, уходящее в очередь как JSON
     */
    public function body(): array;
}
