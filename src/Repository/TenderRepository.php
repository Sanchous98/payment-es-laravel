<?php

namespace PaymentSystem\Laravel\Repository;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\DotSeparatedSnakeCaseInflector;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDecoratorChain;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\SynchronousMessageDispatcher;
use EventSauce\EventSourcing\UnableToReconstituteAggregateRoot;
use Generator;
use PaymentSystem\Laravel\Contracts\AccountableTenderRepository;
use PaymentSystem\Laravel\Messages\AccountDecorator;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\TenderInterface;
use SplObjectStorage;

class TenderRepository implements AccountableTenderRepository
{
    private array $tenders = [];

    public function __construct(
        private readonly MessageRepository $messages,
        private readonly MessageDispatcher $dispatcher = new SynchronousMessageDispatcher(),
        private MessageDecorator           $decorator = new DefaultHeadersDecorator(),
        private readonly ClassNameInflector $classNameInflector = new DotSeparatedSnakeCaseInflector()
    ) {
    }

    public function retrieve(AggregateRootId $id): TenderInterface
    {
        try {
            $messages = $this->messages->retrieveAll($id);
            /** @var class-string<TenderInterface> $className */
            $className = $this->classNameInflector
                ->typeToClassName($messages->current()->header(Header::AGGREGATE_ROOT_TYPE));

            assert(is_a($className, TenderInterface::class, true));

            return $this->tenders[$id->toString()] ??= $className::reconstituteFromEvents($id, self::map(fn(Message $message) => $message->payload(), $messages));
        } catch (\Throwable $throwable) {
            throw UnableToReconstituteAggregateRoot::becauseOf($throwable->getMessage(), $throwable);
        }
    }

    public function persist(TenderInterface $tender): void
    {
        $aggregateRootId = $tender->aggregateRootId();
        $aggregateRootVersion = $tender->aggregateRootVersion();
        $events = $tender->releaseEvents();

        if (count($events) === 0) {
            return;
        }

        // decrease the aggregate root version by the number of raised events
        // so the version of each message represents the version at the time
        // of recording.
        $aggregateRootVersion = $aggregateRootVersion - count($events);
        $metadata = [
            Header::AGGREGATE_ROOT_ID => $aggregateRootId,
            Header::AGGREGATE_ROOT_TYPE => $this->classNameInflector->classNameToType($tender::class),
        ];
        $messages = array_map(function (object $event) use ($metadata, &$aggregateRootVersion) {
            return $this->decorator->decorate(
                new Message(
                    $event,
                    $metadata + [Header::AGGREGATE_ROOT_VERSION => ++$aggregateRootVersion]
                )
            );
        }, $events);

        $this->messages->persist(...$messages);
        $this->dispatcher->dispatch(...$messages);
    }

    public function forAccounts(Account ...$accounts): self
    {
        $decorators = array_map(fn(Account $account) => new AccountDecorator($account), $accounts);
        $this->decorator = new MessageDecoratorChain(...array_filter([$this->decorator, ...$decorators]));

        return $this;
    }

    private static function map(callable $callback, Generator $messages): Generator
    {
        foreach ($messages as $message) {
            yield $callback($message);
        }

        return $messages->getReturn();
    }

    public function __destruct()
    {
        foreach ($this->tenders as $tender) {
            $this->persist($tender);
        }
    }
}