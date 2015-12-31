<?php

namespace Llvdl\Domino\Dto;

class GameDetailDtoBuilder
{
    /** @var int */
    private $_id;
    /** @var string */
    private $_name = 'domino game';
    /** @var string */
    private $_state;
    /** @var PlayerDto[] */
    private $_players = [];
    /** @var StoneDto */
    private $_tableStones = [];
    /** @var TurnDto */
    private $_currentTurn;

    public function id($id)
    {
        $this->_id = $id;

        return $this;
    }

    public function state($state)
    {
        $this->_state = $state;

        return $this;
    }

    public function stateReady()
    {
        $this->_state = GameDetailDto::STATE_READY;

        return $this;
    }

    public function stateStarted()
    {
        $this->_state = GameDetailDto::STATE_STARTED;

        return $this;
    }

    public function name($name)
    {
        $this->_name = $name;

        return $this;
    }

    public function addPlayer(array $stones, $name = '')
    {
        $playerNumber = count($this->_players) + 1;
        $playerName = ($name === '' ? 'player '.$playerNumber : $name);
        $this->_players[] = new PlayerDto($playerNumber, $playerName, $this->mapToStoneDto($stones));

        return $this;
    }

    public function tableStones(array $stones)
    {
        $this->_tableStones = $this->mapToStoneDto($stones);
    }

    public function turn($turnNumber, $currentPlayerNumber)
    {
        $this->_currentTurn = new CurrentTurnDto($turnNumber, $currentPlayerNumber);

        return $this;
    }

    /** @return GameDetailDto */
    public function get()
    {
        return new GameDetailDto($this->_id, $this->_name, $this->_state, $this->_players, $this->_tableStones, $this->_currentTurn);
    }

    private function mapToStoneDto(array $stones)
    {
        return array_map(function ($stone) {
            return is_array($stone) ? new StoneDto($stone[0], $stone[1]) : $stone;
        }, $stones);
    }
}
