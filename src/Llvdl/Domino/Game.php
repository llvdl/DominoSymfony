<?php

namespace Llvdl\Domino;

use Llvdl\Domino\Player;
use Llvdl\Domino\State;
use Llvdl\Domino\Stone;

class Game
{
    /** @var int */
    private $id;
    /** @var string */
    private $name;
    /** @var State */
    private $state;
    /** @var Player[]; */
    private $players = [];

    /** @param string $name */
    public function __construct($name)
    {
        $this->id = null;
        $this->name = $name;
        $this->state = State::getInitialState();
        $this->initializePlayers();
    }

    /** @return int */
    public function getId()
    {
        return $this->id;
    }

    /** @return string */
    public function getName()
    {
        return $this->name;
    }

    /** @param string $name */
    public function setName($name)
    {
        $this->name = $name;
    }

    /** @return State state */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return Player[] array of players, the key is the player number
     */
    public function getPlayers()
    {
        $players = [];
        foreach($this->players as $player) {
            $players[$player->getNumber()] = $player;
        }
        return $players;
    }

    public function deal()
    {
        $stones = [];
        for($top = 0; $top < 7; ++$top) {
            for($bottom = $top; $bottom < 7; ++$bottom) {
                $stones[] = new Stone($top, $bottom);
            }
        }
        shuffle($stones);

        foreach($this->players as $player) {
            $player->addStones(array_splice($stones, 0, 7));
        }

        $this->state->start();
    }

    private function initializePlayers()
    {
        $this->players = [];
        foreach([1,2,3,4] as $number) {
            $this->players[] = new Player($this, $number);
        }
    }

    /**
     * Set state
     *
     * @param \Llvdl\Domino\State $state
     *
     * @return Game
     */
    public function setState(\Llvdl\Domino\State $state = null)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Add player
     *
     * @param \Llvdl\Domino\Player $player
     *
     * @return Game
     */
    public function addPlayer(\Llvdl\Domino\Player $player)
    {
        $this->players[] = $player;

        return $this;
    }

    /**
     * Remove player
     *
     * @param \Llvdl\Domino\Player $player
     */
    public function removePlayer(\Llvdl\Domino\Player $player)
    {
        $this->players->removeElement($player);
    }
}
