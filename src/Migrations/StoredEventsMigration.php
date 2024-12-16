<?php

namespace PaymentSystem\Laravel\Migrations;

use PaymentSystem\Laravel\Contracts\MigrationTemplateInterface;

final class StoredEventsMigration implements MigrationTemplateInterface
{
    public function getStubPath(): string
    {
        return __DIR__ . '/stubs/create_stored_events_table.stub';
    }

    public function getTableName(): string
    {
        return 'stored_events';
    }
}