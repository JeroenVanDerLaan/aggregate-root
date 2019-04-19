# Event Sourced Aggregate
An Event Sourced Aggregate Root implementation in PHP.
Based on the Aggregate Root and Domain Event concepts of Domain Driven Design.

```php
<?php

//create the aggregate
$aggregate = new Developer($id);
$aggregate->startDeveloping($project);

//work with the aggregate's events
$bus->publish(...$aggregate->getNewEvents());
$aggregate->commitEvents();

//build the aggregate from past events
$aggregate = Developer::buildFromHistory($id, ...$events);
$aggregate->startDeveloping($anotherProject);
```

## Table of Contents
* [Install](#install)
* [Event Sourcing](#event-sourcing)
* [Aggregate Root](#aggregate-root)
* [Domain Evens](#domain-events)
* [Example](#example)
  + [Identifying the domain](#identifying-the-domain)
  + [Implementing Domain Events](#implementing-domain-events)
  + [Implementing Aggregates](#implementing-aggregates)
  + [Applying Domain Events](#applying-domain-events)
  + [Identifying Invariants](#identifying-invariants)
  + [Implementing Invariants](#implementing-invariants)
  + [Orchestrating Aggregates](#orchestrating-aggregates)
  + [Versioning Aggregates](#versioning-aggregates)
  + [Rebuilding Aggregates](#rebuilding-aggregates)

## Install
```
comsposer require jeroenvanderlaan/aggregate-root
```
## Event Sourcing
Simply put, event sourcing means that we want to track the state of something as a sequence of events.

Traditionally, applications make use of an underlying CRUD implementation, usually some ORM framework, to track the state of their models. When a model state has changed, the appropriate table row should be updated to reflect this change.

In [Event Sourcing](https://martinfowler.com/eaaDev/EventSourcing.html), the state of a model is reflected by the sum of all events that occured in relation to that model. And where events are stored as a sequence of changes.

Model state is not directly read from the database and given to a model object. Instead, the appropriate list of events is fetched from which the model state is build by applying each event in order of occurance.

## Aggregate Root
The aggregate root in Domain Driven Design is an aggregation of domain objects that together define a concept within the domain. This concept comes forth from the agreed upon [ubiquitous language](https://martinfowler.com/bliki/UbiquitousLanguage.html), and an aggregate is the expression of this concept. Its interface should reveal understandable intentions to the domain, while internally, it will enforce all invariants required, on itself and the domain objects it orchestrates, to maintain integrity of the domain concept as a whole.

For a more complete description of the aggregate, and domain driven design in general, I suggest you check out [Martin Fowler's website](https://martinfowler.com/bliki/DDD_Aggregate.html).
If you are completely new to this style of software design, I suggest reading [Domain Driven Design](http://www.google.com/search?q=Eric+Evans+Domain+Driven+Design) by Eric Evans and [Implementing Domain Driven Design](http://www.google.com/search?q=Vince+Vaugn+Impementing+Domain+Driven+Design) by Vince Vaughn.

## Domain Events
Aggregate roots are meant to trigger [domain events](https://martinfowler.com/eaaDev/DomainEvent.html), communicating that your domain is being acted upon. Other parts of your domain might want to react to this event, or perhaps the event caused state changes to the aggregate root itself.

The domain event, like the aggregate root, is a central concept in Domain Driven Design, and goes hand in hand with Event Sourcing.

## Example
### Identifying the domain
For this example to make sense, we first we need to identify a simple domain by formulating an [ubiquitous language](https://martinfowler.com/bliki/UbiquitousLanguage.html).

Let's say that our domain entails the employment of developers, and what projects they are working on.
We identify the following concepts:

* A *developer* can be *hired*
* A *developer* can be *fired*
* A *developer* can *start development* on a *project*
* A *developer* can *stop development* on a *project*

We will expand the domain later. For now, this is all we know.

### Implementing Domain Events
We can translate these concepts as events that happen within our domain. One such way is to identify them as:

* `DeveloperHired`
* `DeveloperFired`
* `DevelopmentStarted`
* `DevelopmentStopped`

An event is usually just a simple immutable value object that carries only the data it needs to describe itself. For example, a `DevelopmentStarted` event can hold a `Project` value object, discribing which project was started.

We create events by implementing the empty `DomainEvent` interface, and giving it the appropriate data accessors.

```php
<?php

class DeverloperHired implements DomainEvent
{
    //no data required
}

class DeverloperFired implements DomainEvent
{
    //no data required
}

class DevelopmentStarted implements DomainEvent
{
    private $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function getProject(): Project
    {
        return $this->project;
    }
}

class DevelopmentStopped implements DomainEvent
{
    private $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function getProject(): Project
    {
        return $this->project;
    }
}
```
### Implementing Aggregates
These events, according to our domain, are encapsulated by to the concept of a *developer*. This is the entity responsible for making our events occur when interacted with.

To stick with the language, we implement a `Developer` aggregate.  And we do this by extending the `EventSourcedAggregateRoot` abstract class.
The `Developer` class will get public methods that reveal domain specific intentions. And which trigger the appropriate event if no invariants are violated.

To signify that an event occured, the `Developer` can use the inherited protected `apply` method, passing the respective `DomainEvent` to the `EventSourcedAggregateRoot` abstract class.

```php
<?php
class Developer extends EventSourcedAggregateRoot
{
    public function __construct(Guid $id)
    {
        parent::__construct($id);
        $event = new DeveloperHired();
        $this->apply($event);
    }
    
    public function fire(): void
    {
        $event = new DeveloperFired();
        $this->apply($event);
    }
    
    public function startDevelopmentOn(Project $project): void
    {
        $event = new DevelopmentStarted($project);
        $this->apply($event);
    }
    
    public function stopDevelopmentOn(Project $project): void
    {
        $event = new DevelopmentStopped($project);
        $this->apply($event);
    }
}
```
### Applying Domain Events
By calling `apply`, the `Developer` aggregate is communicating to the `EventSourcedAggregateRoot` abstract class that an event has occurred, and that it is ready to apply state changes caused by the event.

The `EventSourcedAggregateRoot` class will add the event to the list of new events for later access. After which it will invoke a very specific callback on `Developer`, passing the `DomainEvent` back. By invoking this callback, the `EventSourcedAggregateRoot` tells `Developer` that it has processed the given event successfully, and that state changes can finally be applied.

`Developer` and `EventSourcedAggregateRoot` communicate as follows:

1. `Developer` applies `$event` with `apply`
2. `EventSourcedAggregateRoot` processes `$event`
3. `EventSourcedAggregateRoot` invokes callback on `Developer`, passing `$event` back to `Developer`
4. `Developer` possibly updates state inside this callback, using `$event`

At the moment, the `Developer` aggregate does not have any callbacks implemented. Before we do so, there are a few criteria to keep in mind:

*  The callback must be a **private** or **protected** method of the implemented `EventSourcedAggregateRoot`.
*  The callback must take only **1 parameter**, the `DomainEvent` instance.
*  The callback method name must start with **on** and end with the `DomainEvent` instance **short class name**
*  All applied events must have a corresponding callback

```php

class Developer extends EventSourcedAggregateRoot
{
    public function __construct(Guid $id)
    {
        parent::__construct($id);
        $event = new DeveloperHired();
        $this->apply($event);
    }

    public function fire(): void
    {
        $event = new DeveloperFired();
        $this->apply($event);
    }

    public function startDevelopmentOn(Project $project): void
    {
        $event = new DevelopmentStarted($project);
        $this->apply($event);
    }

    public function stopDevelopmentOn(Project $project): void
    {
        $event = new DevelopmentStopped($project);
        $this->apply($event);
    }

    private function onDeveloperHired(DeveloperHired $event): void
    {
        //update state
    }

    private function onDeveloperFired(DeveloperFired $event): void
    {
        //update state
    }

    private function onDevelopmentStarted(DevelopmentStarted $event): void
    {
        //update state
    }

    private function onDevelopmentStopped(DevelopmentStopped $event): void
    {
        //update state
    }
}
```
### Identifying Invariants
The `Developer` aggregate is now all set up to update its internal state. However, our current domain does not provide us with any reason to maintain state. Given what we know, what state should a `Developer` hold? A `Developer` can get *fired*, but what does that mean to our domain?

Let's expand our domain a little, and give meaning to some of the events that are happening, by introducing the following concepts:

*  A developer can only start or stop development if they are hired.
*  When a developer gets fired, development on all projects stop.
*  A developer can work on a maximum of two projects at the same time.
*  A developer can not start development on the same project twice
*  A developer can not stop development on a project that was not started
*  If a developer starts development on a new project, and is already working on two other projects, development on the project that was started first will be stopped.

The introduction of these concepts bring along a variety of invariants, and thus a need for the `Developer` to be aware of its state.

For example, a client is asking the `Developer` to start development on a given `Project`. The `Developer` must know if it is still hired or not, because as stated, only hired developers can start development on a project. If the `Developer` was previously fired, it will not comply with the request, enforcing this invariant.

### Implementing Invariants
Enforcing invariants is done **before** an event will occur. Meaning, before a `DomainEvent` is passed to `apply`. While state changes will be made right **after** an event occurred. Which is when the appropriate event callback is invoked.

```php
public function fire(): void
{
    if (!$this->hired) {
        //already fired
    }
    $this->apply(new DeveloperFired());
}

private function onDeveloperFired(DeveloperFired $event): void
{
    $this->hired = false;
}
```
We do this for all the invariants that we identify in our domain.
```php
<?php

class Developer extends EventSourcedAggregateRoot
{
    //state we need to track if developer is hired or not
    private $hired = false;

    //state we need to manage which projects are being worked on
    private $projects = [];

    public function __construct(Guid $id)
    {
        parent::__construct($id);
        $event = new DeveloperHired();
        $this->apply($event);
    }

    public function fire(): void
    {
        if (!$this->hired) {
            //can not fire a developer that is not hired
        }
        foreach ($this->projects as $project) {
            //stop all development
            $this->stopDevelopmentOn($project);
        }
        $event = new DeveloperFired();
        $this->apply($event);
    }

    public function startDevelopmentOn(Project $project): void
    {
        if (!$this->hired) {
            //can not start development because this developer is not hired
        }
        if ($this->isDeveloping($project)) {
            //given project is already being worked on
        }
        if (count($this->projects) === 2) {
            //stop development on earliest assigned project
            $this->stopDevelopmentOn($this->projects[0]);
        }
        $event = new DevelopmentStarted($project);
        $this->apply($event);
    }

    public function stopDevelopmentOn(Project $project): void
    {
        if (!$this->hired) {
            //can not stop development because this developer is not hired
        }
        if (!$this->isDeveloping($project)) {
            //can not stop development because development was never started
        }
        $event = new DevelopmentStopped($project);
        $this->apply($event);
    }
    
    public function isDeveloping(Project $project): bool
    {
        foreach ($this->projects as $activeProject) {
            if ($activeProject->equals($project)) {
                return true;
            }
        }
        return false;
    }

    private function onDeveloperHired(DeveloperHired $event): void
    {
        //developer is hired
        $this->hired = true;
    }

    private function onDeveloperFired(DeveloperFired $event): void
    {
        //developer is fired
        $this->hired = false;
    }

    private function onDevelopmentStarted(DevelopmentStarted $event): void
    {
        //development on a project started
        $this->projects[] = $event->getProject();
    }

    private function onDevelopmentStopped(DevelopmentStopped $event): void
    {
        //development on a project has stopped
        $this->removeProject($event->getProject());
    }

    private function removeProject(Project $project): void
    {
        $projects = [];
        foreach ($this->projects as $activeProject) {
            if ($activeProject->equals($project)) {
                continue;
            }
            $projects[] = $project;
        }
        $this->projects = $projects;
    }
}
```
### Orchestrating Aggregates
The `Developer` now accurately expresses the concept it represents, and is ready be interacted with, accumulating events over time.

```php
$halfLife = new Project("Half Life 3");
$aPromise = new Project("A Marketing Promise");

$developer = new Developer($id);
$developer->startDevelopmentOn($halfLife);
$developer->isDeveloping($aPromise); //false
$developer->fire();
$developer->startDevelopmentOn($aPromise); //invariant violation
```
These events can be accessed by using the public `getNewEvents` method, inherited from `EventSourcedAggregateRoot`.
```php
$events = $developer->getNewEvents();
$event[0]; //DeveloperHired
$event[1]; //DevelopmentStarted
$event[2]; //DevelopmentStopped
$event[3]; //DeveloperFired
```
### Versioning Aggregates
The `EventSourcedAggregateRoot` abstract class provides a versioning mechanism for dealing with permanent changes.
If you want to commit to permanent changes to the implemented `EventSourcedAggregateRoot`, use the public `commitChanges` method. This will empty out the new events and update the `EventSourcedAggregateRoot` version, which can be accessed via the `getVersion` method.

```php
count($developer->getNewEvents()); //4
$developer->getVersion(); //0

$developer->commitChanges();
count($developer->getNewEvents()); //0
$developer->getVersion(); //4

$clone = new Developer($id);
$developer->getId() === $clone->getId(); //true
$developer->getVersion() === $clone->getVersion(); //false
```

### Rebuilding Aggregates
As long as the `EventSourcedAggregateRoot` implementation has the required event callbacks implemented, you can rebuild the aggregate by using the `buildFromHistory` method.

Unfortunately, this method has to be a static, as PHP lacks constructor overloading. But it will take any arrangement of historic events, and attempt to invoke the appropriate callback on a new instance for each event given. Therefore building up the aggregate state from first to last.

```php
$history = [];
$history[] = new DeveloperHired();
$history[] = new DevelopmentStarted($halfLife);
$history[] = new DevelopmentStopped($halfLife);

$developer = Developer::buildFromHistory($id, ...$history);
$developer->isDeveloping($halfLife); //false
```
