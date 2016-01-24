<?php

namespace Llvdl\Domino\Exception;

class InvalidMoveException extends DominoException
{
    public function __construct($msg, $code = 0, $previous = null)
    {
        parent::__construct($msg, $code, $previous);
    }
}
