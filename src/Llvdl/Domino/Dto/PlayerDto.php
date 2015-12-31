<?php

namespace Llvdl\Domino\Dto;

class PlayerDto
{
    /** @var int */
    private $number;
    /** @var string */
    private $name;
    /** @var StoneDto[] */
    private $stones;

    /**
     * @var int
     * @var string     $name
     * @var StoneDto[] $stones
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

    /** @return StoneDto[] */
    public function getStones()
    {
        return $this->stones;
    }
}
