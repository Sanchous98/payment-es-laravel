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
use PaymentSystem\Laravel\Contracts\AccountablePaymentIntentRepository;
use PaymentSystem\Laravel\Messages\AccountDecorator;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Laravel\Repository\Behaviours\PersistsEventsBehaviour;
use PaymentSystem\Laravel\Repository\Behaviours\RetrievesEventsBehaviour;
use PaymentSystem\PaymentIntentAggregateRoot;

class PaymentIntentRepository implements AccountablePaymentIntentRepository
{
    use RetrievesEventsBehaviour;
    use PersistsEventsBehaviour;

    /**
     * @var array<string, PaymentIntentAggregateRoot>
     */
    private array $trackedAggregateRoots = [];

    public function __construct(
        private readonly MessageRepository  $messages,
        private readonly string $className = PaymentIntentAggregateRoot::class,
        private readonly ?MessageDispatcher $dispatcher = null,
        private MessageDecorator            $decorator = new DefaultHeadersDecorator(),
        private readonly ?ClassNameInflector $classNameInflector = null,
        private readonly IdentityMap $identityMap = new IdentityMap(),
    ) {
    }

    public function retrieve(AggregateRootId $id): PaymentIntentAggregateRoot
    {
        if ($this->identityMap->has($id)) {
            $aggregateRoot = $this->identityMap->get($id);

            assert($aggregateRoot instanceof PaymentIntentAggregateRoot);

            self::map(
                (fn(Message $message) => $aggregateRoot->apply($message))->bindTo(null, $aggregateRoot::class),
                $this->messages->retrieveAllAfterVersion($id, $aggregateRoot->aggregateRootVersion()),
            );
        }

        $this->identityMap->set($aggregateRoot ??= $this->retrieveAggregateRoot($id));
        $this->trackedAggregateRoots[$id->toString()] = $aggregateRoot;

        return $aggregateRoot;
    }

    public function persist(PaymentIntentAggregateRoot $paymentIntent): void
    {
        $this->identityMap->set($paymentIntent);
        $this->trackedAggregateRoots[$paymentIntent->aggregateRootId()->toString()] = $paymentIntent;
    }

    public function flush(): void
    {
        foreach ($this->trackedAggregateRoots as $paymentIntent) {
            $this->persistEvents(
                $paymentIntent->aggregateRootId(),
                PaymentIntentAggregateRoot::class,
                $paymentIntent->aggregateRootVersion(),
                ...$paymentIntent->releaseEvents(),
            );
        }
    }

    public function __destruct()
    {
        $this->flush();
    }

    public function forAccount(Account $account): self
    {
        $this->decorator = new MessageDecoratorChain(...array_filter([$this->decorator, new AccountDecorator($account)]));

        return $this;
    }
}
