<?php

namespace Tests\Llvdl\Domino\Service\Dto;

use Llvdl\Domino\Service\Dto\GameDetail;
use Llvdl\Domino\Service\Dto\Stone;
use Llvdl\Domino\Service\Dto\Player;
use Llvdl\Domino\Service\Dto\Turn;
use Llvdl\Domino\Domain\Exception\InvalidArgumentException;

class GameDetailTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function canGetData()
    {
        $players = [
            new Player(1, [new Stone(1,1), new Stone(2,0)]),
            new Player(2, [new Stone(3,3)]),
            new Player(3, [new Stone(6,0), new Stone(6,1)]),
            new Player(4, [new Stone(4,1), new Stone(4,5)])
        ];

        $gameDetail = new GameDetail(
            123, 
            'my name', 
            GameDetail::STATE_STARTED, 
            $players,
            [new Stone(5,5), new Stone(6,6), new Stone(6,5)],
            new Turn(11,3)
        );
        
        $this->assertSame(123, $gameDetail->getId());
        $this->assertsame('my name', $gameDetail->getName());
        $this->assertSame(GameDetail::STATE_STARTED, $gameDetail->getState());
        $this->assertSame(1, $gameDetail->getPlayers()[0]->getNumber());
        $this->assertSame(2, $gameDetail->getPlayers()[1]->getNumber());
        $this->assertSame(3, $gameDetail->getPlayers()[2]->getNumber());
        $this->assertSame(4, $gameDetail->getPlayers()[3]->getNumber());
        $this->assertStones([new Stone(1,1), new Stone(2,0)], $gameDetail->getPlayers()[0]->getStones());
        $this->assertStones([new Stone(3,3)], $gameDetail->getPlayers()[1]->getStones());
        $this->assertStones([new Stone(6,0), new Stone(6,1)], $gameDetail->getPlayers()[2]->getStones());
        $this->assertStones([new Stone(4,1), new Stone(4,5)], $gameDetail->getPlayers()[3]->getStones());
        $this->assertStones([new Stone(5,5), new Stone(6,6), new Stone(6,5)], $gameDetail->getTableStones());
        $this->assertSame(11, $gameDetail->getCurrentTurn()->getNumber());
        $this->assertSame(3, $gameDetail->getCurrentTurn()->getCurrentPlayerNumber());
    }
     
    private function assertStones(array $expectedStones, array $foundStones)
    {
        $this->assertSame(count($expectedStones), count($foundStones));
         
        foreach(array_keys($expectedStones) as $key) {
            $this->assertTrue($expectedStones[$key]->isEqual($foundStones[$key]));
        }
     }
}
