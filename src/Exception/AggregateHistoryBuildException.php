<?php

namespace Jeroenvanderlaan\AggregateRoot\Exception;

use Jeroenvanderlaan\AggregateRoot\DomainEvent;
use Throwable;

class AggregateHistoryBuildException extends \Exception
{
    /**
     * @var string
     */
    private $aggregateName;

    /**
     * @var DomainEvent[]
     */
    private $history;

    /**
     * AggregateHistoryBuildException constructor.
     * @param string $aggregateName
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param DomainEvent ...$history
     */
    public function __construct(string $aggregateName, string $message = "", int $code = 0, Throwable $previous = null, DomainEvent ...$history)
    {
        parent::__construct($message, $code, $previous);
        $this->aggregateName = $aggregateName;
        $this->history = $history;
    }

    /**
     * @return string
     */
    public function getAggregateName(): string
    {
        return $this->aggregateName;
    }

    /**
     * @return DomainEvent[]
     */
    public function getHistory(): array
    {
        return $this->history;
    }
}