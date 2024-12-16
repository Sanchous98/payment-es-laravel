<?php

namespace PaymentSystem\Laravel\Jobs;

use EventSauce\EventSourcing\AggregateRootId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PaymentSystem\Commands\CapturePaymentCommandInterface;
use PaymentSystem\Laravel\Contracts\AccountablePaymentIntentRepository;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\TenderInterface;

class CapturePaymentIntentJob implements CapturePaymentCommandInterface, ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use SerializesModels;
    use InteractsWithQueue;

    public function __construct(
        private readonly AggregateRootId $id,
        private readonly Account $account,
        private readonly ?string $amount = null,
        private readonly ?TenderInterface $tender = null,
    ) {
    }

    public function uniqueId(): string
    {
        return $this->id;
    }

    public function __invoke(AccountablePaymentIntentRepository $repository): void
    {
        $repository->forAccount($this->account)->persist($repository->retrieve($this->id)->capture($this));
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function getTender(): ?TenderInterface
    {
        return $this->tender;
    }
}