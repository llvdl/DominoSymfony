<?php

namespace Tests\Llvdl\Domain\Domino;

use Llvdl\Domino\Domain\Game;
use Llvdl\Domino\Domain\Player;
use Llvdl\Domino\Domain\Stone;
use Llvdl\Domino\Domain\Play;
use Llvdl\Domino\Domain\Table;

class GameTest extends \PHPUnit_Framework_TestCase
{
    /** @var Game */
    private $game;

    /** @var string */
    private $gameName;

    public function setUp() 
    {
        $this->gameName = 'my game #' . uniqid();
        $this->game = new Game($this->gameName);
    }

    /** @test */
    public function gameHasAName()
    {
        $this->assertEquals($this->gameName, $this->game->getName());
    }

    /** @test */
    public function newlyCreatedGameHasFourPlayersWithNoStonesInHand()
    {
        $this->assertPlayerCount(4, $this->game);
        foreach($this->game->getPlayers() as $player) {
            $this->assertPlayerStoneCount(0, $player);
        }
    }
    
    /** @test */
    public function playersHaveNumbers1Through4() 
    {
        foreach(range(1, 4) as $number) {
            $player = $this->game->getPlayerByPlayerNumber($number);
            $this->assertInstanceOf(Player::class, $player);
        }
    }
    
    /**
     * @test
     * @dataProvider provideInvalidPlayerNumbers
     * @expectedException \Llvdl\Domino\Domain\Exception\DominoException 
     * */
    public function getPlayerByInvalidNumberThrowsException($playerNumber)
    {
        $this->game->getPlayerByPlayerNumber($playerNumber);
    }
    
    public function provideInvalidPlayerNumbers() 
    {
        return [
            ['too low, first player has number 1' => 0],
            ['too high' => 5]
        ];
    }
    
    /** @test */
    public function dealGameStartsGame()
    {
        $this->assertTrue($this->game->getState()->canStart());
        $this->game->deal();
        $this->assertFalse($this->game->getState()->canStart());
    }

    /** 
     * @test
     * @expectedException \Llvdl\Domino\Domain\Exception\DominoException 
     */
    public function gameCannotBeDealtTwice()
    {
        $this->game->deal();
        $this->game->deal();
    }

    /** @test */
    public function afterDealStonesAreEvenlyDistributedAmongPlayers()
    {
        $this->game->deal();

        $this->assertPlayerCount(4, $this->game);
        foreach($this->game->getPlayers() as $player) {
            $this->assertPlayerStoneCount(7, $player);
        }

        // get all player stones and see if all expected stones are distributed
        $stones = [];
        foreach($this->game->getPlayers() as $player) {
            $stones = array_merge($stones, $player->getStones());
        }

        $this->assertCount(count($this->getFullStoneSet()), $stones);
        foreach($this->getFullStoneSet() as $stone) {
            $this->assertContainsStone($stone, $stones);
        }
    }

    /** @test */
    public function addMovePlayFirstStone()
    {
        $this->game->deal();

        $currentPlayerNumber = $this->game->getCurrentTurn()->getPlayerNumber();
        $player = $this->game->getPlayerByPlayerNumber($currentPlayerNumber);

        $this->assertEquals(1, $this->game->getCurrentTurn()->getNumber(), 'current turn number is 1');

        $play = new Play(1, new Stone(6,6), Table::SIDE_LEFT);
        $this->game->addMove($player, $play);

        $this->assertEquals(2, $this->game->getCurrentTurn()->getNumber(), 'current turn number is 2');
    }

    /** 
     * @test
     * @expectedException \Llvdl\Domino\Domain\Exception\InvalidMoveException 
     */
    public function cannotAddMoveToUnstartedGame()
    {
        $this->assertFalse($this->game->getState()->isStarted());

        $player = $this->game->getPlayerByPlayerNumber(1);
        
        $play = new Play(1, new Stone(6,6), Table::SIDE_LEFT);
        $this->game->addMove($player, $play);
    }
    
    /** 
     * @test
     * @expectedException \Llvdl\Domino\Domain\Exception\InvalidMoveException 
     */
    public function moveOnlyAllowedForCurrentTurn()
    {
        $this->game->deal();

        $currentPlayerNumber = $this->game->getCurrentTurn()->getPlayerNumber();
        $player = $this->game->getPlayerByPlayerNumber($currentPlayerNumber);

        $this->assertEquals(1, $this->game->getCurrentTurn()->getNumber(), 'current turn number is 1');

        $play = new Play(2, new Stone(6,6), Table::SIDE_LEFT);
        $this->game->addMove($player, $play);
    }
    
    /** 
     * @test
     * @expectedException \Llvdl\Domino\Domain\Exception\InvalidMoveException 
     */
    public function moveOnlyAllowedForCurrentPlayer()
    {
        $this->game->deal();

        $currentPlayerNumber = $this->game->getCurrentTurn()->getPlayerNumber();
        $player = $this->game->getPlayerByPlayerNumber($currentPlayerNumber);

        $play = new Play(1, new Stone(6,6), Table::SIDE_LEFT);
        
        $nextPlayerNumber = $currentPlayerNumber === 4 ? 1 : $currentPlayerNumber + 1;
        $nextPlayer = $this->game->getPlayerByPlayerNumber($nextPlayerNumber);
        
        $nextPlayer->addStones([new Stone(6,6)]);
        
        $this->game->addMove($nextPlayer, $play);
    }

    private function assertPlayerCount($playerCount, Game $game)
    {
        $this->assertCount($playerCount, $game->getPlayers());
    }

    private function assertPlayerStoneCount($stoneCount, Player $player)
    {
        $this->assertCount($stoneCount, $player->getStones());
    }
    
    private function assertContainsStone(Stone $stone, array $stones)
    {
        $hasStone = array_reduce(
            $stones, function($hasStone, Stone $other) use ($stone) {
                return $hasStone || $stone->isEqual($other);
            },
            false);
        $this->assertTrue($hasStone);
    }

    /** @return Stone[] collection of all stones */
    private function getFullStoneSet()
    {
        $stones = [];
        for($top = 0; $top < 7; ++$top) {
            for($bottom = $top; $bottom < 7; ++$bottom) {
                $stones[] = new Stone($top, $bottom);
            }
        }
        return $stones;
    }
}
