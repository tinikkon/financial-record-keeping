<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Data;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Finance\Domains\Changelog\Exceptions\MalformedMessageException;
use Finance\Domains\Changelog\Support\MessageFields;

/**
 * Сообщение сервиса таблиц о том, что на листе изменились ячейки.
 */
final readonly class SheetChangedMessage
{
    /**
     * @param list<ChangedCell> $cells
     */
    public function __construct(
        public string $messageIdentifier,
        public CarbonImmutable $occurredAt,
        public string $workbookIdentifier,
        public string $sheetIdentifier,
        public string $sheetName,
        public int $sheetVersion,
        public string $actorIdentifier,
        public array $cells,
    ) {
    }

    /**
     * @param array<array-key, mixed> $payload тело сообщения, разобранное из JSON
     *
     * @throws MalformedMessageException
     */
    public static function fromArray(array $payload): self
    {
        $fields = new MessageFields($payload);

        try {
            $occurredAt = CarbonImmutable::parse($fields->string('occurredAt'));
        } catch (InvalidFormatException) {
            throw new MalformedMessageException('occurredAt');
        }

        return new self(
            messageIdentifier: $fields->string('messageId'),
            occurredAt: $occurredAt,
            workbookIdentifier: $fields->string('workbookId'),
            sheetIdentifier: $fields->string('sheetId'),
            sheetName: $fields->string('sheetName'),
            sheetVersion: $fields->integer('sheetVersion'),
            actorIdentifier: $fields->string('actorId'),
            cells: array_map(ChangedCell::fromFields(...), $fields->listOfObjects('cells')),
        );
    }
}
