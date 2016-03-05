<?php

namespace Llvdl\Domino\Service\Dto;

class Player
{
    /** @var int */
    private $number;

    /** @var Stone[] */
    private $stones;

    /**
     * @param int
     * @param Stone[] $stones
     */
    public function __construct($number, array $stones)
    {
        $this->number = $number;
        $this->stones = $stones;
    }

    /** @return int */
    public function getNumber()
    {
        return $this->number;
    }

    /** @return Stone[] */
    public function getStones()
    {
        return $this->stones;
    }
}
