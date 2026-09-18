<?php

declare(strict_types=1);

namespace Finance\Domains\Summaries\Actions;

use Finance\Domains\Changelog\Models\CellChangeModel;
use Finance\Domains\Changelog\Repositories\CellChangeRepository;

/**
 * Восстанавливает содержимое листа на заданную версию.
 *
 * Журнал проигрывается от начала: каждая запись перезаписывает состояние ячейки.
 * Снимков состояния намеренно нет — при двух пользователях журнал короткий, и
 * заводить их сейчас значило бы усложнять код ради оптимизации, которая пока
 * ничего не ускоряет. Место, куда их добавить, здесь одно и очевидное.
 */
final readonly class ReconstructSheetStateAction
{
    public function __construct(private CellChangeRepository $changes)
    {
    }

    /**
     * @return array<string, array{value: string|null, input: string|null}>
     */
    public function execute(string $sheetIdentifier, int $version): array
    {
        $state = [];

        foreach ($this->changes->upToVersion($sheetIdentifier, $version) as $change) {
            /** @var CellChangeModel $change */
            $state[$change->address] = [
                'value' => $change->value_after,
                'input' => $change->input_after,
            ];
        }

        return array_filter($state, static fn (array $cell): bool => $cell['value'] !== null || $cell['input'] !== null);
    }
}
