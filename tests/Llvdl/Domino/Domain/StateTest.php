<?php

namespace Tests\Llvdl\Domain\Domino;

use Llvdl\Domino\Domain\State;
use Llvdl\Domino\Domain\Exception\LogicException;

class StateTest extends \PHPUnit_Framework_TestCase
{
    public function testStateCanBeConstructedWithKnownName()
    {
        foreach([State::READY, State::STARTED, State::FINISHED] as $name) {
            $state = new State($name);
            $this->assertInstanceOf(State::class, $state);
        }
    }

    /**
     * @dataProvider provideInvalidStateNames
     * @expectedException \Llvdl\Domino\Domain\Exception\LogicException
     */
    public function testStateConstructedWithAnInvalidNameThrowsLogicException($name)
    {
        $state = new State($name);
    }

    /** @see StateTest::testStateConstructedWithAnInvalidNameThrowsLogicException */
    public function provideInvalidStateNames()
    {
        return [
            'empty name'=> [''],
            'unknown name'=> ['state_that_is_invalid'],
            'wrong casing' => ['Ready']
        ];
    }

    /** @dataProvider provideCanStartData */
    public function testCanStart(State $state, $expectedCanStart)
    {
        $this->assertSame($expectedCanStart, $state->canStart());
    }

    /** @see StateTest::testCanStart */
    public function provideCanStartData()
    {
        $canStart = true;
        $cannotStart = false;
        return [
            [new State(State::READY), $canStart],
            [new State(State::STARTED), $cannotStart],
            [new State(State::FINISHED), $cannotStart],
        ];
    }

    public function testSetStateToStart()
    {
        $state = new State(State::READY);
        $state->start();
        $this->assertTrue($state->isEqual(new State(State::STARTED)), 'state set to "started"');
    }

    /**
     * @dataProvider provideStartedStates
     * @expectedException \Llvdl\Domino\Domain\Exception\LogicException 
     */
    public function testSetStateToStartIfNotReadyThrowsLogicException(State $state)
    {
        $state->start();
    }

    /** @see StateTest::testSetStateToStartIfNotReadyThrowsLogicException */
    public function provideStartedStates()
    {
        return [
            [new State(State::STARTED)],
            [new State(State::FINISHED)]
        ];
    }

    public function testInitialStateIsReady()
    {
        $state = State::getInitialState();
        $this->assertTrue($state->isEqual(new State(State::READY)));
    }

    /** @dataProvider provideIsEqualStateData */
    public function testIsEqualTestsForSameName(State $state1, State $state2, $expectedEqual)
    {
        $this->assertSame($expectedEqual, $state1->isEqual($state2));
    }

    /** @see StateTest::testIsEqualTestsForSameName */
    public function provideIsEqualStateData()
    {
        $data = [];
        $states = [State::READY, State::STARTED, State::FINISHED];
        foreach($states as $name1) {
            foreach($states as $name2) {
                $data[] = [new State($name1), new State($name2), $name1 === $name2];
            }
        }
        return $data;
    }
}
