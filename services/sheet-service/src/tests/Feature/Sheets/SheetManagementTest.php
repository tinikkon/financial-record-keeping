<?php

declare(strict_types=1);

use Finance\Domains\Sheets\Models\SheetModel;
use Finance\Domains\Workbooks\Actions\CreateWorkbookAction;

function authenticate(): array
{
    createUser();

    $tokens = test()->postJson('/api/auth/login', [
        'email' => 'kostya@example.com',
        'password' => 'finance-local-8',
    ])->json();

    return $tokens;
}

function workbookFor(string $userIdentifier, string $name = 'План финансов'): string
{
    /** @var CreateWorkbookAction $createWorkbook */
    $createWorkbook = app(CreateWorkbookAction::class);

    return $createWorkbook->execute($name, $userIdentifier)->identifier();
}

test('книга создаётся и попадает в список своих', function (): void {
    $tokens = authenticate();

    $this->withToken($tokens['accessToken'])
        ->postJson('/api/workbooks', ['name' => 'План финансов'])
        ->assertCreated()
        ->assertJsonPath('name', 'План финансов');

    $this->withToken($tokens['accessToken'])
        ->getJson('/api/workbooks')
        ->assertOk()
        ->assertJsonCount(1, 'workbooks');
});

test('чужая книга не видна и не открывается', function (): void {
    $tokens = authenticate();

    $strangerUser = createUser('stranger@example.com')['user'];
    $strangerWorkbook = workbookFor($strangerUser->identifier(), 'Чужая книга');

    $this->withToken($tokens['accessToken'])
        ->getJson('/api/workbooks')
        ->assertJsonCount(0, 'workbooks');

    $this->withToken($tokens['accessToken'])
        ->getJson("/api/workbooks/{$strangerWorkbook}/sheets")
        ->assertStatus(404)
        ->assertJsonPath('message', 'Книга не найдена');
});

test('лист создаётся с размерами по умолчанию и нулевой версией', function (): void {
    $tokens = authenticate();
    $workbook = workbookFor($tokens['user']['id']);

    $this->withToken($tokens['accessToken'])
        ->postJson("/api/workbooks/{$workbook}/sheets", ['name' => '10.26'])
        ->assertCreated()
        ->assertJsonPath('name', '10.26')
        ->assertJsonPath('version', 0)
        ->assertJsonPath('rowCount', SheetModel::DEFAULT_ROW_COUNT)
        ->assertJsonPath('columnCount', SheetModel::DEFAULT_COLUMN_COUNT);
});

test('два листа с одинаковым названием в одной книге не заводятся', function (): void {
    $tokens = authenticate();
    $workbook = workbookFor($tokens['user']['id']);

    $this->withToken($tokens['accessToken'])->postJson("/api/workbooks/{$workbook}/sheets", ['name' => '10.26']);

    $this->withToken($tokens['accessToken'])
        ->postJson("/api/workbooks/{$workbook}/sheets", ['name' => '10.26'])
        ->assertStatus(409)
        ->assertJsonPath('message', 'Лист с названием «10.26» в этой книге уже есть');
});

test('листы выдаются в порядке вкладок и переставляются', function (): void {
    $tokens = authenticate();
    $workbook = workbookFor($tokens['user']['id']);

    $identifiers = [];

    foreach (['08.26', '09.26', '10.26'] as $name) {
        $identifiers[$name] = $this->withToken($tokens['accessToken'])
            ->postJson("/api/workbooks/{$workbook}/sheets", ['name' => $name])
            ->json('id');
    }

    $this->withToken($tokens['accessToken'])
        ->postJson("/api/workbooks/{$workbook}/sheets/order", [
            'sheetIds' => [$identifiers['10.26'], $identifiers['08.26']],
        ])
        ->assertOk()
        ->assertJsonPath('sheets.0.name', '10.26')
        ->assertJsonPath('sheets.1.name', '08.26')
        ->assertJsonPath('sheets.2.name', '09.26');
});

test('лист переименовывается и получает ширины колонок', function (): void {
    $tokens = authenticate();
    $workbook = workbookFor($tokens['user']['id']);

    $sheet = $this->withToken($tokens['accessToken'])
        ->postJson("/api/workbooks/{$workbook}/sheets", ['name' => '10.26'])
        ->json('id');

    $this->withToken($tokens['accessToken'])
        ->patchJson("/api/sheets/{$sheet}", ['name' => '11.26', 'columnWidths' => ['A' => 200, 'B' => 90]])
        ->assertOk()
        ->assertJsonPath('name', '11.26')
        ->assertJsonPath('columnWidths.A', 200);
});

test('ширина колонки ограничивается разумными пределами', function (): void {
    $tokens = authenticate();
    $workbook = workbookFor($tokens['user']['id']);

    $sheet = $this->withToken($tokens['accessToken'])
        ->postJson("/api/workbooks/{$workbook}/sheets", ['name' => '10.26'])
        ->json('id');

    $this->withToken($tokens['accessToken'])
        ->patchJson("/api/sheets/{$sheet}", ['columnWidths' => ['A' => 9000]])
        ->assertStatus(422);
});

test('версия листа увеличивается атомарно и не повторяется', function (): void {
    $tokens = authenticate();
    $workbook = workbookFor($tokens['user']['id']);

    $sheetIdentifier = $this->withToken($tokens['accessToken'])
        ->postJson("/api/workbooks/{$workbook}/sheets", ['name' => '10.26'])
        ->json('id');

    /** @var \Finance\Domains\Sheets\Contracts\SheetRepositoryContract $sheets */
    $sheets = app(\Finance\Domains\Sheets\Contracts\SheetRepositoryContract::class);

    $versions = [];

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $versions[] = $sheets->incrementVersion($sheetIdentifier);
    }

    expect($versions)->toBe([1, 2, 3, 4, 5]);
});

test('удаление листа убирает его из книги', function (): void {
    $tokens = authenticate();
    $workbook = workbookFor($tokens['user']['id']);

    $sheet = $this->withToken($tokens['accessToken'])
        ->postJson("/api/workbooks/{$workbook}/sheets", ['name' => '10.26'])
        ->json('id');

    $this->withToken($tokens['accessToken'])->deleteJson("/api/sheets/{$sheet}")->assertNoContent();

    $this->withToken($tokens['accessToken'])
        ->getJson("/api/workbooks/{$workbook}/sheets")
        ->assertJsonCount(0, 'sheets');
});
