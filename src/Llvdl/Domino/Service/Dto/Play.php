<?php

namespace Llvdl\Domino\Service\Dto;

use Llvdl\Domino\Domain\Exception\DominoException;

class Play
{
    const SIDE_LEFT = 'left';
    const SIDE_RIGHT = 'right';

    /** @var int */
    private $turnNumber;

    /** @var Stone */
    private $stone;

    /** @var string one of SIDE_LEFT or SIDE_RIGHT */
    private $side;

    /**
     * @param int    $turnNumber
     * @param Stone  $stone
     * @param string $side       one of SIDE_LEFT or SIDE_RIGHT 
     */
    public function __construct($turnNumber, Stone $stone, $side)
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

    /** @return Stone */
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
     * @param Play $other
     *
     * @return bool
     */
    public function isEqual(Play $other)
    {
        return $this->turnNumber === $other->turnNumber
            && $this->stone->isEqual($other->stone)
            && $this->side === $other->side;
    }
}
