<?php

namespace PaymentSystem\Laravel\Repository;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;
use PaymentSystem\Laravel\Repository\Behaviours\PersistsEventsBehaviour;
use PaymentSystem\Laravel\Repository\Behaviours\RetrievesEventsBehaviour;
use PaymentSystem\Repositories\SubscriptionPlanRepositoryInterface;
use PaymentSystem\SubscriptionPlanAggregateRoot;

class SubscriptionPlanRepository implements SubscriptionPlanRepositoryInterface
{
    use RetrievesEventsBehaviour;
    use PersistsEventsBehaviour;

    /**
     * @var array<string, SubscriptionPlanAggregateRoot>
     */
    private array $trackedAggregateRoots = [];

    public function __construct(
        private readonly MessageRepository $messages,
        private readonly string $className = SubscriptionPlanAggregateRoot::class,
        private readonly ?MessageDispatcher $dispatcher = null,
        private readonly MessageDecorator $decorator = new DefaultHeadersDecorator(),
        private readonly ?ClassNameInflector $classNameInflector = null,
        private readonly IdentityMap $identityMap = new IdentityMap(),
    ) {
    }

    public function retrieve(AggregateRootId $id): SubscriptionPlanAggregateRoot
    {
        if ($this->identityMap->has($id)) {
            $aggregateRoot = $this->identityMap->get($id);

            assert($aggregateRoot instanceof SubscriptionPlanAggregateRoot);

            self::map(
                (fn(Message $message) => $aggregateRoot->apply($message))->bindTo(null, $aggregateRoot::class),
                $this->messages->retrieveAllAfterVersion($id, $aggregateRoot->aggregateRootVersion()),
            );
        }

        $this->identityMap->set($aggregateRoot ??= $this->retrieveAggregateRoot($id));
        $this->trackedAggregateRoots[$id->toString()] = $aggregateRoot;

        return $aggregateRoot;
    }

    public function persist(SubscriptionPlanAggregateRoot $subscriptionPlan): void
    {
        $this->identityMap->set($subscriptionPlan);
        $this->trackedAggregateRoots[$subscriptionPlan->aggregateRootId()->toString()] = $subscriptionPlan;
    }

    public function flush(): void
    {
        foreach ($this->trackedAggregateRoots as $subscriptionPlan) {
            $this->persistEvents(
                $subscriptionPlan->aggregateRootId(),
                SubscriptionPlanAggregateRoot::class,
                $subscriptionPlan->aggregateRootVersion(),
                ...$subscriptionPlan->releaseEvents(),
            );
        }

        $this->trackedAggregateRoots = [];
    }

    public function __destruct()
    {
        $this->flush();
    }
}