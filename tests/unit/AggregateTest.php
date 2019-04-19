<?php

namespace Jeroenvanderlaan\AggregateRoot\Tests\Unit;

use Jeroenvanderlaan\AggregateRoot\EventSourcedAggregateRoot;
use Jeroenvanderlaan\AggregateRoot\DomainEvent;
use Jeroenvanderlaan\AggregateRoot\Tests\Unit\Mock\MockEventSourcedAggregateRoot;
use Jeroenvanderlaan\AggregateRoot\Tests\Unit\Mock\MockAggregateCreated;
use Jeroenvanderlaan\AggregateRoot\Tests\Unit\Mock\MockAggregateDeleted;
use Jeroenvanderlaan\AggregateRoot\Tests\Unit\Mock\MockGuid;
use PHPUnit\Framework\TestCase;

class AggregateTest extends TestCase
{
    public function testThatIdIsSetWhenAggregateIsCreated(): void
    {
        $id = new MockGuid("mock-guid");
        $aggregate = new MockEventSourcedAggregateRoot($id);
        $this->assertEquals($id, $aggregate->getId());
    }

    public function testThatVersionIsZeroWhenAggregateIsCreated(): void
    {
        $id = new MockGuid("mock-guid");
        $aggregate = new MockEventSourcedAggregateRoot($id);
        $this->assertEquals(0, $aggregate->getVersion());
    }

    public function testThatCreatedEventIsAddedToNewEventsWhenAggregateIsCreated(): void
    {
        $id = new MockGuid("mock-guid");
        $aggregate = new MockEventSourcedAggregateRoot($id);
        $events = $aggregate->getNewEvents();
        $event = reset($events);
        $this->assertCount(1, $events);
        $this->assertInstanceOf(MockAggregateCreated::class, $event);
    }

    public function testThatDeletedEventIsAddedToNewEventsWhenDeleteIsInvoked(): void
    {
        $id = new MockGuid("mock-guid");
        $aggregate = new MockEventSourcedAggregateRoot($id);
        $aggregate->delete();
        $events = $aggregate->getNewEvents();
        $event = end($events);
        $this->assertCount(2, $events);
        $this->assertInstanceOf(MockAggregateDeleted::class, $event);
    }

    public function testThatVersionIsUpdatedWhenEventsAreCommitted(): void
    {
        $id = new MockGuid("mock-guid");
        $aggregate = new MockEventSourcedAggregateRoot($id);
        $aggregate->commitEvents();
        $this->assertEquals(1, $aggregate->getVersion());
    }

    public function testThatNewEventsIsEmptyWhenEventsAreCommitted(): void
    {
        $id = new MockGuid("mock-guid");
        $aggregate = new MockEventSourcedAggregateRoot($id);
        $aggregate->commitEvents();
        $this->assertEmpty($aggregate->getNewEvents());
    }

    public function testThatAggregateIsNotMarkedAsDeletedWhenAggregateIsCreated(): void
    {
        $id = new MockGuid("mock-guid");
        $aggregate = new MockEventSourcedAggregateRoot($id);
        $this->assertFalse($aggregate->isDeleted());
    }

    public function testThatAggregateIsMarkedAsDeletedWhenDeleteIsInvoked(): void
    {
        $id = new MockGuid("mock-guid");
        $aggregate = new MockEventSourcedAggregateRoot($id);
        $aggregate->delete();
        $this->assertTrue($aggregate->isDeleted());
    }

    public function testThatBuildingFromHistoryCreatesInstanceOfBoundedAggregate(): void
    {
        $id = new MockGuid("mock-guid");
        $history = new MockAggregateCreated();
        $aggregate = MockEventSourcedAggregateRoot::buildFromHistory($id, $history);
        $this->assertInstanceOf(MockEventSourcedAggregateRoot::class, $aggregate);
    }

    public function testThatIdIsSetWhenBuildingAggregateFromHistory(): void
    {
        $id = new MockGuid("mock-guid");
        $history = new MockAggregateCreated();
        /** @var MockEventSourcedAggregateRoot $aggregate */
        $aggregate = MockEventSourcedAggregateRoot::buildFromHistory($id, $history);
        $this->assertEquals($id, $aggregate->getId());
    }

    public function testThatNewEventsIsEmptyWhenBuildingAggregateFromHistory(): void
    {
        $id = new MockGuid("mock-guid");
        $history = new MockAggregateCreated();
        /** @var MockEventSourcedAggregateRoot $aggregate */
        $aggregate = MockEventSourcedAggregateRoot::buildFromHistory($id, $history);
        $this->assertEmpty($aggregate->getNewEvents());
    }

    public function testThatEventsAreCommittedWhenBuildingAggregateFromHistory(): void
    {
        $id = new MockGuid("mock-guid");
        $history = [new MockAggregateCreated(), new MockAggregateDeleted()];
        /** @var MockEventSourcedAggregateRoot $aggregate */
        $aggregate = MockEventSourcedAggregateRoot::buildFromHistory($id, ...$history);
        $this->assertEquals(2, $aggregate->getVersion());
    }

    public function testThatAggregateIsNotMarkedAsDeletedIfHistoryContainsNoDeleteEvent(): void
    {
        $id = new MockGuid("mock-guid");
        $history = new MockAggregateCreated();
        $aggregate = MockEventSourcedAggregateRoot::buildFromHistory($id, $history);
        /** @var MockEventSourcedAggregateRoot $aggregate */
        $this->assertFalse($aggregate->isDeleted());
    }

    public function testThatAggregateIsMarkedAsDeletedIfHistoryContainsDeleteEvent(): void
    {
        $id = new MockGuid("mock-guid");
        $history = [new MockAggregateCreated(), new MockAggregateDeleted()];
        $aggregate = MockEventSourcedAggregateRoot::buildFromHistory($id, ...$history);
        /** @var MockEventSourcedAggregateRoot $aggregate */
        $this->assertTrue($aggregate->isDeleted());
    }

    /**
     * @expectedException \Jeroenvanderlaan\AggregateRoot\Exception\AggregateEventCallbackException
     */
    public function testThatExceptionIsThrownWhenHistoryContainsUnknownEvent(): void
    {
        $id = new MockGuid("mock-guid");
        /** @var DomainEvent $history */
        $history = $this->createMock(DomainEvent::class);
        MockEventSourcedAggregateRoot::buildFromHistory($id, $history);
    }

    /**
     * @expectedException \Jeroenvanderlaan\AggregateRoot\Exception\AggregateHistoryBuildException
     */
    public function testThatExceptionIsThrownIfInvalidAggregateIsUsedToBuildFromHistory(): void
    {
        $id = new MockGuid("mock-guid");
        $history = new MockAggregateCreated();
        EventSourcedAggregateRoot::buildFromHistory($id, $history);
    }
}