<?php

namespace PaymentSystem\Laravel\Jobs;

use EventSauce\EventSourcing\AggregateRootId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Money\Money;
use PaymentSystem\Commands\CreateRefundCommandInterface;
use PaymentSystem\Laravel\Contracts\AccountableRefundRepository;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\PaymentIntentAggregateRoot;
use PaymentSystem\RefundAggregateRoot;

class CreateRefundJob implements CreateRefundCommandInterface, ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use SerializesModels;
    use InteractsWithQueue;

    public function __construct(
        private readonly AggregateRootId $id,
        private readonly PaymentIntentAggregateRoot $paymentIntent,
        private readonly Money $money,
        private readonly Account $account,
    ) {
    }

    public function uniqueId(): string
    {
        return $this->id;
    }

    public function __invoke(AccountableRefundRepository $repository): void
    {
        $repository->forAccount($this->account)->persist(RefundAggregateRoot::create($this));
    }

    public function getId(): AggregateRootId
    {
        return $this->id;
    }

    public function getPaymentIntent(): PaymentIntentAggregateRoot
    {
        return $this->paymentIntent;
    }

    public function getMoney(): Money
    {
        return $this->money;
    }
}