<?php

namespace PaymentSystem\Laravel\Migrations;

use PaymentSystem\Laravel\Contracts\MigrationTemplateInterface;

final class SnapshotsMigration implements MigrationTemplateInterface
{
    public function getStubPath(): string
    {
        return __DIR__ . '/stubs/create_snapshots_table.stub';
    }

    public function getTableName(): string
    {
        return 'snapshots';
    }
}