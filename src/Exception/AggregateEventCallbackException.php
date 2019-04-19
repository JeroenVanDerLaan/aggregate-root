<?php

namespace Jeroenvanderlaan\AggregateRoot\Exception;

use Jeroenvanderlaan\AggregateRoot\EventSourcedAggregateRoot;
use Jeroenvanderlaan\AggregateRoot\DomainEvent;
use Throwable;

class AggregateEventCallbackException extends \Exception
{
    /**
     * @var EventSourcedAggregateRoot
     */
    private $aggregate;

    /**
     * @var DomainEvent
     */
    private $event;

    /**
     * AggregateEventCallbackException constructor.
     * @param EventSourcedAggregateRoot $aggregate
     * @param DomainEvent $event
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(EventSourcedAggregateRoot $aggregate, DomainEvent $event, $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->aggregate = $aggregate;
        $this->event = $event;
    }

    /**
     * @return EventSourcedAggregateRoot
     */
    public function getAggregate(): EventSourcedAggregateRoot
    {
        return $this->aggregate;
    }

    /**
     * @return DomainEvent
     */
    public function getEvent(): DomainEvent
    {
        return $this->event;
    }
}