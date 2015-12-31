<?php

namespace Llvdl\DominoDto;

class TurnDto
{
    /** @var int */
    private $number;
    /** @var int */
    private $currentPlayerNumber;

    public function __construct($number, $currentPlayerNumber)
    {
        $this->number = $number;
        $this->currentPlayerNumber = $currentPlayerNumber;
    }

    /** @return int */
    public function getNumber()
    {
        return $this->number;
    }

    /** @return int */
    public function getCurrentPlayerNumber()
    {
        return $this->currentPlayerNumber;
    }
}
