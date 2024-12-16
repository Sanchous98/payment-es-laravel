<?php

namespace PaymentSystem\Laravel\Messages;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\PaginationCursor;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use EventSauce\EventSourcing\UnableToPersistMessages;
use EventSauce\EventSourcing\UnableToRetrieveMessages;
use EventSauce\IdEncoding\IdEncoder;
use EventSauce\MessageRepository\TableSchema\DefaultTableSchema;
use EventSauce\MessageRepository\TableSchema\TableSchema;
use Generator;
use Illuminate\Database\ConnectionInterface;
use EventSauce\IdEncoding\BinaryUuidIdEncoder;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use Throwable;

readonly class IlluminateUuidV7MessageRepository implements MessageRepository
{
    public function __construct(
        private ConnectionInterface $connection,
        private string              $tableName,
        private MessageSerializer   $serializer,
        private int                 $jsonEncodeOptions = 0,
        private TableSchema         $tableSchema = new DefaultTableSchema(),
        private IdEncoder           $idEncoder = new BinaryUuidIdEncoder(),
    ) {
    }

    public function persist(Message ...$messages): void
    {
        if (count($messages) === 0) {
            return;
        }

        $values = [];
        $versionColumn = $this->tableSchema->versionColumn();
        $eventIdColumn = $this->tableSchema->eventIdColumn();
        $payloadColumn = $this->tableSchema->payloadColumn();
        $aggregateRootIdColumn = $this->tableSchema->aggregateRootIdColumn();
        $additionalColumns = $this->tableSchema->additionalColumns();

        foreach ($messages as $message) {
            $parameters = [];
            $payload = $this->serializer->serializeMessage($message);
            $parameters[$versionColumn] = $payload['headers'][Header::AGGREGATE_ROOT_VERSION] ?? 0;
            $payload['headers'][Header::EVENT_ID] = $payload['headers'][Header::EVENT_ID] ?? Uuid::uuid7()->toString();
            $parameters[$eventIdColumn] = $this->idEncoder->encodeId($payload['headers'][Header::EVENT_ID]);
            $parameters[$payloadColumn] = json_encode($payload, $this->jsonEncodeOptions);
            $parameters[$aggregateRootIdColumn] = $this->idEncoder->encodeId($payload['headers'][Header::AGGREGATE_ROOT_ID]);

            foreach ($additionalColumns as $column => $header) {
                $parameters[$column] = $payload['headers'][$header];
            }

            $values[] = $parameters;
        }

        try {
            $this->connection->transaction(fn() => $this->connection->table($this->tableName)->insert($values));
        } catch (Throwable $exception) {
            throw UnableToPersistMessages::dueTo($exception->getMessage(), $exception);
        }
    }

    public function retrieveAll(AggregateRootId $id): Generator
    {
        $builder = $this->connection->table($this->tableName)
            ->where($this->tableSchema->aggregateRootIdColumn(), $this->idEncoder->encodeId($id))
            ->orderBy($this->tableSchema->versionColumn(), 'ASC');

        try {
            return $this->yieldMessagesForResult($builder->pluck('payload'));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMessages::dueTo('', $exception);
        }
    }

    /** @psalm-return Generator<Message> */
    public function retrieveAllAfterVersion(AggregateRootId $id, int $aggregateRootVersion): Generator
    {
        $versionColumn = $this->tableSchema->versionColumn();
        $builder = $this->connection->table($this->tableName)
            ->where($this->tableSchema->aggregateRootIdColumn(), $this->idEncoder->encodeId($id))
            ->where($versionColumn, '>', $aggregateRootVersion)
            ->orderBy($versionColumn, 'ASC');

        try {
            return $this->yieldMessagesForResult($builder->get(['payload']));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMessages::dueTo('', $exception);
        }
    }

    /**
     * @param Collection<int, mixed> $result
     * @psalm-return Generator<int, Message>
     */
    private function yieldMessagesForResult(Collection $result): Generator
    {
        foreach ($result as $payload) {
            yield $message = $this->serializer->unserializePayload(json_decode($payload, true));
        }

        return isset($message) ? $message->header(Header::AGGREGATE_ROOT_VERSION) ?: 0 : 0;
    }

    public function paginate(PaginationCursor $cursor): Generator
    {
        $offset = $cursor->offset();
        $incrementalIdColumn = $this->tableSchema->incrementalIdColumn();
        $builder = $this->connection->table($this->tableName)
            ->limit($cursor->limit())
            ->where($incrementalIdColumn, '>', $cursor->offset())
            ->orderBy($incrementalIdColumn, 'ASC');

        try {
            $result = $builder->get([$incrementalIdColumn, 'payload']);

            foreach ($result as $row) {
                $offset = $row->{$incrementalIdColumn};
                yield $this->serializer->unserializePayload(json_decode($row->payload, true));
            }

            return $cursor->withOffset($offset);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMessages::dueTo('', $exception);
        }
    }
}