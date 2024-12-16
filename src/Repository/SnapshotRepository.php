<?php

namespace PaymentSystem\Laravel\Repository;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Snapshotting\Snapshot;
use EventSauce\EventSourcing\Snapshotting\SnapshotRepository as EventSauceSnapshotRepository;
use EventSauce\IdEncoding\BinaryUuidIdEncoder;
use EventSauce\IdEncoding\IdEncoder;
use Illuminate\Database\ConnectionInterface;

readonly class SnapshotRepository implements EventSauceSnapshotRepository
{
    public function __construct(
        private ConnectionInterface $connection,
        private IdEncoder $aggregateRootIdEncoder = new BinaryUuidIdEncoder(),
        private string $tableName = 'snapshots',
    ) {
    }

    public function persist(Snapshot $snapshot): void
    {
        $this->connection->table($this->tableName)->insert([
            'aggregate_root_id' => $this->aggregateRootIdEncoder->encodeId($snapshot->aggregateRootId()),
            'aggregate_root_version' => $snapshot->aggregateRootVersion(),
            'state' => json_encode($snapshot->state())
        ]);
    }

    public function retrieve(AggregateRootId $id): ?Snapshot
    {
        $result = $this->connection->table($this->tableName)
            ->where(['aggregate_root_id' => $this->aggregateRootIdEncoder->encodeId($id)])
            ->latest('aggregate_root_version')
            ->first();

        return isset($result) ? new Snapshot($id, $result->aggregate_root_version, json_decode($result->state)) : null;
    }
}
