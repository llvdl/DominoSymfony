<?php

namespace Llvdl\Domino\Domain;

abstract class Move
{
    /**
     * param int $turnNumber.
     */
    public function __construct($turnNumber)
    {
        if ($turnNumber <= 0) {
            throw new Exception\DominoException('invalid turn number');
        }

        $this->turnNumber = $turnNumber;
    }

    public function getTurnNumber()
    {
        return $this->turnNumber;
    }
}
