<?php

namespace PaymentSystem\Laravel\Contracts;

use PaymentSystem\Laravel\Models\Account;
use PaymentSystem\Repositories\SubscriptionRepositoryInterface;

interface AccountableSubscriptionRepository extends SubscriptionRepositoryInterface
{
    public function forAccount(Account $account): self;
}