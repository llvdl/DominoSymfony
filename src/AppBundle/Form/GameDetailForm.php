<?php

namespace AppBundle\Form;

class GameDetailForm
{
    /** @var int */
    private $gameId;

    /** @var bool */
    private $canDeal = false;

    public function __construct($gameId)
    {
        $this->gameId = $gameId;
    }

    public function getGameId()
    {
        return $this->gameId;
    }

    public function setCanDeal($canDeal)
    {
        $this->canDeal = $canDeal;
    }

    public function getCanDeal()
    {
        return $this->canDeal;
    }
}
