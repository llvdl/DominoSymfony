<?php

namespace Llvdl\Domino\Domain;

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

    /**
     * @return Turn
     */
    public function next()
    {
        $turnNumber = $this->getNumber() + 1;
        $playerNumber = $this->getPlayerNumber() === 4 ? 1 : $this->getPlayerNumber() + 1;

        return new self($turnNumber, $playerNumber);
    }
}
