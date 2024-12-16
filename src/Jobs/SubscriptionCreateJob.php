<?php

namespace PaymentSystem\Laravel\Jobs;

use EventSauce\EventSourcing\AggregateRootId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PaymentSystem\Commands\CreateSubscriptionCommandInterface;
use PaymentSystem\Entities\SubscriptionPlan;
use PaymentSystem\Laravel\Contracts\AccountableSubscriptionRepository;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\PaymentMethodAggregateRoot;
use PaymentSystem\SubscriptionAggregateRoot;

class SubscriptionCreateJob implements CreateSubscriptionCommandInterface, ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use SerializesModels;
    use InteractsWithQueue;

    public function __construct(
        private readonly AggregateRootId $id,
        private readonly SubscriptionPlan $plan,
        private readonly PaymentMethodAggregateRoot $paymentMethod,
        private readonly Account $account,
    )
    {
    }

    public function __invoke(AccountableSubscriptionRepository $repository): void
    {
        $repository->forAccount($this->account)->persist(SubscriptionAggregateRoot::create($this));
    }

    public function uniqueId(): string
    {
        return $this->id;
    }

    public function getId(): AggregateRootId
    {
        return $this->id;
    }

    public function getPlan(): SubscriptionPlan
    {
        return $this->plan;
    }

    public function getPaymentMethod(): PaymentMethodAggregateRoot
    {
        return $this->paymentMethod;
    }
}