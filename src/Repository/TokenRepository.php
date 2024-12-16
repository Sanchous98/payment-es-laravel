<?php

namespace PaymentSystem\Laravel\Repository;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDecoratorChain;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;
use PaymentSystem\Laravel\Contracts\AccountableTokenRepository;
use PaymentSystem\Laravel\Messages\AccountDecorator;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Laravel\Repository\Behaviours\PersistsEventsBehaviour;
use PaymentSystem\Laravel\Repository\Behaviours\RetrievesEventsBehaviour;
use PaymentSystem\TokenAggregateRoot;

class TokenRepository implements AccountableTokenRepository
{
    use RetrievesEventsBehaviour;
    use PersistsEventsBehaviour;

    /**
     * @var array<string, TokenAggregateRoot>
     */
    private array $trackedAggregateRoots = [];

    public function __construct(
        private readonly MessageRepository $messages,
        private readonly string $className = TokenAggregateRoot::class,
        private readonly ?MessageDispatcher $dispatcher = null,
        private MessageDecorator $decorator = new DefaultHeadersDecorator(),
        private readonly ?ClassNameInflector $classNameInflector = null,
        private readonly IdentityMap $identityMap = new IdentityMap(),
    ) {
    }

    public function retrieve(AggregateRootId $id): TokenAggregateRoot
    {
        if ($this->identityMap->has($id)) {
            $aggregateRoot = $this->identityMap->get($id);

            assert($aggregateRoot instanceof TokenAggregateRoot);

            self::map(
                (fn(Message $message) => $aggregateRoot->apply($message))->bindTo(null, $aggregateRoot::class),
                $this->messages->retrieveAllAfterVersion($id, $aggregateRoot->aggregateRootVersion()),
            );
        }

        $this->identityMap->set($aggregateRoot ??= $this->retrieveAggregateRoot($id));
        $this->trackedAggregateRoots[$id->toString()] = $aggregateRoot;

        return $aggregateRoot;
    }

    public function persist(TokenAggregateRoot $token): void
    {
        $this->identityMap->set($token);
        $this->trackedAggregateRoots[$token->aggregateRootId()->toString()] = $token;
    }

    public function flush(): void
    {
        foreach ($this->trackedAggregateRoots as $token) {
            $this->persistEvents(
                $token->aggregateRootId(),
                TokenAggregateRoot::class,
                $token->aggregateRootVersion(),
                ...$token->releaseEvents(),
            );
        }
    }

    public function __destruct()
    {
        $this->flush();
    }

    public function forAccounts(Account ...$accounts): self
    {
        $decorators = array_map(fn(Account $account) => new AccountDecorator($account), $accounts);
        $this->decorator = new MessageDecoratorChain(...array_filter([$this->decorator, ...$decorators]));

        return $this;
    }
}