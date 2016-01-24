<?php

namespace Tests\Llvdl\Domino;

use Llvdl\Domino\Game;
use Llvdl\Domino\Player;
use Llvdl\Domino\Play;
use Llvdl\Domino\Table;
use Llvdl\Domino\Stone;

class PlayerTest extends \PHPUnit_Framework_TestCase
{
    const ONCE = 1;
    
    /**
     * @todo implement test for placing stone on table
     */
    public function testPlay()
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
                $this->equalToPlay(new Play(4, new Stone(4,1), Table::SIDE_LEFT))
            );
    }
    
    private function assertPlayerHasStone(Player $player, Stone $stone)
    {
        $this->assertTrue($player->hasStone(new Stone(4,1)), 'player has stone ' . $stone);
    }
    
    private function assertPlayerDoesNotHaveStone(Player $player, Stone $stone)
    {
        $this->assertFalse($player->hasStone(new Stone(4,1)), 'player does not have stone ' . $stone);
    }

}
