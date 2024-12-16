<?php

namespace PaymentSystem\Laravel\Migrations;

use PaymentSystem\Laravel\Contracts\MigrationTemplateInterface;

final class AccountsMigration implements MigrationTemplateInterface
{
    public function getStubPath(): string
    {
        return __DIR__ . '/stubs/create_accounts_table.stub';
    }

    public function getTableName(): string
    {
        return 'accounts';
    }
}