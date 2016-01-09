<?php

namespace Llvdl\Domino;

use Llvdl\Domino\Player;

class Stone
{
    /**
     * only for use by the ORM
     * @var integer
     */
    private $id;
    /**
     * only for use by the ORM
     * @var Player
     */
    private $player;

    /** @var integer */
    private $topValue;
    /** @var integer */
    private $bottomValue;

    /**
     * @param integer $topValue
     * @param integer $bottomValue
     */
    public function __construct($topValue, $bottomValue)
    {
        $this->topValue = $topValue;
        $this->bottomValue = $bottomValue;
    }

    /** @return integer */
    public function getTopValue()
    {
        return $this->topValue;
    }

    /** @return integer */
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
     * @return boolean TRUE if stone is equal to other stone, otherwise false
     */
    public function isEqual(Stone $other)
    {
        return $this->topValue === $other->topValue && $this->bottomValue === $other->bottomValue;
    }
}