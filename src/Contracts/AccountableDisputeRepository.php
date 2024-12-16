<?php

namespace PaymentSystem\Laravel\Contracts;

use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Repositories\DisputeRepositoryInterface;

interface AccountableDisputeRepository extends DisputeRepositoryInterface
{
    public function forAccount(Account $account): self;
}