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
use PaymentSystem\Laravel\Contracts\AccountablePaymentIntentRepository;
use PaymentSystem\Laravel\Messages\AccountDecorator;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\PaymentIntentAggregateRoot;

class PaymentIntentRepository implements AggregateRootRepositoryWithSnapshotting, AccountablePaymentIntentRepository
{
    use PersistsEventsBehaviour;
    use RetrievesEventsBehaviour;
    use SnapshotBehavior;

    public function __construct(
        private MessageRepository $messages,
        private string $className = PaymentIntentAggregateRoot::class,
        private ?MessageDispatcher $dispatcher = null,
        private MessageDecorator $decorator = new DefaultHeadersDecorator(),
        private ?ClassNameInflector $classNameInflector = null
    ) {
    }

    public function retrieve(AggregateRootId $aggregateRootId): PaymentIntentAggregateRoot
    {
        return $this->retrieveAggregateRoot($aggregateRootId);
    }

    public function forAccount(Account $account): self
    {
        $this->decorator = new MessageDecoratorChain(...array_filter([$this->decorator, new AccountDecorator($account)]));

        return $this;
    }
}