<?php

namespace Llvdl\Domino\Dto;

class GameDetailDto
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
     * @var int
     * @var string      $state state, one of GameDetail::STATE_XXX
     * @var PlayerDto[] $players
     * @var StoneDto[]  $tableStones
     * @var TurnDto     $currentTurn
     */
    public function __construct($id, $name, $state, array $players, array $tableStones, TurnDto $currentTurn = null)
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

    /** @return PlayerDto[] */
    public function getPlayers()
    {
        return $this->players;
    }

    /** @return StoneDto[] */
    public function getTableStones()
    {
        return $this->tableStones;
    }

    /** @return TurnDto */
    public function getCurrentTurn()
    {
        return $this->currentTurn;
    }

    /** @return PlayerDto|null player dto with player number or NULL if not found */
    public function getPlayerByNumber($number)
    {
        foreach ($this->getPlayers() as $player) {
            if ($player->getNumber() === intval($number)) {
                return $player;
            }
        }

        return;
    }
}
