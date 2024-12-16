<?php

use PaymentSystem\Laravel\Serializer\AggregateRootIdNormalizer;
use PaymentSystem\Laravel\Serializer\BillingAddressNormalizer;
use PaymentSystem\Laravel\Serializer\CreditCardNormalizer;
use PaymentSystem\Laravel\Serializer\MoneyNormalizer;
use PaymentSystem\Laravel\Serializer\SourceNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

return [
    'events_table' => 'stored_events',
    'snapshots_table' => 'snapshots',
    'queue' => 'sync',
    'normalizers' => [
        MoneyNormalizer::class,
        SourceNormalizer::class,
        CreditCardNormalizer::class,
        AggregateRootIdNormalizer::class,
        BillingAddressNormalizer::class,
        JsonSerializableNormalizer::class,
        ObjectNormalizer::class,
    ],
];