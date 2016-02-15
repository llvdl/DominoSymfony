<?php

namespace Tests\Llvdl\Domino\Service;

use Llvdl\Domino\Service\GameService;
use Llvdl\Domino\Service\Dto;
use Llvdl\Domino\Domain;
use Tests\Llvdl\Domino\Traits;
use Tests\Llvdl\Domino\Constraint;

/**
 * GameService API:
 * - getGameById($gameId: int): GameDetailDto|NULL
 * - getRecentGames(): GameDetailDto[]
 * - deal($gameId: int): void
 * - move($move: MoveDto): void
 * - play($gameId, $playerNumber, PlayDto): void
 */
class GameServiceTest extends \PHPUnit_Framework_TestCase
{
    use Traits\PrivatePropertySetter;
    use Traits\GameServiceExpectationTrait;

    const ONCE = 1;

    /** @var GameService */
    private $gameService;
    
    /** @var Domain\GameRepository */
    private $gameRepositoryMock;

    public function setUp()
    {
        $this->gameRepositoryMock = $this->getMockBuilder(Domain\GameRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->gameService = new gameService($this->gameRepositoryMock);
    }

    public function testGetGameById()
    {
        $game = $this->createGame(14, 'game test');
        $this->expectForFindById(14, $game);

        $gameDetailDto = $this->gameService->getGameById(14);
        $this->assertInstanceOf(Dto\GameDetail::class, $gameDetailDto);
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

        $gameDetailDtos = $this->gameService->getRecentGames();
        $this->assertCount(3, $gameDetailDtos);
        for($i = 0; $i < 3; ++$i) {
            $this->assertInstanceOf(Dto\GameDetail::class, $gameDetailDtos[$i]);
            $this->assertSame($games[$i]->getId(), $gameDetailDtos[$i]->getId());
            $this->assertSame($games[$i]->getName(), $gameDetailDtos[$i]->getName());
        }
    }

    public function testGetRecentGamesNonAvailable()
    {
        $this->expectForGetRecentGames([]);

        $gameDetailDtos = $this->gameService->getRecentGames();
        $this->assertTrue(is_array($gameDetailDtos), 'return value is an array');
        $this->assertCount(0, $gameDetailDtos, 'the array is empty');
    }

    public function testCreateGame()
    {
        $this->gameRepositoryMock->expects($this->once())->method('persistGame')
            ->with($this->isInstanceOf(Domain\Game::class))
            ->will($this->returnCallback(function(Domain\Game $game) {
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
            ->with($this->isInstanceOf(Domain\Game::class))
            ->will($this->returnCallback(function(Domain\Game $game) use(&$persistedGame) {
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
        $this->assertInstanceOf(Dto\GameDetail::class, $gameDetailDto);

        $this->assertCount(4, $gameDetailDto->getPlayers());
        foreach($gameDetailDto->getPlayers() as $player) {
            $this->assertInstanceOf(Dto\Player::class, $player);
            $this->assertCount(0, $player->getStones());
        }
    }

    public function testDealGame()
    {
        $game = $this->createGame(42, 'some game');
        $this->assertTrue($game->getState()->isEqual(new Domain\State(Domain\State::READY)));

        $this->gameRepositoryMock->expects($this->once())->method('findById')
            ->with($this->identicalTo(42))
            ->willReturn($game);

        $this->gameRepositoryMock->expects($this->once())->method('persistGame')
            ->with($this->identicalTo($game))
            ->willReturn($game);

        $this->gameService->deal(42);
        $this->assertTrue($game->getState()->isEqual(new Domain\State(Domain\State::STARTED)));
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
        $this->assertTrue($game->getState()->isEqual(new Domain\State(Domain\State::STARTED)));

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
        $this->assertPlayerHasStone($gameDetailDto, $currentPlayerNumber, new Dto\Stone(6, 6));
    }

    public function testPlayCallsPlayOnPlayer()
    {
        $game = $this->createGameMock(42);
        $player = $this->createPlayerMock($game, 1, 'player 1');
        
        $this->gameRepositoryMock
            ->expects($this->once())
            ->method('findById')
            ->with($this->identicalTo(42))
            ->willReturn($game);
        
        $game
            ->expects($this->any())
            ->method('getPlayerByPlayerNumber')
            ->with($this->equalTo(3))
            ->willReturn($player);
            
        $player
            ->expects($this->once())
            ->method('play')
            ->with($this->equalToPlay(new Domain\Play(1, new Domain\Stone(6,6), Domain\Table::SIDE_LEFT)));

        $turnNumber = 1;
        $playerNumber = 3;
        $play = new Dto\Play($turnNumber, new Dto\Stone(6,6), Dto\Play::SIDE_LEFT);
        $this->gameService->play(42, $playerNumber, $play);
    }

    /** @expectedException \Llvdl\Domino\Domain\Exception\DominoException */
    public function testDealGameWithNonExistingGameThrowsException()
    {
        $this->expectForFindById(42, NULL);
        $this->gameService->deal(42);
    }

    private function createGame($id, $name, $players = [])
    {
        $game = new Domain\Game($name);
        $this->setPrivateProperty($game, 'id', $id);
        return $game;
    }

    private function assertStoneCount($count, Dto\Player $player)
    {
        $this->assertCount($count, $player->getStones());
    }

    private function assertPlayerHasStone(Dto\GameDetail $game, $playerNumber, Dto\Stone $stoneDto)
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

    private function assertContainsStone(array $stones, Dto\Stone $stone)
    {
        $containsStone = array_reduce(
            $stones,
            function($carry, Dto\Stone $other) use($stone) { 
                return $carry ||
                    ($stone->getTopValue() === $other->getTopValue()
                    && $stone->getBottomValue() === $other->getBottomValue());
            },
            false
        );
        $this->assertTrue($containsStone, 'expected to find stone in stone collection');
    }
    
    private function createGameMock($id = null)
    {
        $mock = $this->getMockBuilder(Domain\Game::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $mock->expects($this->any())->method('getId')->willReturn($id);
        
        return $mock;
    }

    private function createPlayerMock(Domain\Game $game, $playerNumber, $name)
    {
        $mock = $this->getMockBuilder(Domain\Player::class)
            ->setConstructorArgs([$game, $playerNumber, $name])
            ->getMock();

        $mock->expects($this->any())->method('getNumber')->willReturn($playerNumber);
        $mock->expects($this->any())->method('getName')->willReturn($name);

        return $mock;
    }

    /** @return PHPUnit_Framework_Constraint */
    private function equalToPlay(Domain\Play $play)
    {
        return new Constraint\IsEqualPlayConstraint($play);
    }
}
