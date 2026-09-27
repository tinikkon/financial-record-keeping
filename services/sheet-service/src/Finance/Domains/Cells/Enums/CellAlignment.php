<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Enums;

enum CellAlignment: string
{
    case Left = 'left';
    case Center = 'center';
    case Right = 'right';
}
