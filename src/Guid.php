<?php

namespace Jeroenvanderlaan\AggregateRoot;

interface Guid
{
    /**
     * @return string
     */
    public function toString(): string;

    /**
     * @param Guid $id
     * @return bool
     */
    public function equals(Guid $id): bool;

    /**
     * @return string
     */
    public function __toString(): string;
}