<?php

namespace Llvdl\Domino;

class Stone
{
    /**
     * only for use by the ORM.
     *
     * @var int
     */
    private $id;
    /**
     * only for use by the ORM.
     *
     * @var Player
     */
    private $player;

    /** @var int */
    private $topValue;
    /** @var int */
    private $bottomValue;

    /**
     * @param int $topValue
     * @param int $bottomValue
     */
    public function __construct($topValue, $bottomValue)
    {
        $this->topValue = $topValue;
        $this->bottomValue = $bottomValue;
    }

    /** @return int */
    public function getTopValue()
    {
        return $this->topValue;
    }

    /** @return int */
    public function getBottomValue()
    {
        return $this->bottomValue;
    }

    public function setPlayer(Player $player)
    {
        $this->player = $player;
    }

    /** 
     * @param Stone $other
     *
     * @return bool TRUE if stone is equal to other stone, otherwise false
     */
    public function isEqual(Stone $other)
    {
        return $this->topValue === $other->topValue && $this->bottomValue === $other->bottomValue;
    }
}
