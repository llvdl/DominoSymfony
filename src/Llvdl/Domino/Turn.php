<?php

namespace Llvdl\Domino;

class Turn
{
    /** @var int id, only used by ORM layer */
    private $id;

    /** @var int */
    private $number;

    /** @var int */
    private $playerNumber;

    public function __construct($number, $playerNumber)
    {
        $this->number = $number;
        $this->playerNumber = $playerNumber;
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function getPlayerNumber()
    {
        return $this->playerNumber;
    }

    public function isEqual(Turn $other)
    {
        return $this->getNumber() === $other->getNumber()
            && $this->getPlayerNumber() === $other->getPlayerNumber();
    }
}
