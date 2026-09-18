<?php

declare(strict_types=1);

use Finance\FormulaEngine\Ast\BinaryOperationNode;
use Finance\FormulaEngine\Ast\FunctionCallNode;
use Finance\FormulaEngine\Ast\RangeNode;
use Finance\FormulaEngine\Exceptions\SyntaxErrorException;
use Finance\FormulaEngine\FormulaEngine;

beforeEach(function (): void {
    $this->engine = FormulaEngine::create();
});

test('ведущий знак равенства необязателен', function (): void {
    expect($this->engine->parse('=1+2'))->toBeInstanceOf(BinaryOperationNode::class)
        ->and($this->engine->parse('1+2'))->toBeInstanceOf(BinaryOperationNode::class);
});

test('двоеточие между ссылками даёт диапазон', function (): void {
    $node = $this->engine->parse('=СУММ(B3:B90)');

    expect($node)->toBeInstanceOf(FunctionCallNode::class)
        ->and($node->arguments[0])->toBeInstanceOf(RangeNode::class)
        ->and($node->arguments[0]->range->toString())->toBe('B3:B90');
});

test('аргументы разделяются и точкой с запятой, и запятой', function (string $formula): void {
    $node = $this->engine->parse($formula);

    expect($node)->toBeInstanceOf(FunctionCallNode::class)
        ->and($node->arguments)->toHaveCount(3);
})->with([
    '=СУММ(1;2;3)',
    '=SUM(1,2,3)',
]);

test('незакрытая скобка отвергается', function (): void {
    $this->engine->parse('=СУММ(B3:B90');
})->throws(SyntaxErrorException::class);

test('незакрытая кавычка отвергается', function (): void {
    $this->engine->parse('="продукты');
})->throws(SyntaxErrorException::class);

test('мусор после выражения отвергается', function (): void {
    $this->engine->parse('=1+2 3');
})->throws(SyntaxErrorException::class);
