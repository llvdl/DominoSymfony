<?php

namespace Llvdl\Domino\Domain\Exception;

class DominoException extends \Exception
{
    public function __construct($msg, $code = 0, $previous = null)
    {
        parent::__construct($msg, $code, $previous);
    }
}
