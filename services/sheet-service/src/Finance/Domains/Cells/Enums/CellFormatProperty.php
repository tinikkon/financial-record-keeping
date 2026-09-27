<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Enums;

/**
 * Свойства оформления под теми именами, под которыми они лежат в базе
 * и приходят от клиента.
 */
enum CellFormatProperty: string
{
    case Background = 'background';
    case Bold = 'bold';
    case Italic = 'italic';
    case Align = 'align';
    case Decimals = 'decimals';
}
