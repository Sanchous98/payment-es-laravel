<?php

namespace PaymentSystem\Laravel\Contracts;

use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Repositories\RefundRepositoryInterface;

interface AccountableRefundRepository extends RefundRepositoryInterface
{
    public function forAccount(Account $account): self;
}