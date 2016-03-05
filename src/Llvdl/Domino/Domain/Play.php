<?php

namespace Llvdl\Domino\Domain;

use Llvdl\Domino\Domain\Exception\InvalidArgumentException;

class Play extends Move
{
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
        parent::__construct($turnNumber);

        if (!in_array($side, [Table::SIDE_LEFT, Table::SIDE_RIGHT])) {
            throw new InvalidArgumentException('Invalid side');
        }

        $this->stone = $stone;
        $this->side = $side;
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

    /** @return string */
    public function __toString()
    {
        return 'Play(turn: '.$this->getTurnNumber().', stone: '.$this->getStone()->__toString().')';
    }
}
