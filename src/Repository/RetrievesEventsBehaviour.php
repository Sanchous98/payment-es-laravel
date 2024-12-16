<?php

namespace PaymentSystem\Laravel\Repository;

use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\EventSourcedAggregateRootRepository;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\UnableToReconstituteAggregateRoot;
use Generator;

trait RetrievesEventsBehaviour
{
    public function retrieveAggregateRoot(AggregateRootId $aggregateRootId): object
    {
        try {
            $className = $this->className;
            $events = $this->retrieveAllEvents($aggregateRootId);

            return $className::reconstituteFromEvents($aggregateRootId, $events);
        } catch (\Throwable $throwable) {
            throw UnableToReconstituteAggregateRoot::becauseOf($throwable->getMessage(), $throwable);
        }
    }

    private function retrieveAllEvents(AggregateRootId $aggregateRootId): Generator
    {
        /** @var Generator<Message> $messages */
        $messages = $this->messages->retrieveAll($aggregateRootId);

        foreach ($messages as $message) {
            yield $message->event();
        }

        return $messages->getReturn();
    }
}