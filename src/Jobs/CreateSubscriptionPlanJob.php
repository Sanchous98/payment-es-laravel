<?php

namespace PaymentSystem\Laravel\Jobs;

use DateInterval;
use EventSauce\EventSourcing\AggregateRootId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Money\Money;
use PaymentSystem\Commands\CreateSubscriptionPlanCommandInterface;
use PaymentSystem\Repositories\SubscriptionPlanRepositoryInterface;
use PaymentSystem\SubscriptionPlanAggregateRoot;
use PaymentSystem\ValueObjects\MerchantDescriptor;

class CreateSubscriptionPlanJob implements CreateSubscriptionPlanCommandInterface, ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use SerializesModels;
    use InteractsWithQueue;

    public function __construct(
        private readonly AggregateRootId $id,
        private readonly string $name,
        private readonly string $description,
        private readonly Money $money,
        private readonly DateInterval $interval,
        private readonly MerchantDescriptor $merchantDescriptor = new MerchantDescriptor(),
    ) {
    }

    public function uniqueId(): string
    {
        return $this->id;
    }

    public function __invoke(SubscriptionPlanRepositoryInterface $repository): void
    {
        $repository->persist(SubscriptionPlanAggregateRoot::create($this));
    }

    public function getId(): AggregateRootId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getMoney(): Money
    {
        return $this->money;
    }

    public function getInterval(): DateInterval
    {
        return $this->interval;
    }

    public function getMerchantDescriptor(): MerchantDescriptor
    {
        return $this->merchantDescriptor;
    }
}