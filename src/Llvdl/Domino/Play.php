<?php

namespace Llvdl\Domino;

use Llvdl\Domino\Exception\DominoException;

class Play
{
    /** @var int */
    private $turnNumber;

    /** @var Stone */
    private $stone;

    /** @var side */
    private $side;

    /**
     * @param int    $turnNumber
     * @param Stone  $stone
     * @param string $side       Side, on of Table::SIDE_LEFT or Table::SIDE_RIGHT
     */
    public function __construct($turnNumber, Stone $stone, $side)
    {
        if ($turnNumber <= 0) {
            throw new DominoException('invalid turn number');
        }
        if ($stone === null) {
            throw new DominoException('stone may not be null');
        }
        if (!in_array($side, [Table::SIDE_LEFT, Table::SIDE_RIGHT])) {
            throw new DominoException('Invalid side');
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

    /** @return bool */
    public function isEqual(Play $other)
    {
        return $this->getStone()->isEqual($other->getStone())
            && $this->getSide() === $other->getSide();
    }
}
