<?php

namespace PaymentSystem\Laravel\Repository;

use ArrayIterator;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootId;
use IteratorAggregate;
use Traversable;

class IdentityMap implements IteratorAggregate
{
    /**
     * @var array<string, AggregateRoot>
     */
    private array $aggregateRoots = [];

    public function has(AggregateRootId $aggregateRootId): bool
    {
        return isset($this->aggregateRoots[$aggregateRootId->toString()]);
    }

    public function get(AggregateRootId $aggregateRootId): ?AggregateRoot
    {
        return $this->aggregateRoots[$aggregateRootId->toString()] ?? null;
    }

    public function set(AggregateRoot $aggregateRoot): void
    {
        $this->aggregateRoots[$aggregateRoot->aggregateRootId()->toString()] = $aggregateRoot;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->aggregateRoots);
    }
}