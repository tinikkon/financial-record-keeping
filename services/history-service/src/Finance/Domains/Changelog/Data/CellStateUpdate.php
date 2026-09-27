<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Data;

/**
 * Новое последнее известное содержимое ячейки.
 */
final readonly class CellStateUpdate
{
    /**
     * @param string|null $numberValue число для сводок; у текста его нет, даже если текст похож на число
     */
    public function __construct(
        public string $workbookIdentifier,
        public string $sheetIdentifier,
        public string $sheetName,
        public string $address,
        public int $row,
        public int $column,
        public ?string $value,
        public ?string $numberValue,
        public ?string $input,
    ) {
    }
}
