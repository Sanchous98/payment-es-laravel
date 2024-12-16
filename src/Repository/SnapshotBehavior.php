<?php

namespace PaymentSystem\Laravel\Repository;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Snapshotting\AggregateRootWithSnapshotting;

trait SnapshotBehavior
{
    public function retrieveFromSnapshot(AggregateRootId $aggregateRootId): object
    {
        $snapshot = $this->snapshotRepository->retrieve($aggregateRootId);

        return $this->className::reconstituteFromSnapshotAndEvents(
            $snapshot,
            $this->messageRepository->retrieveAllAfterVersion($aggregateRootId, $snapshot->aggregateRootVersion())
        );
    }

    public function storeSnapshot(AggregateRootWithSnapshotting $aggregateRoot): void
    {
        $this->snapshotRepository->persist($aggregateRoot->createSnapshot());
    }
}
