<?php

declare(strict_types=1);

use Finance\Domains\Workbooks\Actions\CreateWorkbookAction;
use Finance\FormulaEngine\Values\CellReference;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class)->in('Feature');

/**
 * Тесты ходят в настоящую MongoDB: подменять её заглушкой бессмысленно, потому
 * что проверяется в том числе поведение индексов и запросов.
 */
uses()->beforeEach(function (): void {
    $database = DB::connection('mongodb')->getMongoDB();

    foreach ($database->listCollectionNames() as $collectionName) {
        $database->selectCollection($collectionName)->deleteMany([]);
    }
})->in('Feature');

/**
 * @param array<string, mixed> $attributes
 */
function createUser(string $email = 'kostya@example.com', string $password = 'finance-local-8'): array
{
    /** @var \Finance\Domains\Auth\Actions\RegisterUserAction $registerUser */
    $registerUser = app(\Finance\Domains\Auth\Actions\RegisterUserAction::class);
    $user = $registerUser->execute($email, 'Костя', $password);

    return ['user' => $user, 'password' => $password];
}

/**
 * Готовит вход и пустой лист, как при первом открытии нового месяца.
 *
 * @return array{token: string, userId: string, workbook: string, sheet: string}
 */
function sheetContext(): array
{
    createUser();

    $tokens = test()->postJson('/api/auth/login', [
        'email' => 'kostya@example.com',
        'password' => 'finance-local-8',
    ])->json();

    /** @var CreateWorkbookAction $createWorkbook */
    $createWorkbook = app(CreateWorkbookAction::class);
    $workbook = $createWorkbook->execute('План финансов', $tokens['user']['id'])->identifier();

    $sheet = test()->withToken($tokens['accessToken'])
        ->postJson("/api/workbooks/{$workbook}/sheets", ['name' => '10.26'])
        ->json('id');

    return [
        'token' => $tokens['accessToken'],
        'userId' => $tokens['user']['id'],
        'workbook' => $workbook,
        'sheet' => $sheet,
    ];
}

/**
 * @param array<string, string|null> $cellsByAddress адрес ячейки к вводу
 */
function writeCells(array $context, array $cellsByAddress): array
{
    $edits = [];

    foreach ($cellsByAddress as $address => $input) {
        preg_match('/^([A-Z]+)(\d+)$/', $address, $parts);

        $edits[] = [
            'column' => CellReference::lettersToColumn($parts[1]),
            'row' => (int) $parts[2],
            'input' => $input,
        ];
    }

    return test()->withToken($context['token'])
        ->patchJson("/api/sheets/{$context['sheet']}/cells", ['edits' => $edits])
        ->assertOk()
        ->json();
}

function valueAt(array $response, string $address): ?string
{
    foreach ($response['cells'] as $cell) {
        if ($cell['address'] === $address) {
            return $cell['value'] ?? $cell['error'];
        }
    }

    return null;
}

