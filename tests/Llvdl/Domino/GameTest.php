<?php

namespace Tests\Llvdl\Domino;

use Llvdl\Domino\Game;
use Llvdl\Domino\Player;
use Llvdl\Domino\Stone;

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

    public function testGameHasAName()
    {
        $this->assertEquals($this->gameName, $this->game->getName());
    }

    public function testNewlyCreatedGameHasFourPlayersWithNoStonesInHand()
    {
        $this->assertPlayerCount(4, $this->game);
        foreach($this->game->getPlayers() as $player) {
            $this->assertPlayerStoneCount(0, $player);
        }
    }

    public function testDealGameStartsGame()
    {
        $this->assertTrue($this->game->getState()->canStart());
        $this->game->deal();
        $this->assertFalse($this->game->getState()->canStart());
    }

     /** @expectedException \Llvdl\Domino\Exception\DominoException */
    public function testGameCannotBeDealtTwice()
    {
        $this->game->deal();
        $this->game->deal();
    }

    public function testAfterDealStonesAreEvenlyDistributedAmongPlayers()
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