<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Dependencies;

use Finance\FormulaEngine\Ast\BinaryOperationNode;
use Finance\FormulaEngine\Ast\FunctionCallNode;
use Finance\FormulaEngine\Ast\Node;
use Finance\FormulaEngine\Ast\RangeNode;
use Finance\FormulaEngine\Ast\ReferenceNode;
use Finance\FormulaEngine\Ast\UnaryOperationNode;
use Finance\FormulaEngine\Values\CellRange;
use Finance\FormulaEngine\Values\CellReference;

/**
 * Обходит дерево разбора и собирает всё, на что ссылается формула.
 */
final class DependencyExtractor
{
    public function extract(Node $node): CellDependencies
    {
        /** @var list<CellReference> $references */
        $references = [];
        /** @var list<CellRange> $ranges */
        $ranges = [];

        $this->walk($node, $references, $ranges);

        return new CellDependencies($references, $ranges);
    }

    /**
     * @param list<CellReference> $references
     * @param list<CellRange>     $ranges
     */
    private function walk(Node $node, array &$references, array &$ranges): void
    {
        if ($node instanceof ReferenceNode) {
            $references[] = $node->reference;

            return;
        }

        if ($node instanceof RangeNode) {
            $ranges[] = $node->range;

            return;
        }

        if ($node instanceof UnaryOperationNode) {
            $this->walk($node->operand, $references, $ranges);

            return;
        }

        if ($node instanceof BinaryOperationNode) {
            $this->walk($node->left, $references, $ranges);
            $this->walk($node->right, $references, $ranges);

            return;
        }

        if ($node instanceof FunctionCallNode) {
            foreach ($node->arguments as $argument) {
                $this->walk($argument, $references, $ranges);
            }
        }
    }
}
