<?php

declare(strict_types=1);

use Finance\Domains\Auth\Models\RefreshTokenModel;

test('вход с верным паролем выдаёт пару токенов', function (): void {
    createUser();

    $response = $this->postJson('/api/auth/login', [
        'email' => 'kostya@example.com',
        'password' => 'finance-local-8',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.email', 'kostya@example.com')
        ->assertJsonStructure(['user' => ['id', 'email', 'name'], 'accessToken', 'accessTokenExpiresAt', 'refreshToken']);
});

test('пароль не возвращается в ответе ни в каком виде', function (): void {
    createUser();

    $response = $this->postJson('/api/auth/login', [
        'email' => 'kostya@example.com',
        'password' => 'finance-local-8',
    ]);

    expect($response->json('user'))->not->toHaveKey('password_hash');
});

test('вход с неверным паролем отвергается', function (): void {
    createUser();

    $this->postJson('/api/auth/login', [
        'email' => 'kostya@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(401)->assertJsonPath('message', 'Неверная почта или пароль');
});

test('вход несуществующего пользователя отвергается так же, как неверный пароль', function (): void {
    $this->postJson('/api/auth/login', [
        'email' => 'nobody@example.com',
        'password' => 'finance-local-8',
    ])->assertStatus(401)->assertJsonPath('message', 'Неверная почта или пароль');
});

test('почта приводится к нижнему регистру и вход по ней работает', function (): void {
    createUser('Kostya@Example.com');

    $this->postJson('/api/auth/login', [
        'email' => 'kostya@example.com',
        'password' => 'finance-local-8',
    ])->assertOk();
});

test('свои данные отдаются по действующему токену доступа', function (): void {
    createUser();

    $accessToken = $this->postJson('/api/auth/login', [
        'email' => 'kostya@example.com',
        'password' => 'finance-local-8',
    ])->json('accessToken');

    $this->withToken($accessToken)
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('email', 'kostya@example.com');
});

test('без токена доступа запрос отвергается', function (): void {
    $this->getJson('/api/me')->assertStatus(401);
});

test('подделанный токен доступа отвергается', function (): void {
    $this->withToken('явно.не.токен')->getJson('/api/me')->assertStatus(401);
});
