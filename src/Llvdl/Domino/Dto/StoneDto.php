<?php

namespace Llvdl\Domino\Dto;

class StoneDto
{
    /** @var int */
    private $topValue;
    /** @var int */
    private $bottomValue;

    /**
     * @var int
     * @var int $bottomValue
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
        return $this->getBottomValue;
    }
}
