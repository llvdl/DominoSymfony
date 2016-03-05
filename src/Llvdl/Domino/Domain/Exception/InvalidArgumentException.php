<?php

namespace Llvdl\Domino\Domain\Exception;

class InvalidArgumentException extends DominoException
{
    public function __construct($msg, $code = 0, $prev = null)
    {
        parent::__construct($msg, $code, $prev);
    }
}
