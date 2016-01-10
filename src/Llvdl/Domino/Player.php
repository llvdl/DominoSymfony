<?php

namespace Llvdl\Domino;

class Player
{
    /** @var int only used by ORM layer */
    private $id;

    /** @var Game $game */
    private $game;
    /** @var int number */
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

    /** @return int */
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
        foreach ($stones as $stone) {
            $stone->setPlayer($this);
        }
        $this->stones = array_merge(is_array($this->stones) ? $this->stones : [], $stones);
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set number.
     *
     * @param int $number
     *
     * @return Player
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Set name.
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

    /**
     * Determines if the player can start the game (i.e. make the first move).
     *
     * @return bool TRUE if the the player can start the game, otherwise FALSE
     */
    public function canStart()
    {
        $doubleSix = new Stone(6, 6);
        foreach ($this->getStones() as $stone) {
            if ($stone->isEqual($doubleSix)) {
                return true;
            }
        }

        return false;
    }
}
