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
use SplObjectStorage;

class PaymentIntentRepository implements AggregateRootRepositoryWithSnapshotting, AccountablePaymentIntentRepository
{
    use PersistsEventsBehaviour;
    use RetrievesEventsBehaviour;
    use SnapshotBehavior;

    private array $paymentIntents = [];

    public function __construct(
        private readonly MessageRepository  $messages,
        private readonly string             $className = PaymentIntentAggregateRoot::class,
        private readonly ?MessageDispatcher $dispatcher = null,
        private MessageDecorator            $decorator = new DefaultHeadersDecorator(),
        private readonly ?ClassNameInflector $classNameInflector = null
    ) {
    }

    public function retrieve(AggregateRootId $aggregateRootId): PaymentIntentAggregateRoot
    {
        return $this->paymentIntents[$aggregateRootId->toString()] ??= $this->retrieveAggregateRoot($aggregateRootId);
    }

    public function persist(object $aggregateRoot): void
    {
        $this->persistEvents(
            $aggregateRoot->aggregateRootId(),
            $aggregateRoot->aggregateRootVersion(),
            ...$aggregateRoot->releaseEvents()
        );

        unset($this->paymentIntents[$aggregateRoot->aggregateRootId()->toString()]);
    }

    public function forAccount(Account $account): self
    {
        $this->decorator = new MessageDecoratorChain(...array_filter([$this->decorator, new AccountDecorator($account)]));

        return $this;
    }

    public function __destruct()
    {
        foreach ($this->paymentIntents as $paymentIntent) {
            $this->persist($paymentIntent);
        }
    }
}
