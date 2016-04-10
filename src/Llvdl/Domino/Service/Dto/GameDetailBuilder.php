<?php

namespace Llvdl\Domino\Service\Dto;

use Llvdl\Domino\Domain;

class GameDetailBuilder
{
    /** @var int */
    private $_id;
    /** @var string */
    private $_name = 'domino game';
    /** @var string */
    private $_state;
    /** @var Player[] */
    private $_players = [];
    /** @var Stone */
    private $_tableStones = [];
    /** @var Turn */
    private $_currentTurn;

    /**
     * @param int $id
     * 
     * @return GameDetailBuilder
     */
    public function id($id)
    {
        $this->_id = $id;

        return $this;
    }

    /**
     * @param  string            $state
     *                                   
     * @return GameDetailBuilder
     */
    public function state($state)
    {
        $this->_state = $state;

        return $this;
    }

    /** @return GameDetailBuilder */
    public function stateReady()
    {
        $this->_state = GameDetail::STATE_READY;

        return $this;
    }

    /** @return GameDetailBuilder */
    public function stateStarted()
    {
        $this->_state = GameDetail::STATE_STARTED;

        return $this;
    }

    /**
     * @param string $name
     * 
     * @return GameDetailBuilder
     */
    public function name($name)
    {
        $this->_name = $name;

        return $this;
    }

    /**
     * @param int                   $number player number
     * @param array|ArrayCollection $stones
     * @param string                $name   player name
     * 
     * @return GameDetailBuilder
     */
    public function addPlayer($number, /*array*/ $stones)
    {
        $playerNumber = $number;
        $this->_players[] = new Player($playerNumber, $this->mapToStoneDto($stones));

        return $this;
    }

    public function tableStones(array $stones)
    {
        $this->_tableStones = $this->mapToStoneDto($stones);

        return $this;
    }

    /**
     * @param int $turnNumber
     * @param int $currentPlayerNumber
     * 
     * @return GameDetailBuilder
     * */
    public function turn($turnNumber, $currentPlayerNumber)
    {
        $this->_currentTurn = new Turn($turnNumber, $currentPlayerNumber);

        return $this;
    }

    /** @return GameDetailDto */
    public function get()
    {
        return new GameDetail(
            $this->_id,
            $this->_name,
            $this->_state,
            $this->_players,
            $this->_tableStones,
            $this->_currentTurn
        );
    }

    /**
     * @param array|Domain\Stone[] an array of arrays consisting of two values
     *                           (top value, bottom value) or Stone objects
     *
     * @return Stone[]
     */
    private function mapToStoneDto(/*array*/ $stones)
    {
        $stoneDtos = [];
        foreach ($stones as $stone) {
            $stoneDtos[] = is_array($stone) ? new Stone($stone[0], $stone[1]) : new Stone($stone->getTopValue(), $stone->getBottomValue());
        }

        return $stoneDtos;
    }
}
