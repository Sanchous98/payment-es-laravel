<?php

namespace PaymentSystem\Laravel\Contracts;

use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Repositories\PaymentMethodRepositoryInterface;

interface AccountablePaymentMethodRepository extends PaymentMethodRepositoryInterface
{
    public function forAccounts(Account ...$accounts): self;
}