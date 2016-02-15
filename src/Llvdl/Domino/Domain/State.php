<?php

namespace Llvdl\Domino\Domain;

use Llvdl\Domino\Domain\Exception\LogicException;

class State
{
    const READY = 'ready';
    const STARTED = 'started';
    const FINISHED = 'finished';

    /** 
     * @var int
     * @note this is only used by the ORM layer
     */
    private $id;

    /** @var string name */
    private $name;

    public static function getInitialState()
    {
        return new self(self::READY);
    }

    /**
     * @param string $name name 
     */
    public function __construct($name)
    {
        if (!in_array($name, [self::READY, self::STARTED, self::FINISHED])) {
            throw new LogicException('invalid state name "'.$name.'"');
        }

        $this->id = null; // to keep phpmd quiet
        $this->name = $name;
    }

    /** @return bool TRUE if the state can be moved to STARTED, else FALSE */
    public function canStart()
    {
        return $this->name === self::READY;
    }

    /** @return State */
    public function start()
    {
        if (!$this->canStart()) {
            throw new LogicException('cannot start, already started');
        }
        $this->name = self::STARTED;
    }

    /** @return string */
    public function getName()
    {
        return $this->name;
    }

    /** @return bool TRUE if equal, otherwise FALSE */
    public function isEqual(State $other)
    {
        // the id field is an implementation detail of the ORM layer and cannot be used
        return $this->name === $other->name;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return State
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /** @return bool */
    public function isStarted()
    {
        return $this->name === self::STARTED;
    }
}
