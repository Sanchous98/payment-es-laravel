<?php

namespace PaymentSystem\Laravel\Repository;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDecoratorChain;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Snapshotting\AggregateRootRepositoryWithSnapshotting;
use PaymentSystem\DisputeAggregateRoot;
use PaymentSystem\Laravel\Contracts\AccountableDisputeRepository;
use PaymentSystem\Laravel\Messages\AccountDecorator;
use PaymentSystem\Laravel\Models\Account;
use SplObjectStorage;

class DisputeRepository implements AggregateRootRepositoryWithSnapshotting, AccountableDisputeRepository
{
    use PersistsEventsBehaviour;
    use RetrievesEventsBehaviour;
    use SnapshotBehavior;

    private SplObjectStorage $disputes;

    public function __construct(
        private readonly MessageRepository  $messages,
        private readonly string             $className = DisputeAggregateRoot::class,
        private readonly ?MessageDispatcher $dispatcher = null,
        private MessageDecorator            $decorator = new DefaultHeadersDecorator(),
        private readonly ?ClassNameInflector $classNameInflector = null
    ) {
        $this->disputes = new SplObjectStorage();
    }

    public function retrieve(AggregateRootId $aggregateRootId): DisputeAggregateRoot
    {
        $this->disputes->attach($dispute = $this->retrieveAggregateRoot($aggregateRootId));

        return $dispute;
    }

    public function forAccount(Account $account): self
    {
        $this->decorator = new MessageDecoratorChain(...array_filter([$this->decorator, new AccountDecorator($account)]));

        return $this;
    }

    public function __destruct()
    {
        foreach ($this->disputes as $dispute) {
            $this->persist($dispute);
        }
    }
}
