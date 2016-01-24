<?php

namespace Tests\Llvdl\Domino\Traits;

use Llvdl\Domino\Game;

trait GameServiceExpectationTrait
{
    private function expectForFindById($gameId, $resultGame, $count = null)
    {
        $this->gameRepositoryMock
            ->expects($count === null ? $this->any() : $this->exactly($count))
            ->method('findById')
            ->with($this->identicalTo($gameId))
            ->willReturn($resultGame);
    }

    private function expectForGetRecentGames($resultGames, $count = null)
    {
        $this->gameRepositoryMock
            ->expects($count === null ? $this->any() : $this->exactly($count))
            ->method('getRecentGames')
            ->willReturn($resultGames);
    }

    private function expectPersistForGame(Game $game, $count = null)
    {
        $expectation = $this->gameRepositoryMock
            ->expects($count === null ? $this->any() : $this->exactly($count))
            ->method('persistGame')
            ->with($this->identicalTo($game))
            ->willReturn($game);
    }
}