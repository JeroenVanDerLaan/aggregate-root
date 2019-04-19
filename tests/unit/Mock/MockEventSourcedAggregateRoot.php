<?php

namespace Jeroenvanderlaan\AggregateRoot\Tests\Unit\Mock;

use Jeroenvanderlaan\AggregateRoot\EventSourcedAggregateRoot;
use Jeroenvanderlaan\AggregateRoot\Guid;

class MockEventSourcedAggregateRoot extends EventSourcedAggregateRoot
{
    /**
     * @var bool
     */
    private $deleted;

    /**
     * MockAggregate constructor.
     * @param Guid $id
     * @throws \Exception
     */
    public function __construct(Guid $id)
    {
        parent::__construct($id);
        $this->apply(new MockAggregateCreated());
    }

    /**
     * @throws \Exception
     */
    public function delete(): void
    {
        $this->apply(new MockAggregateDeleted());
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param MockAggregateCreated $event
     */
    private function onMockAggregateCreated(MockAggregateCreated $event): void
    {
        $this->deleted = false;
    }

    /**
     * @param MockAggregateDeleted $event
     */
    private function onMockAggregateDeleted(MockAggregateDeleted $event): void
    {
        $this->deleted = true;
    }
}