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
use PaymentSystem\Laravel\Contracts\AccountablePaymentMethodRepository;
use PaymentSystem\Laravel\Messages\AccountDecorator;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Laravel\Repository\Behaviours\PersistsEventsBehaviour;
use PaymentSystem\Laravel\Repository\Behaviours\RetrievesEventsBehaviour;
use PaymentSystem\PaymentMethodAggregateRoot;

class PaymentMethodRepository implements AccountablePaymentMethodRepository
{
    use RetrievesEventsBehaviour;
    use PersistsEventsBehaviour;

    /**
     * @var array<string, PaymentMethodAggregateRoot>
     */
    private array $trackedAggregateRoots = [];

    public function __construct(
        private readonly MessageRepository $messages,
        private readonly string $className = PaymentMethodAggregateRoot::class,
        private readonly ?MessageDispatcher $dispatcher = null,
        private MessageDecorator $decorator = new DefaultHeadersDecorator(),
        private readonly ?ClassNameInflector $classNameInflector = null,
        private readonly IdentityMap $identityMap = new IdentityMap(),
    ) {
    }

    public function retrieve(AggregateRootId $id): PaymentMethodAggregateRoot
    {
        if ($this->identityMap->has($id)) {
            $aggregateRoot = $this->identityMap->get($id);

            assert($aggregateRoot instanceof PaymentMethodAggregateRoot);

            self::map(
                (fn(Message $message) => $aggregateRoot->apply($message))->bindTo(null, $aggregateRoot::class),
                $this->messages->retrieveAllAfterVersion($id, $aggregateRoot->aggregateRootVersion()),
            );
        }

        $this->identityMap->set($aggregateRoot ??= $this->retrieveAggregateRoot($id));
        $this->trackedAggregateRoots[$id->toString()] = $aggregateRoot;

        return $aggregateRoot;
    }

    public function persist(PaymentMethodAggregateRoot $paymentMethod): void
    {
        $this->identityMap->set($paymentMethod);
        $this->trackedAggregateRoots[$paymentMethod->aggregateRootId()->toString()] = $paymentMethod;
    }

    public function flush(): void
    {
        foreach ($this->trackedAggregateRoots as $paymentMethod) {
            $this->persistEvents(
                $paymentMethod->aggregateRootId(),
                PaymentMethodAggregateRoot::class,
                $paymentMethod->aggregateRootVersion(),
                ...$paymentMethod->releaseEvents(),
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
