<?php

namespace Tests\Llvdl\Domino;

use Llvdl\Domino\Game;
use Llvdl\Domino\GameService;
use Llvdl\Domino\GameRepository;
use Llvdl\Domino\Dto\GameDetailDto;
use Llvdl\Domino\Dto\PlayerDto;
use Llvdl\Domino\Dto\StoneDto;
use Llvdl\Domino\State;

/**
 * GameService API:
 * - getGameById($gameId: int): GameDetailDto|NULL
 * - getRecentGames(): GameDetailDto[]
 * - deal($gameId: int): void
 * - move($move: MoveDto): void
 */
class GameServiceTest extends \PHPUnit_Framework_TestCase
{
    const ONCE = 1;

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
        $this->expectForGetRecentGames($games);

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
        $this->expectForGetRecentGames([]);

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
        $this->expectForFindById(42, $game);
        $this->expectPersistForGame($game, self::ONCE);

        $gameDetailDto = $this->gameService->getGameById(42);

        $this->assertCount(4, $gameDetailDto->getPlayers());
        foreach($gameDetailDto->getPlayers() as $player) {
            $this->assertStoneCount(0, $player);
        }

        $this->gameService->deal(42);
        $this->assertTrue($game->getState()->isEqual(new State(State::STARTED)));

        $gameDetailDto = $this->gameService->getGameById(42);
        $this->assertCount(4, $gameDetailDto->getPlayers());
        foreach($gameDetailDto->getPlayers() as $player) {
            $this->assertStoneCount(7, $player);
        }
    }

    public function testAfterDealPlayerWithDoubleSixHasTurn()
    {
        $game = $this->createGame(42, 'test game');
        $this->expectForFindById(42, $game);
        $this->expectPersistForGame($game);

        $gameDetailDto = $this->gameService->getGameById(42);
        $this->assertNull($gameDetailDto->getCurrentTurn());

        $this->gameService->deal(42);
        $gameDetailDto = $this->gameService->getGameById(42);

        // check if player with current turn has double six
        $turnDto = $gameDetailDto->getCurrentTurn();
        $this->assertNotNull($turnDto);
        $currentPlayerNumber = $turnDto->getCurrentPlayerNumber();
        $this->assertPlayerHasStone($gameDetailDto, $currentPlayerNumber, new StoneDto(6, 6));
    }

    /** @expectedException \Llvdl\Domino\Exception\DominoException */
    public function testDealGameWithNonExistingGameThrowsException()
    {
        $this->expectForFindById(42, NULL);
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

    private function assertStoneCount($count, PlayerDto $player)
    {
        $this->assertCount($count, $player->getStones());
    }

    private function assertPlayerHasStone(GameDetailDto $game, $playerNumber, StoneDto $stoneDto)
    {
        $playerWithPlayerNumber = null;
        foreach($game->getPlayers() as $player) {
            if($player->getNumber() === $playerNumber) {
                $this->assertNull($playerWithPlayerNumber, 'only one player has given player number');
                $playerWithPlayerNumber = $player;
            }
        }
        $this->assertNotNull($playerWithPlayerNumber, 'player with player number found');
        $this->assertContainsStone($playerWithPlayerNumber->getStones(), $stoneDto);
    }

    private function assertContainsStone(array $stones, StoneDto $stone)
    {
        $containsStone = array_reduce(
            $stones,
            function($carry, StoneDto $other) use($stone) { 
                return $carry ||
                    ($stone->getTopValue() === $other->getTopValue()
                    && $stone->getBottomValue() === $other->getBottomValue());
            },
            false
        );
        $this->assertTrue($containsStone, 'expected to find stone in stone collection');
    }
}