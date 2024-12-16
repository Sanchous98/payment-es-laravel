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
use PaymentSystem\Commands\UpdateSubscriptionPlanCommandInterface;
use PaymentSystem\Repositories\SubscriptionPlanRepositoryInterface;
use PaymentSystem\ValueObjects\MerchantDescriptor;

class UpdateSubscriptionPlanJob implements UpdateSubscriptionPlanCommandInterface, ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use SerializesModels;
    use InteractsWithQueue;

    public function __construct(
        private readonly AggregateRootId $id,
        private readonly ?string $name = null,
        private readonly ?string $description = null,
        private readonly ?Money $money = null,
        private readonly ?DateInterval $interval = null,
        private readonly ?MerchantDescriptor $merchantDescriptor = null,
    ) {
    }

    public function uniqueId(): string
    {
        return $this->id;
    }

    public function __invoke(SubscriptionPlanRepositoryInterface $repository): void
    {
        $repository->persist($repository->retrieve($this->id)->update($this));
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMoney(): ?Money
    {
        return $this->money;
    }

    public function getInterval(): ?DateInterval
    {
        return $this->interval;
    }

    public function getMerchantDescriptor(): ?MerchantDescriptor
    {
        return $this->merchantDescriptor;
    }
}