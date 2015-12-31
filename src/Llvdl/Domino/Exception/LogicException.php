<?php

namespace Llvdl\Domino\Exception;

class LogicException extends DominoException
{
    public function __construct($msg, $code = 0, $prev = null)
    {
        parent::__construct($msg, $code, $prev);
    }
}
