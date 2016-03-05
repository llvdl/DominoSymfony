<?php

namespace Llvdl\Domino\Domain;

use Llvdl\Domino\Domain\Exception\InvalidArgumentException;

abstract class Move
{
    /** @var int */
    private $turnNumber;

    /**
     * param int $turnNumber.
     */
    public function __construct($turnNumber)
    {
        if (!is_int($turnNumber) || $turnNumber <= 0) {
            throw new InvalidArgumentException('invalid turn number');
        }

        $this->turnNumber = intval($turnNumber);
    }

    /** @return int */
    public function getTurnNumber()
    {
        return $this->turnNumber;
    }
}
