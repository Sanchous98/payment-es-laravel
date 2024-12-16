<?php

namespace PaymentSystem\Laravel\Jobs;

use EventSauce\EventSourcing\AggregateRootId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use PaymentSystem\Commands\CreateTokenCommandInterface;
use PaymentSystem\Laravel\Contracts\AccountableTokenRepository;
use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\TokenAggregateRoot;
use PaymentSystem\Entities\BillingAddress;
use PaymentSystem\ValueObjects\CreditCard;

class CreateTokenJob implements CreateTokenCommandInterface, ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use SerializesModels;
    use InteractsWithQueue;

    private readonly Collection $accounts;

    public function __construct(
        private readonly AggregateRootId $id,
        private readonly BillingAddress $billingAddress,
        private readonly CreditCard $card,
        Account ...$accounts
    ) {
        $accounts = collect($accounts);

        if ($accounts->isEmpty()) {
            $accounts = Account::query()->cursor();
        }

        if ($accounts->isEmpty()) {
            throw new RecordsNotFoundException('No accounts found.');
        }

        $this->accounts = collect($accounts);
    }

    public function uniqueId(): string
    {
        return $this->id;
    }

    public function __invoke(AccountableTokenRepository $repository): void
    {
        $repository->forAccounts(...$this->accounts)->persist(TokenAggregateRoot::create($this));
    }

    public function getId(): AggregateRootId
    {
        return $this->id;
    }

    public function getCard(): CreditCard
    {
        return $this->card;
    }

    public function getBillingAddress(): BillingAddress
    {
        return $this->billingAddress;
    }
}