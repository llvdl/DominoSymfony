<?php

namespace Llvdl\Domino\Dto;

use Llvdl\Domino\Exception\DominoException;

class PlayDto
{
    const SIDE_LEFT = 'left';
    const SIDE_RIGHT = 'right';

    /** @var int */
    private $turnNumber;

    /** @var StoneDto */
    private $stone;

    /** @var string one of SIDE_LEFT or SIDE_RIGHT */
    private $side;

    /**
     * @param int      $turnNumber
     * @param StoneDto $stone
     * @param string   $side       ont of SIDE_LEFT or SIDE_RIGHT 
     */
    public function __construct($turnNumber, StoneDto $stone, $side)
    {
        if (!in_array($side, [self::SIDE_LEFT, self::SIDE_RIGHT], true)) {
            throw new DominoException('invalid side value');
        }

        $this->turnNumber = $turnNumber;
        $this->stone = $stone;
        $this->side = $side;
    }

    /** @return int */
    public function getTurnNumber()
    {
        return $this->turnNumber;
    }

    /** @return StoneDto */
    public function getStone()
    {
        return $this->stone;
    }

    /** @return string */
    public function getSide()
    {
        return $this->side;
    }

    /**
     * @param PlayDto $other
     *
     * @return bool
     */
    public function isEqual(PlayDto $other)
    {
        return $this->turnNumber === $other->turnNumber
            && $this->stone->isEqual($other->stone)
            && $this->side === $other->side;
    }
}
