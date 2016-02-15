<?php

namespace Tests\Llvdl\Domino\Constraint;

use Llvdl\Domino\Domain\Play;

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
        $match = $play !== null && $play instanceof Play && $play->isEqual($this->other);
        return $match;
    }

    public function toString()
    {
        return 'matches ' . $this->other;
    }
    
    public function failureDescription($other)
    {
        return $other . ' ' . $this->toString();
    }
}
