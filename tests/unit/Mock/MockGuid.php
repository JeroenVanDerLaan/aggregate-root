<?php

namespace Jeroenvanderlaan\AggregateRoot\Tests\Unit\Mock;

use Jeroenvanderlaan\AggregateRoot\Guid;

class MockGuid implements Guid
{
    /**
     * @var string
     */
    private $id;

    /**
     * MockGuid constructor.
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->id;
    }

    /**
     * @param Guid $guid
     * @return bool
     */
    public function equals(Guid $guid): bool
    {
        return $this->toString() === $guid->toString();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

}