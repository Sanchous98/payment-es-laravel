<?php

namespace PaymentSystem\Laravel\Contracts;

use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Repositories\PaymentIntentRepositoryInterface;

interface AccountablePaymentIntentRepository extends PaymentIntentRepositoryInterface
{
    public function forAccount(Account $account): self;
}