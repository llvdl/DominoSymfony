<?php

namespace Tests\Llvdl\Domain\Domino;

use Llvdl\Domino\Domain\Game;
use Llvdl\Domino\Domain\Player;
use Llvdl\Domino\Domain\Play;
use Llvdl\Domino\Domain\Table;
use Llvdl\Domino\Domain\Stone;
use Tests\Llvdl\Domino\Constraint;
use Llvdl\Domino\Domain\Exception\InvalidMoveException;

class PlayerTest extends \PHPUnit_Framework_TestCase
{
    const NEVER = 0;
    const ONCE = 1;
    
    /**
     * @todo implement test for placing stone on table
     * 
     * @test
     */
    public function playerCanPlayStone()
    {
        $game = $this->getMockGame();
        $player = new Player($game, 1, 'player 1');
        $player->addStones([new Stone(1,1), new Stone(4,1)]);
        $play = new Play(4, new Stone(4,1), Table::SIDE_LEFT);

        $this->assertPlayerHasStone($player, new Stone(4,1));
        $this->expectThatAddMoveIsCalled($game, $player, $play, self::ONCE);
        
        $player->play(new Play(4, new Stone(4,1), Table::SIDE_LEFT));
        $this->assertPlayerDoesNotHaveStone($player, new Stone(4,1));
    }

    /**
     * @test
     * @expectedException Llvdl\Domino\Domain\Exception\InvalidMoveException
     */
    public function playerCannotPlayStonesPlayerDoesNotHave()
    {
        $game = $this->getMockGame();
        $player = new Player($game, 1, 'player 1');
        $player->addStones([new Stone(1,1), new Stone(4,1)]);
        $play = new Play(4, new Stone(2,3), Table::SIDE_LEFT);

        $this->assertPlayerDoesNotHaveStone($player, new Stone(2,3));
        $this->expectThatAddMoveIsCalled($game, $player, $play, self::NEVER);
        
        $player->play($play);
    }

    private function getMockGame()
    {
        $mock = $this->getMockBuilder(Game::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $mock;
    }

    /** @return PHPUnit_Framework_Constraint */
    private function equalToPlay(Play $play)
    {
        return new Constraint\IsEqualPlayConstraint($play);
    }    
    
    private function expectThatAddMoveIsCalled($gameMock, Player $player, Play $play, $callCount = null)
    {
        $gameMock
            ->expects($callCount === null ? $this->any() : $this->exactly($callCount))
            ->method('addMove')
            ->with(
                $this->identicalTo($player),
                $this->equalToPlay($play)
            );
    }
    
    private function assertPlayerHasStone(Player $player, Stone $stone)
    {
        $this->assertTrue($player->hasStone($stone), 'player has stone ' . $stone);
    }
    
    private function assertPlayerDoesNotHaveStone(Player $player, Stone $stone)
    {
        $this->assertFalse($player->hasStone($stone), 'player does not have stone ' . $stone);
    }

}
