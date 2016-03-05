<?php

namespace Tests\Llvdl\Domain\Domino;

use Llvdl\Domino\Domain\Play;
use Llvdl\Domino\Domain\Stone;
use Llvdl\Domino\Domain\Table;
use Llvdl\Domino\Domain\Exception\InvalidArgumentException;

class PlayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * 
     * @expectedException Llvdl\Domino\Domain\Exception\InvalidArgumentException
     * @dataProvider provideInvalidPlayValues
     */
    public function playAssertsConstraints($turnNumber, Stone $stone, $side)
    {
        $play = new Play($turnNumber, $stone, $side);
    } 
    
    public function provideInvalidPlayValues()
    {
        return [
            'invalid turn number 0' => [0, new Stone(1,1), TABLE::SIDE_LEFT],
            'invalid turn number null' => [null, new Stone(1,1), TABLE::SIDE_LEFT],
            'invalid side' => [2, new Stone(1,1), 'left side'],
            'invalid side null' => [2, new Stone(1,1), null]
        ];
    }
    
}
