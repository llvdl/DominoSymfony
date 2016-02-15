<?php

namespace Llvdl\Domino\Service\Dto;

class Player
{
    /** @var int */
    private $number;
    /** @var string */
    private $name;
    /** @var Stone[] */
    private $stones;

    /**
     * @param int
     * @param string  $name
     * @param Stone[] $stones
     */
    public function __construct($number, $name, array $stones)
    {
        $this->number = $number;
        $this->name = $name;
        $this->stones = $stones;
    }

    /** @return int */
    public function getNumber()
    {
        return $this->number;
    }

    /** @return string */
    public function getName()
    {
        return $this->name;
    }

    /** @return Stone[] */
    public function getStones()
    {
        return $this->stones;
    }
}
