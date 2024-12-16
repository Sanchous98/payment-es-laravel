<?php

namespace PaymentSystem\Laravel\Contracts;

use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Repositories\TokenRepositoryInterface;

interface AccountableTokenRepository extends TokenRepositoryInterface
{
    public function forAccounts(Account ...$accounts): self;
}