<?php

namespace Llvdl\Domino;

use Llvdl\Domino\Stone;
use Llvdl\Domino\Game;

class Player
{
    /** @var integer only used by ORM layer */
    private $id;

    /** @var Game $game */
    private $game;
    /** @var integer number */
    private $number;
    /** @var string $name */
    private $name;
    /** @var Stone[] */
    private $stones = [];

    public function __construct(Game $game, $number, $name = '')
    {
        $this->game = $game;
        $this->number = $number;
        $this->name = $name;
    }

    /** @return integer */
    public function getNumber()
    {
        return $this->number;
    }

    /** @return string */
    public function getName()
    {
        return $this->name;
    }

    /** @return Stone[] */
    public function getStones()
    {
        return $this->stones;
    }

    /** 
     * @param Stone[] $stones 
     *
     * @todo remove horrible clutch to assign player to stone to have the ORM persist stones
     */
    public function addStones(array $stones)
    {
        foreach($stones as $stone)
        {
            $stone->setPlayer($this);
        }
        $this->stones = array_merge(is_array($this->stones) ? $this->stones : [], $stones);
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set number
     *
     * @param integer $number
     *
     * @return Player
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Player
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
