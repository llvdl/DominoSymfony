<?php

namespace AppBundle\Form;

class Move
{
    /** @var int */
    private $stoneTopValue;

    /** @var int */
    private $stoneBottomValue;

    /** @var string */
    private $side;

    /**
     * @param int    $stoneTopValue
     * @param int    $stoneBottomValue
     * @param string $side
     */
    private function __construct($stoneTopValue, $stoneBottomValue, $side)
    {
        $this->stoneTopValue = $stoneTopValue;
        $this->stoneBottomValue = $stoneBottomValue;
        $this->side = $side;
    }

    /**
     * @param int    $stoneTopValue
     * @param int    $stoneBottomValue
     * @param string $side
     *
     * @return Move
     */
    public static function play($stoneTopValue, $stoneBottomValue, $side)
    {
        return new self($stoneTopValue, $stoneBottomValue, $side);
    }

    /** @return Move */
    public static function pass()
    {
        return new self(null, null, null);
    }

    /** @return int */
    public function getStoneTopValue()
    {
        return $this->stoneTopValue;
    }

    /** @return int */
    public function getStoneBottomValue()
    {
        return $this->stoneBottomValue;
    }

    /** @return string */
    public function getSide()
    {
        return $this->side;
    }

    /** @return string */
    public function getLabel()
    {
        return $this->isPlay() ? $this->stoneTopValue.'|'.$this->stoneBottomValue.' on side '.$this->side : 'pass';
    }

    /** @return string */
    public function toValue()
    {
        return $this->isPlay() ? $this->stoneTopValue.'_'.$this->stoneBottomValue.'-'.$this->side : 'pass';
    }

    /**
     * @param string value
     *
     * @return Move|null
     */
    public static function fromValue($value)
    {
        if ($value === 'pass') {
            return self::pass();
        }

        $matches = preg_match('/^([0-6])_([0-6])-((left|right))$/', $value);
        if ($matches) {
            return self::play(intval($matches[1]), intval($matches[2]), $matches[3]);
        }

        return;
    }

    /** @return bool */
    public function isPlay()
    {
        return $this->side !== null;
    }
}
