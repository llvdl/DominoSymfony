<?php

namespace Llvdl\Domino\Service\Dto;

class GameDetail
{
    const STATE_READY = 'ready';
    const STATE_STARTED = 'started';
    const STATE_FINISHED = 'finished';

    private $id;
    private $name;
    private $state;
    private $players;
    private $tableStones;
    private $currentTurn;

    /**
     * @param int
     * @param string   $state       state, one of GameDetail::STATE_XXX
     * @param Player[] $players
     * @param Stone[]  $tableStones
     * @param Turn     $currentTurn
     */
    public function __construct($id, $name, $state, array $players, array $tableStones, Turn $currentTurn = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->state = $state;
        $this->players = $players;
        $this->tableStones = $tableStones;
        $this->currentTurn = $currentTurn;
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

    /** @return string state, one of GameDetail::STATE_XXX */
    public function getState()
    {
        return $this->state;
    }

    /** @return Player[] */
    public function getPlayers()
    {
        return $this->players;
    }

    /** @return Stone[] */
    public function getTableStones()
    {
        return $this->tableStones;
    }

    /** @return Turn */
    public function getCurrentTurn()
    {
        return $this->currentTurn;
    }

    /** 
     * @param int $number player number (1 to 4)
     * 
     * @return Player|null player dto with player number or NULL if not found 
     */
    public function getPlayerByNumber($number)
    {
        foreach ($this->getPlayers() as $player) {
            if ($player->getNumber() === intval($number)) {
                return $player;
            }
        }

        return;
    }

    /** return bool */
    public function canDeal()
    {
        return $this->getState() === self::STATE_READY;
    }
}
