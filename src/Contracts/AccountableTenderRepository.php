<?php

namespace PaymentSystem\Laravel\Contracts;

use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Repositories\TenderRepositoryInterface;

interface AccountableTenderRepository extends TenderRepositoryInterface
{
    public function forAccounts(Account ...$accounts): self;
}