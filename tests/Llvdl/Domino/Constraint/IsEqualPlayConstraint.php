<?php

namespace Tests\Llvdl\Domino\Constraint;

use Llvdl\Domino\Play;

class IsEqualPlayConstraint extends \PHPUnit_Framework_Constraint
{
    /** @var Play */
    private $other;

    public function __construct(Play $other)
    {
        $this->other = $other;
    }

    public function matches($play)
    {
        return $play->isEqual($this->other);
    }

    public function toString()
    {
        return 'matches play';
    }
}