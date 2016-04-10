<?php

namespace Tests\Llvdl\Domino\Service\Dto;

use Llvdl\Domino\Service\Dto\Play;
use Llvdl\Domino\Service\Dto\Stone;
use Llvdl\Domino\Domain\Exception\InvalidArgumentException;

class PlayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException Llvdl\Domino\Domain\Exception\InvalidArgumentException
     */
    public function sideMustBeValid()
    {
        $play = new Play(4, new Stone(0, 0), 'another_side');
    }
}

