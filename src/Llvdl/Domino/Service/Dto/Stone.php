<?php

namespace Llvdl\Domino\Service\Dto;

class Stone
{
    /** @var int */
    private $topValue;
    /** @var int */
    private $bottomValue;

    /**
     * @param int
     * @param int $bottomValue
     */
    public function __construct($topValue, $bottomValue)
    {
        $this->topValue = $topValue;
        $this->bottomValue = $bottomValue;
    }

    /** @return int */
    public function getTopValue()
    {
        return $this->topValue;
    }

    /** @return int */
    public function getBottomValue()
    {
        return $this->bottomValue;
    }

    /**
     * @param Stone $other
     *
     * @return bool
     */
    public function isEqual(Stone $other)
    {
        return $this->topValue === $other->topValue
            && $this->bottomValue === $other->bottomValue;
    }
}
