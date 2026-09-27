<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Data;

use Finance\Domains\Changelog\Exceptions\MalformedMessageException;
use Finance\Domains\Changelog\Support\MessageFields;

/**
 * Ячейка из сообщения об изменении — в том виде, в каком она стала.
 */
final readonly class ChangedCell
{
    public function __construct(
        public string $address,
        public int $row,
        public int $column,
        public string $kind,
        public ?string $value,
        public ?string $input,
    ) {
    }

    /**
     * @throws MalformedMessageException
     */
    public static function fromFields(MessageFields $fields): self
    {
        return new self(
            address: $fields->string('address'),
            row: $fields->integer('row'),
            column: $fields->integer('column'),
            kind: $fields->string('kind'),
            value: $fields->nullableString('value'),
            input: $fields->nullableString('input'),
        );
    }

    /**
     * Текст, похожий на число, числом не считается: номер счёта «007»
     * в сумму колонки попадать не должен.
     */
    public function numberValue(): ?string
    {
        return $this->kind === 'text' ? null : $this->value;
    }
}
