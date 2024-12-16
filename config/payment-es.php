<?php

use PaymentSystem\Laravel\Serializer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateIntervalNormalizer;

return [
    'events_table' => 'stored_events',
    'snapshots_table' => 'snapshots',
    'queue' => 'sync',
    'normalizers' => [
        Serializer\MoneyNormalizer::class,
        Serializer\SourceNormalizer::class,
        Serializer\CreditCardNormalizer::class,
        Serializer\AggregateRootIdNormalizer::class,
        Serializer\CountryNormalizer::class,
        Serializer\EmailNormalizer::class,
        Serializer\PhoneNumberNormalizer::class,
        Serializer\StateNormalizer::class,
        DateIntervalNormalizer::class,
        JsonSerializableNormalizer::class,
        ObjectNormalizer::class,
    ],
];