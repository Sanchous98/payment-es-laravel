<?php

namespace PaymentSystem\Laravel\Messages;

use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDecorator;
use PaymentSystem\Laravel\Models\Account;

readonly class AccountDecorator implements MessageDecorator
{
    public const ACCOUNT_IDS_HEADER = '__account_ids';

    public function __construct(private Account $account)
    {
    }

    public function decorate(Message $message): Message
    {
        $accounts = $message->header(self::ACCOUNT_IDS_HEADER) ?? [];
        $accounts[] = $this->account->id;

        return $message->withHeader(self::ACCOUNT_IDS_HEADER, $accounts);
    }
}