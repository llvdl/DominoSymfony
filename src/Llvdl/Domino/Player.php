<?php

namespace Llvdl\Domino;

use Llvdl\Domino\Exception\InvalidMoveException;

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

    /**
     * @param $stone
     *
     * @return bool
     */
    public function hasStone(Stone $stone)
    {
        foreach ($this->stones as $playerStone) {
            if ($stone->isEqual($playerStone)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Stone $stone
     */
    public function removeStone(Stone $stone)
    {
        foreach ($this->stones as $k => $playerStone) {
            if ($stone->isEqual($playerStone)) {
                unset($this->stones[$k]);
            }
        }
    }

    /**
     * @param Play $play
     */
    public function play(Play $play)
    {
        if (!$this->hasStone($play->getStone())) {
            throw new InvalidMoveException('player does not have stone');
        }

        try {
            $this->removeStone($play->getStone());
            $this->game->addMove($this, $play);
        } catch (InvalidMoveException $e) {
            $this->addStones([$play->getStone()]);
            throw $e;
        }
    }

    public function __toString()
    {
        return 'Player(number: '.$this->number.')';
    }
}
