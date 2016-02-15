<?php

namespace Tests\AppBundle\Controller\Traits;

use Llvdl\Domino\Service\GameService;
use Llvdl\Domino\Service\Dto;
use Symfony\Component\DomCrawler\Crawler;

trait GameServiceExpectationTrait
{
    /** @return GameService */
    private function getGameServiceMock()
    {
        $container = $this->getClient()->getContainer(); 

        return $container->mock('app.game_service', GameService::class);
    }

    /** @param Dto\GameDetail[] $result */
    private function expectForRecentGames(array $result, $count = 1)
    {
        $expectation = $this->getGameServiceMock()
            ->shouldReceive('getRecentGames')
            ->andReturn($result);

        if($count !== null) {
            $expectation->times($count);
        }
    }

    /**
     * @param integer $id
     * @param Dto\GameDetail|callback|NULL $result
     * @param bool $isOnce TRUE if call is expected exactly once
     */
    private function expectForGameById($id, $result, $count = 1)
    {
        $expectation = $this->getGameServiceMock()
            ->shouldReceive('getGameById')->with($id);

        if(is_callable($result)) {
            $expectation->andReturnUsing($result);
        } else {
            $expectation->andReturn($result);
        }

        if($count !== null) {
            $expectation->times($count);
        }
    }

    /**
     * @param string $gameName
     * @param integer $result game id result
     */
    private function expectCreateGame($gameName, $result)
    {
        $expectation = $this->getGameServiceMock()
            ->shouldReceive('createGame')
            ->with($gameName);

        if(is_callable($result)) {
            $expectation->andReturnUsing($result);
        } else {
            $expectation->andReturn($result);
        }
    }

    /** @param integer $gameId */
    private function expectForDeal($gameId, $callback = null)
    {
        $this->getGameServiceMock()
            ->shouldReceive('deal')
            ->with($gameId)
            ->once()
            ->andReturnUsing(function() use($callback) {
                if($callback) {
                    $callback();
                }
                return;
            });
    }

    /**
     * @param integer $id gameId
     * @param Exception $e
     */
    private function expectExceptionForGameById($gameId, \Exception $e)
    {
        $this->getGameServiceMock()
            ->shouldReceive('deal')
            ->with($gameId)
            ->once()
            ->andThrow($e);
    }

    /**
     * @param int $gameId
     * @param int $playerId
     * @param Dto\Play $play
     * @param callable|null $callback
     */
    private function expectForPlay($gameId, $playerId, Dto\Play $play, callable $callback = null)
    {
        $this->getGameServiceMock()
            ->shouldReceive('play')
            ->with($gameId, $playerId, \Mockery::on(function($p) use($play) {
                return $p instanceof Dto\Play
                    && $p->isEqual($play);
            }))
            ->once()
            ->andReturnUsing(function() use($callback) {
                if($callback) {
                    $callback();
                }
                return;
            });
    }
}
