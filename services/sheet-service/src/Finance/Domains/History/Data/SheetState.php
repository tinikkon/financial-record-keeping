<?php

declare(strict_types=1);

namespace Finance\Domains\History\Data;

/**
 * Содержимое листа на прошлую версию, как его восстановил сервис истории.
 * Пустые в ту версию ячейки сюда не входят.
 */
final readonly class SheetState
{
    /**
     * @param array<string, CellSnapshot> $cellsByAddress
     */
    public function __construct(private array $cellsByAddress)
    {
    }

    /**
     * Ответ сервиса истории разбирается снисходительно: ячейка без нужных полей
     * считается пустой, а не роняет восстановление всего листа.
     *
     * @param array<array-key, mixed> $cells адрес ячейки к её полям value и input
     */
    public static function fromArray(array $cells): self
    {
        $snapshots = [];
        foreach ($cells as $address => $cell) {
            if (! is_array($cell)) {
                continue;
            }

            $snapshots[(string) $address] = new CellSnapshot(
                value: is_scalar($cell['value'] ?? null) ? (string) $cell['value'] : null,
                input: is_scalar($cell['input'] ?? null) ? (string) $cell['input'] : null,
            );
        }

        return new self($snapshots);
    }

    /**
     * @return list<string>
     */
    public function addresses(): array
    {
        return array_map(strval(...), array_keys($this->cellsByAddress));
    }

    public function inputAt(string $address): ?string
    {
        return ($this->cellsByAddress[$address] ?? null)?->input;
    }
}
