<?php

namespace PaymentSystem\Laravel\Repository;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;
use PaymentSystem\BillingAddressAggregationRoot;
use PaymentSystem\Laravel\Repository\Behaviours\PersistsEventsBehaviour;
use PaymentSystem\Laravel\Repository\Behaviours\RetrievesEventsBehaviour;
use PaymentSystem\Repositories\BillingAddressRepositoryInterface;

class BillingAddressRepository implements BillingAddressRepositoryInterface
{
    use RetrievesEventsBehaviour;
    use PersistsEventsBehaviour;

    /**
     * @var array<string, BillingAddressAggregationRoot>
     */
    private array $trackedAggregateRoots = [];

    public function __construct(
        private readonly MessageRepository $messages,
        private readonly string $className = BillingAddressAggregationRoot::class,
        private readonly ?MessageDispatcher $dispatcher = null,
        private readonly MessageDecorator $decorator = new DefaultHeadersDecorator(),
        private readonly ?ClassNameInflector $classNameInflector = null,
        private readonly IdentityMap $identityMap = new IdentityMap(),
    ) {
    }

    public function retrieve(AggregateRootId $id): BillingAddressAggregationRoot
    {
        if ($this->identityMap->has($id)) {
            $aggregateRoot = $this->identityMap->get($id);

            assert($aggregateRoot instanceof BillingAddressAggregationRoot);

            self::map(
                (fn(Message $message) => $aggregateRoot->apply($message))->bindTo(null, $aggregateRoot::class),
                $this->messages->retrieveAllAfterVersion($id, $aggregateRoot->aggregateRootVersion()),
            );
        }

        $this->identityMap->set($aggregateRoot ??= $this->retrieveAggregateRoot($id));
        $this->trackedAggregateRoots[$id->toString()] = $aggregateRoot;

        return $aggregateRoot;
    }

    public function persist(BillingAddressAggregationRoot $billingAddress): void
    {
        $this->identityMap->set($billingAddress);
        $this->trackedAggregateRoots[$billingAddress->aggregateRootId()->toString()] = $billingAddress;
    }

    public function flush(): void
    {
        foreach ($this->trackedAggregateRoots as $billingAddress) {
            $this->persistEvents(
                $billingAddress->aggregateRootId(),
                BillingAddressAggregationRoot::class,
                $billingAddress->aggregateRootVersion(),
                ...$billingAddress->releaseEvents(),
            );
        }

        $this->trackedAggregateRoots = [];
    }

    public function __destruct()
    {
        $this->flush();
    }
}