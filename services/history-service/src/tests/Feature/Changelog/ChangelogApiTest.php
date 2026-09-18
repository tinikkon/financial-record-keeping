<?php

declare(strict_types=1);

test('история листа отдаётся от новых изменений к старым', function (): void {
    handleEvent(sheetEvent([cellPayload('B3', 3, 2, '120')], sheetVersion: 1));
    handleEvent(sheetEvent([cellPayload('B3', 3, 2, '300')], sheetVersion: 2));

    $response = $this->withToken(accessTokenFor())
        ->getJson('/api/sheets/лист-1/history')
        ->assertOk()
        ->json();

    expect($response['changes'])->toHaveCount(2)
        ->and($response['changes'][0]['valueAfter'])->toBe('300')
        ->and($response['changes'][0]['sheetVersion'])->toBe(2);
});

test('история одной ячейки отдаётся отдельно', function (): void {
    handleEvent(sheetEvent([cellPayload('B3', 3, 2, '120'), cellPayload('B4', 4, 2, '80')]));

    $response = $this->withToken(accessTokenFor())
        ->getJson('/api/sheets/лист-1/cells/B3/history')
        ->assertOk()
        ->json();

    expect($response['changes'])->toHaveCount(1)
        ->and($response['changes'][0]['address'])->toBe('B3');
});

test('без токена история не отдаётся', function (): void {
    $this->getJson('/api/sheets/лист-1/history')->assertStatus(401);
});

test('токен, подписанный не тем ключом, отвергается', function (): void {
    $this->withToken('подделка.подделка.подделка')
        ->getJson('/api/sheets/лист-1/history')
        ->assertStatus(401);
});
