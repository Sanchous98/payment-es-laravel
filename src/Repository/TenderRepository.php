<?php

namespace PaymentSystem\Laravel\Repository;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\DotSeparatedSnakeCaseInflector;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDecoratorChain;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\SynchronousMessageDispatcher;
use PaymentSystem\Laravel\Contracts\AccountableTenderRepository;
use PaymentSystem\Laravel\Messages\AccountDecorator;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Laravel\Repository\Behaviours\PersistsEventsBehaviour;
use PaymentSystem\Laravel\Repository\Behaviours\RetrievesEventsBehaviour;
use PaymentSystem\TenderInterface;

class TenderRepository implements AccountableTenderRepository
{
    use RetrievesEventsBehaviour;
    use PersistsEventsBehaviour;

    /**
     * @var array<string, TenderInterface>
     */
    private array $trackedAggregateRoots = [];

    public function __construct(
        private readonly MessageRepository $messages,
        private readonly MessageDispatcher $dispatcher = new SynchronousMessageDispatcher(),
        private MessageDecorator $decorator = new DefaultHeadersDecorator(),
        private readonly ClassNameInflector $classNameInflector = new DotSeparatedSnakeCaseInflector(),
        private readonly IdentityMap $identityMap = new IdentityMap(),
    ) {
    }

    public function retrieve(AggregateRootId $id): TenderInterface
    {
        if ($this->identityMap->has($id)) {
            $aggregateRoot = $this->identityMap->get($id);

            assert($aggregateRoot instanceof TenderInterface);

            self::map(
                (fn(Message $message) => $aggregateRoot->apply($message))->bindTo(null, $aggregateRoot::class),
                $this->messages->retrieveAllAfterVersion($id, $aggregateRoot->aggregateRootVersion()),
            );
        }

        $this->identityMap->set($aggregateRoot ??= $this->retrieveAggregateRoot($id));
        $this->trackedAggregateRoots[$id->toString()] = $aggregateRoot;

        return $aggregateRoot;
    }

    public function persist(TenderInterface $tender): void
    {
        $this->identityMap->set($tender);

        $this->persistEvents(
            $tender->aggregateRootId(),
            $tender::class,
            $tender->aggregateRootVersion(),
            ...$tender->releaseEvents(),
        );
    }

    public function forAccounts(Account ...$accounts): self
    {
        $decorators = array_map(fn(Account $account) => new AccountDecorator($account), $accounts);
        $this->decorator = new MessageDecoratorChain(...array_filter([$this->decorator, ...$decorators]));

        return $this;
    }
}