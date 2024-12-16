<?php

namespace PaymentSystem\Laravel\Repository\Behaviours;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\UnableToReconstituteAggregateRoot;
use Generator;
use Throwable;

trait RetrievesEventsBehaviour
{
    private function retrieveAggregateRoot(AggregateRootId $id): object
    {
        try {
            $messages = $this->messages->retrieveAll($id);
            $className = $this->classNameInflector
                ->typeToClassName($messages->current()->header(Header::AGGREGATE_ROOT_TYPE));

            return $this->tenders[$id->toString()] ??= $className::reconstituteFromEvents($id, self::map(fn(Message $message) => $message->payload(), $messages));
        } catch (Throwable $throwable) {
            throw UnableToReconstituteAggregateRoot::becauseOf($throwable->getMessage(), $throwable);
        }
    }

    private static function map(callable $callback, Generator $messages): Generator
    {
        foreach ($messages as $message) {
            yield $callback($message);
        }

        return $messages->getReturn();
    }

}