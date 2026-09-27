<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Support;

use Finance\Domains\Changelog\Exceptions\MalformedMessageException;

/**
 * Чтение полей сообщения из очереди с проверкой типа.
 *
 * Сообщение приходит из соседнего сервиса как JSON, и доверять его форме
 * на слово нельзя: без проверки сломанное поле всплыло бы ошибкой посреди
 * записи в журнал, а не понятным отказом на входе.
 */
final readonly class MessageFields
{
    /**
     * @param array<array-key, mixed> $fields
     */
    public function __construct(private array $fields)
    {
    }

    /**
     * @throws MalformedMessageException
     */
    public function string(string $name): string
    {
        $value = $this->fields[$name] ?? null;
        if (! is_string($value)) {
            throw new MalformedMessageException($name);
        }

        return $value;
    }

    /**
     * Числа ячеек приходят строкой, но допускается и число: значение
     * переводится в строку без потери, а отвергать его было бы придиркой.
     *
     * @throws MalformedMessageException
     */
    public function nullableString(string $name): ?string
    {
        $value = $this->fields[$name] ?? null;
        if ($value !== null && ! is_string($value) && ! is_int($value)) {
            throw new MalformedMessageException($name);
        }

        return $value === null ? null : (string) $value;
    }

    /**
     * @throws MalformedMessageException
     */
    public function integer(string $name): int
    {
        $value = $this->fields[$name] ?? null;
        if (! is_int($value)) {
            throw new MalformedMessageException($name);
        }

        return $value;
    }

    /**
     * @return list<self>
     *
     * @throws MalformedMessageException
     */
    public function listOfObjects(string $name): array
    {
        $value = $this->fields[$name] ?? null;
        if (! is_array($value)) {
            throw new MalformedMessageException($name);
        }

        $items = [];
        foreach ($value as $item) {
            if (! is_array($item)) {
                throw new MalformedMessageException($name);
            }
            $items[] = new self($item);
        }

        return $items;
    }
}
