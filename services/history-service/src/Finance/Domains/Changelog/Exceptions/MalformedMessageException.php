<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Exceptions;

use UnexpectedValueException;

/**
 * В сообщении из очереди нет нужного поля или у поля не тот тип.
 *
 * Отдельный тип нужен потребителю: такое сообщение не исправится от повторной
 * доставки, и его надо отбросить, а не возвращать в очередь.
 */
final class MalformedMessageException extends UnexpectedValueException
{
    public function __construct(string $field)
    {
        parent::__construct("В сообщении нет поля «{$field}» нужного вида");
    }
}
