<?php

namespace Tests\AppBundle\Controller;

class StoneShuffler
{
    /** @var array */
    private $stones;

    /** @var int[] */
    private $setPositions = [];

    public function __construct()
    {
        for($top = 0; $top < 7; ++$top) {
            for($bottom = $top; $bottom < 7; ++$bottom) {
                $this->stones[] = [$top, $bottom];
            }
        }
        shuffle($this->stones);
    }

    public function getNext($amount)
    {
        $stones = [];
        for($i = 0; $i < $amount; ++$i) {
            $stones[] = array_shift($this->stones);
        }
        return $stones;
    }

    /**
     * puts a stone in a certain position so we can assure a player
     * has a certain stone after dealing
     *
     * @param array[] $stone stone value (index 0 is top value, index 1 is bottom value)
     * @param int $position position, value ranging from 1 up to 28 inclusive
     */
    public function setStoneAtPosition(array $stone, $position)
    {
        if($position < 1 || $position > 28) {
            throw new Exception('invalid position');
        }
        if(in_array($position, $this->setPositions)) {
            throw new Exception('a stone is already set for position ' . $position);
        }

        for($currentPosition = 1; $currentPosition <= 28; ++$currentPosition) {
            if($this->stones[$currentPosition - 1] === $stone) {
                break;
            }
        }
        for($freePosition = 1; $freePosition <= 28; ++$freePosition) {
            if(!in_array($freePosition, $this->setPositions)) {
                break;
            }
        }
        $tmp = $this->stones[$freePosition - 1];
        $this->stones[$freePosition - 1] = $this->stones[$currentPosition - 1];
        $this->stones[$currentPosition - 1] = $tmp;
    }
}