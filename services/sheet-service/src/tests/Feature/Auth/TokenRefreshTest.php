<?php

declare(strict_types=1);

use Finance\Domains\Auth\Models\RefreshTokenModel;

function loginAndGetTokens(): array
{
    createUser();

    return test()->postJson('/api/auth/login', [
        'email' => 'kostya@example.com',
        'password' => 'finance-local-8',
    ])->json();
}

test('обновление выдаёт новую пару токенов', function (): void {
    $tokens = loginAndGetTokens();

    $refreshed = $this->postJson('/api/auth/refresh', ['refreshToken' => $tokens['refreshToken']]);

    $refreshed->assertOk();
    expect($refreshed->json('refreshToken'))->not->toBe($tokens['refreshToken']);
});

test('использованный токен обновления второй раз не работает', function (): void {
    $tokens = loginAndGetTokens();

    $this->postJson('/api/auth/refresh', ['refreshToken' => $tokens['refreshToken']])->assertOk();

    $this->postJson('/api/auth/refresh', ['refreshToken' => $tokens['refreshToken']])
        ->assertStatus(401)
        ->assertJsonPath('message', 'Сессия истекла, войдите заново');
});

test('в базе хранится отпечаток токена, а не сам токен', function (): void {
    $tokens = loginAndGetTokens();

    $stored = RefreshTokenModel::query()->first();

    expect($stored)->not->toBeNull()
        ->and($stored->token_hash)->not->toBe($tokens['refreshToken'])
        ->and($stored->token_hash)->toBe(hash('sha256', $tokens['refreshToken']));
});

test('выход делает токен обновления недействительным', function (): void {
    $tokens = loginAndGetTokens();

    $this->postJson('/api/auth/logout', ['refreshToken' => $tokens['refreshToken']])->assertNoContent();

    $this->postJson('/api/auth/refresh', ['refreshToken' => $tokens['refreshToken']])->assertStatus(401);
});

test('просроченный токен обновления не принимается', function (): void {
    $tokens = loginAndGetTokens();

    RefreshTokenModel::query()->update(['expires_at' => now()->subDay()]);

    $this->postJson('/api/auth/refresh', ['refreshToken' => $tokens['refreshToken']])->assertStatus(401);
});
