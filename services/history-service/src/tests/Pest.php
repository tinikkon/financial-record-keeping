<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class)->in('Feature');

uses()->beforeEach(function (): void {
    $database = DB::connection('mongodb')->getMongoDB();

    foreach ($database->listCollectionNames() as $collectionName) {
        $database->selectCollection($collectionName)->deleteMany([]);
    }
})->in('Feature');

/**
 * Выпускает токен доступа тем же закрытым ключом, каким его выпускает сервис
 * таблиц. Обращаться к соседнему сервису из тестов не нужно: проверка подписи
 * на том и построена, что участники сети разговаривают только ключами.
 */
function accessTokenFor(string $userIdentifier = 'пользователь-1'): string
{
    $privateKey = (string) file_get_contents('/run/secrets/jwt-private.pem');

    return JWT::encode([
        'iss' => 'sheet-service',
        'sub' => $userIdentifier,
        'email' => 'kostya@example.com',
        'iat' => CarbonImmutable::now()->getTimestamp(),
        'exp' => CarbonImmutable::now()->addMinutes(15)->getTimestamp(),
    ], $privateKey, 'RS256');
}

/**
 * Собирает сообщение того же вида, какой шлёт сервис таблиц.
 *
 * @param list<array<string, mixed>> $cells
 *
 * @return array<string, mixed>
 */
function sheetEvent(array $cells, int $sheetVersion = 1, ?string $messageIdentifier = null): array
{
    return [
        'messageId' => $messageIdentifier ?? (string) Str::uuid(),
        'occurredAt' => CarbonImmutable::now()->toIso8601String(),
        'workbookId' => 'книга-1',
        'sheetId' => 'лист-1',
        'sheetName' => '10.26',
        'sheetVersion' => $sheetVersion,
        'actorId' => 'пользователь-1',
        'cells' => $cells,
    ];
}

/**
 * @return array<string, mixed>
 */
function cellPayload(string $address, int $row, int $column, ?string $value, ?string $input = null): array
{
    return [
        'address' => $address,
        'row' => $row,
        'column' => $column,
        'input' => $input ?? $value,
        'kind' => $value === null ? 'empty' : 'number',
        'value' => $value,
        'error' => null,
        'format' => [],
    ];
}
