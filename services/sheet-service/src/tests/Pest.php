<?php

declare(strict_types=1);

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
