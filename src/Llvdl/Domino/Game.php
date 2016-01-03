<?php

namespace Llvdl\Domino;

use Llvdl\Domino\Player;
use Llvdl\Domino\State;

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
    
    /** @return Player[] players */
    public function getPlayers()
    {
        return $this->players;
    }

    public function deal()
    {
        $this->state->start();
    }

    private function initializePlayers()
    {
        $this->players = [];
        foreach([1,2,3,4] as $number) {
            $this->players[$number] = new Player($this, $number);
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
