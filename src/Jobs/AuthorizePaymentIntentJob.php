<?php

namespace PaymentSystem\Laravel\Jobs;

use EventSauce\EventSourcing\AggregateRootId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Money\Money;
use PaymentSystem\Commands\AuthorizePaymentCommandInterface;
use PaymentSystem\Laravel\Contracts\AccountablePaymentIntentRepository;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\PaymentIntentAggregateRoot;
use PaymentSystem\TenderInterface;
use PaymentSystem\ValueObjects\ThreeDSResult;

class AuthorizePaymentIntentJob implements AuthorizePaymentCommandInterface, ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use SerializesModels;
    use InteractsWithQueue;

    public function __construct(
        private readonly AggregateRootId $id,
        private readonly Money $money,
        private readonly Account $account,
        private readonly ?TenderInterface $tender = null,
        private readonly string $merchantDescriptor = '',
        private readonly string $description = '',
        private readonly ?ThreeDSResult $threeDSResult = null,
    ) {
    }

    public function uniqueId(): string
    {
        return $this->id;
    }

    public function __invoke(AccountablePaymentIntentRepository $repository): void
    {
        $repository->forAccount($this->account)->persist(PaymentIntentAggregateRoot::authorize($this));
    }

    public function getId(): AggregateRootId
    {
        return $this->id;
    }

    public function getMoney(): Money
    {
        return $this->money;
    }

    public function getTender(): ?TenderInterface
    {
        return $this->tender;
    }

    public function getMerchantDescriptor(): string
    {
        return $this->merchantDescriptor;
    }

    public function getThreeDSResult(): ?ThreeDSResult
    {
        return $this->threeDSResult;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
