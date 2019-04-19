<?php

namespace Jeroenvanderlaan\AggregateRoot;

use Jeroenvanderlaan\AggregateRoot\Exception\AggregateEventCallbackException;
use Jeroenvanderlaan\AggregateRoot\Exception\AggregateHistoryBuildException;

abstract class EventSourcedAggregateRoot
{
    /**
     * @var Guid
     */
    private $id;

    /**
     * @var int
     */
    private $version;

    /**
     * @var DomainEvent[]
     */
    private $newEvents;

    /**
     * @param Guid $id
     * @param DomainEvent ...$events
     * @return EventSourcedAggregateRoot
     * @throws AggregateHistoryBuildException
     */
    public static function buildFromHistory(Guid $id, DomainEvent ...$events): EventSourcedAggregateRoot
    {
        $aggregate = self::newInstance($id);
        foreach ($events as $event) {
            $aggregate->apply($event);
        }
        $aggregate->commitEvents();
        return $aggregate;
    }

    /**
     * @param Guid $id
     * @return EventSourcedAggregateRoot
     * @throws AggregateHistoryBuildException
     */
    private static function newInstance(Guid $id): EventSourcedAggregateRoot
    {
        try {
            /** @var static $aggregate */
            $aggregate = (new \ReflectionClass(static::class))->newInstanceWithoutConstructor();
            $aggregate->id = $id;
            $aggregate->version = 0;
            $aggregate->newEvents = [];
            return $aggregate;
        } catch (\Error | \Exception $exception) {
            throw new AggregateHistoryBuildException(static::class, "Failed to create empty aggregate", 0, $exception);
        }
    }

    /**
     * Aggregate constructor.
     * @param Guid $id
     */
    protected function __construct(Guid $id)
    {
        $this->id = $id;
        $this->version = 0;
        $this->newEvents = [];
    }

    /**
     * @return Guid
     */
    public function getId(): Guid
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return DomainEvent[]
     */
    public function getNewEvents(): array
    {
        return $this->newEvents;
    }

    /**
     * @return void
     */
    public function commitEvents(): void
    {
        $this->version += count($this->newEvents);
        $this->newEvents = [];
    }

    /**
     * @param DomainEvent $event
     * @throws \Exception
     */
    protected function apply(DomainEvent $event): void
    {
        $this->invokeCallback($event);
        $this->newEvents[] = $event;
    }

    /**
     * @param DomainEvent $event
     * @throws AggregateEventCallbackException
     */
    private function invokeCallback(DomainEvent $event): void
    {
        try {
            $method = $this->getCallback($event);
        } catch (\Exception $exception) {
            throw new AggregateEventCallbackException($this, $event, "Failed to get event reflection callback", 0, $exception);
        }
        if (is_null($method)) {
            throw new AggregateEventCallbackException($this, $event, "Aggregate does not have event callback");
        }
        try {
            $method->setAccessible(true);
            $method->invoke($this, $event);
        } catch (\Exception $exception) {
            throw new AggregateEventCallbackException($this, $event, "Failed to invoke event reflection callback", 0, $exception);
        }
    }

    /**
     * @param DomainEvent $event
     * @return null|\ReflectionMethod
     * @throws \ReflectionException
     */
    private function getCallback(DomainEvent $event):? \ReflectionMethod
    {
        $reflection = new \ReflectionClass($event);
        $methods = $this->getReflectionMethods();

        foreach ($methods as $method) {
            if (!$this->hasEventCallbackName($method, $reflection)) {
                continue;
            }
            if (!$this->hasEventAsOnlyParameter($method, $reflection)) {
                continue;
            }
            return $method;
        }
        return null;
    }

    /**
     * @return \ReflectionMethod[]
     * @throws \ReflectionException
     */
    private function getReflectionMethods(): array
    {
        $reflection = new \ReflectionClass(static::class);
        return $reflection->getMethods(\ReflectionMethod::IS_PROTECTED | \ReflectionMethod::IS_PRIVATE);
    }

    /**
     * @param \ReflectionMethod $method
     * @param \ReflectionClass $event
     * @return bool
     */
    private function hasEventCallbackName(\ReflectionMethod $method, \ReflectionClass $event): bool
    {
        return $method->getName() === "on" . $event->getShortName();
    }

    /**
     * @param \ReflectionMethod $method
     * @param \ReflectionClass $event
     * @return bool
     */
    private function hasEventAsOnlyParameter(\ReflectionMethod $method, \ReflectionClass $event): bool
    {
        $parameters = $method->getParameters();
        $parameter = array_shift($parameters);
        $type = !is_null($parameter) ? $parameter->getType() : null;
        return $method->getNumberOfParameters() === 1 && !is_null($type) && $type->getName() === $event->getName();
    }
}