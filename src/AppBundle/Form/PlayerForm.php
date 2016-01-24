<?php

namespace AppBundle\Form;

class PlayerForm
{
    /** @var int */
    private $gameId;

    /** @var int */
    private $playerNumber;

    /** @var int */
    private $turnNumber;

    /** @var Move */
    private $move;

    /** @var Move[] */
    private $moves = [];

    /**
     * @param int      $gameId
     * @param int      $playerNumber
     * @param int|null $turnNumber
     * @param Move[]   $moves
     */
    public function __construct($gameId, $playerNumber, $turnNumber, array $moves)
    {
        $this->gameId = $gameId;
        $this->playerNumber = $playerNumber;
        $this->turnNumber = $turnNumber;
        $this->moves = $moves;
    }

    /** @return int */
    public function getGameId()
    {
        return $this->gameId;
    }

    /** @return int */
    public function getPlayerNumber()
    {
        return $this->playerNumber;
    }

    /** @return int */
    public function getTurnNumber()
    {
        return $this->turnNumber;
    }

    /** @return Move[] */
    public function getMoves()
    {
        return $this->moves;
    }

    /** @param Move $move */
    public function setMove(Move $move = null)
    {
        $this->move = $move;
    }

    /** @return Move|null */
    public function getMove()
    {
        return $this->move;
    }
}
