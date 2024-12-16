<?php

namespace PaymentSystem\Laravel\Contracts;

interface MigrationTemplateInterface
{
    public function getStubPath(): string;

    public function getTableName(): string;
}