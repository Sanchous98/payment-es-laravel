<?php

namespace PaymentSystem\Laravel\Jobs;

use EventSauce\EventSourcing\AggregateRootId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PaymentSystem\Laravel\Contracts\AccountableRefundRepository;
use PaymentSystem\Laravel\Models\Account;

readonly class CancelRefundJob implements ShouldQueue
{
    use Queueable;
    use SerializesModels;
    use InteractsWithQueue;

    public function __construct(
        private AggregateRootId $id,
        private Account $account,
    ) {
    }

    public function __invoke(AccountableRefundRepository $repository): void
    {
        $repository
            ->forAccount($this->account)
            ->persist($repository->retrieve($this->id)->cancel());
    }
}