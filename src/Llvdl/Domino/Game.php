<?php

namespace Llvdl\Domino;

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
    /** @var Turn */
    private $currentTurn;

    /** @param string $name */
    public function __construct($name)
    {
        $this->id = null;
        $this->name = $name;
        $this->state = State::getInitialState();
        $this->initializePlayers();
        $this->currentTurn = null;
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
        foreach ($this->players as $player) {
            $players[$player->getNumber()] = $player;
        }

        return $players;
    }

    /**
     * @param int $playerNumber
     *
     * @return Player|null
     */
    public function getPlayerByPlayerNumber($playerNumber)
    {
        foreach ($this->players as $player) {
            if ($player->getNumber() === $playerNumber) {
                return $player;
            }
        }

        return;
    }

    /**
     * @return Turn|null current turn or NULL if game has not started or has finished
     */
    public function getCurrentTurn()
    {
        return $this->currentTurn;
    }

    /**
     * Deals the stones to the players and starts the game.
     */
    public function deal()
    {
        $stones = [];
        for ($top = 0; $top < 7; ++$top) {
            for ($bottom = $top; $bottom < 7; ++$bottom) {
                $stones[] = new Stone($top, $bottom);
            }
        }
        shuffle($stones);

        foreach ($this->players as $player) {
            $player->addStones(array_splice($stones, 0, 7));
        }

        $this->setFirstTurn();
        $this->state->start();
    }

    /**
     * Set state.
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
     * Add player.
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
     * Remove player.
     *
     * @param \Llvdl\Domino\Player $player
     */
    public function removePlayer(\Llvdl\Domino\Player $player)
    {
        $this->players->removeElement($player);
    }

    private function initializePlayers()
    {
        $this->players = [];
        foreach ([1, 2, 3, 4] as $number) {
            $this->players[] = new Player($this, $number);
        }
    }

    /**
     * creates the first turn and assigns it to the player that can start.
     */
    private function setFirstTurn()
    {
        $startingPlayer = null;
        foreach ($this->getPlayers() as $player) {
            if ($player->canStart()) {
                $startingPlayer = $player;
                break;
            }
        }
        $this->currentTurn = new Turn(1, $startingPlayer->getNumber());
    }
}
