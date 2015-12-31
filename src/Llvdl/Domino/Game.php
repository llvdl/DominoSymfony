<?php

namespace Llvdl\Domino;

class Game
{
    /** @var int */
    private $id;
    /** @var string */
    private $name;
    /** @var State */
    private $state;

    /** @param string $name */
    public function __construct($name)
    {
        $this->id = null;
        $this->name = $name;
        $this->state = State::getInitialState();
    }

    /** @return int */
    public function getId()
    {
        return $this->id;
    }

    /** @return string */
    public function getName()
    {
        return $this->name;
    }

    /** @param string $name */
    public function setName($name)
    {
        $this->name = $name;
    }

    /** @return State state */
    public function getState()
    {
        return $this->state;
    }

    public function deal()
    {
        $this->state->start();
    }
}
