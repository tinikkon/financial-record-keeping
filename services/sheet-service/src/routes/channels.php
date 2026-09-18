<?php

declare(strict_types=1);

use Finance\Domains\Auth\Models\UserModel;
use Finance\Domains\Sheets\Actions\FindAvailableSheetAction;
use Finance\Domains\Sheets\Exceptions\SheetNotAvailableException;
use Illuminate\Support\Facades\Broadcast;

/*
 * Канал листа закрытый: подписаться может только участник книги, которой лист
 * принадлежит. Проверка та же, что и на обычных запросах, — отдельной ветки
 * правил доступа для сокетов нет, иначе они однажды разойдутся.
 */
Broadcast::channel('sheet.{sheetIdentifier}', static function (UserModel $user, string $sheetIdentifier): bool {
    try {
        app(FindAvailableSheetAction::class)->execute($sheetIdentifier, $user->identifier());
    } catch (SheetNotAvailableException) {
        return false;
    }

    return true;
});
