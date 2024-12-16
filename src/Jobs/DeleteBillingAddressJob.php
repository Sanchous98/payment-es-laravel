<?php

namespace PaymentSystem\Laravel\Jobs;

use EventSauce\EventSourcing\AggregateRootId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PaymentSystem\Repositories\BillingAddressRepositoryInterface;

class DeleteBillingAddressJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use SerializesModels;
    use InteractsWithQueue;

    public function __construct(private readonly AggregateRootId $id)
    {
    }

    public function uniqueId(): string
    {
        return $this->id;
    }

    public function __invoke(BillingAddressRepositoryInterface $repository): void
    {
        $repository->persist($repository->retrieve($this->id)->delete());
    }
}