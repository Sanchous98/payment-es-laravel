<?php

namespace PaymentSystem\Laravel\Repository\Behaviours;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;

trait PersistsEventsBehaviour
{
    public function persistEvents(AggregateRootId $aggregateRootId, string $className, int $aggregateRootVersion, object ...$events): void
    {
        if (count($events) === 0) {
            return;
        }

        // decrease the aggregate root version by the number of raised events
        // so the version of each message represents the version at the time
        // of recording.
        $aggregateRootVersion = $aggregateRootVersion - count($events);
        $metadata = [
            Header::AGGREGATE_ROOT_ID => $aggregateRootId,
            Header::AGGREGATE_ROOT_TYPE => $this->classNameInflector->classNameToType($className),
        ];
        $messages = array_map(function (object $event) use ($metadata, &$aggregateRootVersion) {
            return $this->decorator->decorate(new Message(
                $event,
                $metadata + [Header::AGGREGATE_ROOT_VERSION => ++$aggregateRootVersion]
            ));
        }, $events);

        $this->messages->persist(...$messages);
        $this->dispatcher->dispatch(...$messages);
    }
}