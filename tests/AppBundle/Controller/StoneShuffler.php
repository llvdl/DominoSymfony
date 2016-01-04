<?php

namespace Tests\AppBundle\Controller;

class StoneShuffler
{
    /** @var array */
    private $stones;

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
}