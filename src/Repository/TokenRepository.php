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
use PaymentSystem\Laravel\Contracts\AccountableTokenRepository;
use PaymentSystem\Laravel\Messages\AccountDecorator;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\TokenAggregateRoot;

class TokenRepository implements AggregateRootRepositoryWithSnapshotting, AccountableTokenRepository
{
    use PersistsEventsBehaviour;
    use RetrievesEventsBehaviour;
    use SnapshotBehavior;

    public function __construct(
        private MessageRepository $messages,
        private string $className = TokenAggregateRoot::class,
        private ?MessageDispatcher $dispatcher = null,
        private MessageDecorator $decorator = new DefaultHeadersDecorator(),
        private ?ClassNameInflector $classNameInflector = null
    ) {
    }

    public function retrieve(AggregateRootId $aggregateRootId): TokenAggregateRoot
    {
        return $this->retrieveAggregateRoot($aggregateRootId);
    }

    public function forAccounts(Account ...$accounts): self
    {
        $decorators = array_map(fn(Account $account) => new AccountDecorator($account), $accounts);
        $this->decorator = new MessageDecoratorChain(...array_filter([$this->decorator, ...$decorators]));

        return $this;
    }
}