<?php

declare(strict_types=1);

namespace Finance\Domains\Core\Contracts;

/**
 * Описание индекса коллекции.
 *
 * Индексы объявляются рядом с репозиторием, который ими пользуется, а не в общем
 * файле миграций: тогда при чтении репозитория сразу видно, на что он опирается.
 */
final readonly class IndexDefinition
{
    /**
     * @param array<string, int> $keys               поля индекса: 1 по возрастанию, -1 по убыванию
     * @param int|null           $expireAfterSeconds срок жизни документа, если индекс убирающий
     */
    public function __construct(
        public string $name,
        public array $keys,
        public bool $unique = false,
        public ?int $expireAfterSeconds = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        $options = ['name' => $this->name];

        if ($this->unique) {
            $options['unique'] = true;
        }

        if ($this->expireAfterSeconds !== null) {
            $options['expireAfterSeconds'] = $this->expireAfterSeconds;
        }

        return $options;
    }
}
