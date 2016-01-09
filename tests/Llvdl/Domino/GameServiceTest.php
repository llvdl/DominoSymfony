<?php

namespace Tests\Llvdl\Domino;

use Llvdl\Domino\Game;
use Llvdl\Domino\GameService;
use Llvdl\Domino\GameRepository;
use Llvdl\Domino\Dto\GameDetailDto;
use Llvdl\Domino\State;

class GameServiceTest extends \PHPUnit_Framework_TestCase
{
    /** @var gameService */
    private $gameService;
    /** @var GameRepository */
    private $gameRepositoryMock;

    public function setUp()
    {
        $this->gameRepositoryMock = $this->getMockBuilder('Llvdl\Domino\GameRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->gameService = new gameService($this->gameRepositoryMock);
    }

    public function testGetGameById()
    {
        $game = $this->createGame(14, 'game test');
        $this->expectForFindById(14, $game);

        $gameDetailDto = $this->gameService->getGameById(14);
        $this->assertInstanceOf(\Llvdl\Domino\Dto\GameDetailDto::class, $gameDetailDto);
        $this->assertSame(14, $gameDetailDto ->getId());
        $this->assertSame('game test', $gameDetailDto ->getName());
    }

    public function testGetGameByIdNotFound()
    {
        $this->expectForFindById(14, NULL);
        $this->assertNull($this->gameService->getGameById(14));
    }

    public function testGetRecentGames()
    {
        $games = [
            $this->createGame(12, 'game 12'),
            $this->createGame(4, 'game 4'),
            $this->createGame(1, 'game 1'),
        ];
        $this->expectsForGetRecentGames($games);

        $gamesDetailDtos = $this->gameService->getRecentGames();
        $this->assertCount(3, $gamesDetailDtos);
        for($i = 0; $i < 3; ++$i) {
            $this->assertInstanceOf(\Llvdl\Domino\Dto\GameDetailDto::class, $gamesDetailDtos[$i]);
            $this->assertSame($games[$i]->getId(), $gamesDetailDtos[$i]->getId());
            $this->assertSame($games[$i]->getName(), $gamesDetailDtos[$i]->getName());
        }
    }

    public function testGetRecentGamesNonAvailable()
    {
        $this->expectsForGetRecentGames([]);

        $gamesDetailDtos = $this->gameService->getRecentGames();
        $this->assertTrue(is_array($gamesDetailDtos), 'return value is an array');
        $this->assertCount(0, $gamesDetailDtos, 'the array is empty');
    }

    public function testCreateGame()
    {
        $this->gameRepositoryMock->expects($this->once())->method('persistGame')
            ->with($this->isInstanceOf(\Llvdl\Domino\Game::class))
            ->will($this->returnCallback(function(Game $game) {
                // persisting sets the id
                $this->setPrivateProperty($game, 'id', 42);
                return $game;
            }));

        $gameId = $this->gameService->createGame('my game');
        $this->assertSame(42, $gameId, 'the id of the newly created game is returned');
    }

    public function testCreatedGameHasFourPlayersWithNoStones()
    {
        $persistedGame = null;
        $this->gameRepositoryMock->expects($this->once())->method('persistGame')
            ->with($this->isInstanceOf(\Llvdl\Domino\Game::class))
            ->will($this->returnCallback(function(Game $game) use(&$persistedGame) {
                // persisting sets the id
                $this->setPrivateProperty($game, 'id', 42);
                $persistedGame = $game;
                return $game;
            }));

        $this->gameRepositoryMock->expects($this->any())->method('findById')
            ->with($this->identicalTo(42))
            ->will($this->returnCallback(function() use(&$persistedGame) { return $persistedGame; }));

        $gameId = $this->gameService->createGame('my game');
        $gameDetailDto = $this->gameService->getGameById($gameId);
        $this->assertNotNull($gameDetailDto);
        $this->assertInstanceOf(\Llvdl\Domino\Dto\GameDetailDto::class, $gameDetailDto);

        $this->assertCount(4, $gameDetailDto->getPlayers());
        foreach($gameDetailDto->getPlayers() as $player) {
            $this->assertInstanceOf(\Llvdl\Domino\Dto\PlayerDto::class, $player);
            $this->assertCount(0, $player->getStones());
        }
    }

    public function testDealGame()
    {
        $game = $this->createGame(42, 'some game');
        $this->assertTrue($game->getState()->isEqual(new State(State::READY)));

        $this->gameRepositoryMock->expects($this->once())->method('findById')
            ->with($this->identicalTo(42))
            ->willReturn($game);

        $this->gameRepositoryMock->expects($this->once())->method('persistGame')
            ->with($this->identicalTo($game))
            ->willReturn($game);

        $this->gameService->deal(42);
        $this->assertTrue($game->getState()->isEqual(new State(State::STARTED)));
    }

    public function testAfterDealPlayersHaveSevenStonesEach()
    {
        $game = $this->createGame(42, 'some game');

        $this->assertCount(4, $game->getPlayers());
        $this->assertCount(0, $game->getPlayers()[1]->getStones());
        $this->assertCount(0, $game->getPlayers()[2]->getStones());
        $this->assertCount(0, $game->getPlayers()[3]->getStones());
        $this->assertCount(0, $game->getPlayers()[4]->getStones());

        $this->gameRepositoryMock->expects($this->any())->method('findById')
            ->with($this->identicalTo(42))
            ->willReturn($game);

        $this->gameRepositoryMock->expects($this->once())->method('persistGame')
            ->with($this->identicalTo($game))
            ->willReturn($game);

        $this->gameService->deal(42);
        $this->assertTrue($game->getState()->isEqual(new State(State::STARTED)));

        $gameDetailDto = $this->gameService->getGameById(42);

        $this->assertCount(7, $gameDetailDto->getPlayers()[0]->getStones());
        $this->assertCount(7, $gameDetailDto->getPlayers()[1]->getStones());
        $this->assertCount(7, $gameDetailDto->getPlayers()[2]->getStones());
        $this->assertCount(7, $gameDetailDto->getPlayers()[3]->getStones());
    }

    /** @expectedException \Llvdl\Domino\Exception\DominoException */
    public function testDealGameWithNonExistingGameThrowsException()
    {
        $this->gameRepositoryMock->expects($this->once())->method('findById')
            ->with($this->identicalTo(42))
            ->willReturn(NULL);

        $this->gameService->deal(42);
    }

    private function createGame($id, $name)
    {
        $game = new Game($name);
        $this->setPrivateProperty($game, 'id', $id);
        return $game;
    }

    private function setPrivateProperty($obj, $name, $value)
    {
        $property = new \reflectionproperty(get_class($obj), $name);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
    }

    private function expectForFindById($gameId, $resultGame)
    {
        $this->gameRepositoryMock->expects($this->any())->method('findById')
            ->with($this->identicalTo($gameId))
            ->willReturn($resultGame);
    }

    private function expectsForGetRecentGames($resultGames)
    {
        $this->gameRepositoryMock->expects($this->any())->method('getRecentGames')
            ->willReturn($resultGames);

    }
}