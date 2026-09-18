<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Exceptions;

use Finance\Domains\Core\Exceptions\DomainException;
use Throwable;

/**
 * Ячейка записана, но не читается обратно. В обычной работе не возникает —
 * означает, что база отдаёт не то, что только что приняла.
 */
final class CellNotSavedException extends DomainException
{
    public function __construct(string $address, ?Throwable $previous = null)
    {
        parent::__construct("Не удалось сохранить ячейку {$address}", 500, $previous);
    }
}
