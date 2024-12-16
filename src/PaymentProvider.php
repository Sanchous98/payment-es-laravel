<?php

namespace PaymentSystem\Laravel;

use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\DotSeparatedSnakeCaseInflector;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\TableSchema\DefaultTableSchema;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\InvokableValidationRule;
use PaymentSystem\Contracts\DecryptInterface;
use PaymentSystem\Contracts\EncryptInterface;
use PaymentSystem\Laravel\Console\MakeTablesCommand;
use PaymentSystem\Laravel\Contracts\AccountableDisputeRepository;
use PaymentSystem\Laravel\Contracts\AccountablePaymentIntentRepository;
use PaymentSystem\Laravel\Contracts\AccountablePaymentMethodRepository;
use PaymentSystem\Laravel\Contracts\AccountableRefundRepository;
use PaymentSystem\Laravel\Contracts\AccountableSubscriptionRepository;
use PaymentSystem\Laravel\Contracts\AccountableTenderRepository;
use PaymentSystem\Laravel\Contracts\AccountableTokenRepository;
use PaymentSystem\Laravel\Contracts\MigrationTemplateInterface;
use PaymentSystem\Laravel\Messages\EventDispatcherAdapter;
use PaymentSystem\Laravel\Messages\IlluminateUuidV7MessageRepository;
use PaymentSystem\Laravel\Messages\MessageDispatcherCollector;
use PaymentSystem\Laravel\Migrations\AccountsMigration;
use PaymentSystem\Laravel\Migrations\SnapshotsMigration;
use PaymentSystem\Laravel\Migrations\StoredEventsMigration;
use PaymentSystem\Laravel\Repository\BillingAddressRepository;
use PaymentSystem\Laravel\Repository\DisputeRepository;
use PaymentSystem\Laravel\Repository\IdentityMap;
use PaymentSystem\Laravel\Repository\PaymentIntentRepository;
use PaymentSystem\Laravel\Repository\PaymentMethodRepository;
use PaymentSystem\Laravel\Repository\RefundRepository;
use PaymentSystem\Laravel\Repository\SnapshotRepository;
use PaymentSystem\Laravel\Repository\SubscriptionPlanRepository;
use PaymentSystem\Laravel\Repository\SubscriptionRepository;
use PaymentSystem\Laravel\Repository\TenderRepository;
use PaymentSystem\Laravel\Repository\TokenRepository;
use PaymentSystem\Laravel\Serializer\SymfonyPayloadSerializer;
use PaymentSystem\Laravel\Validation\Country;
use PaymentSystem\Laravel\Validation\Currency;
use PaymentSystem\Laravel\Validation\Phone;
use PaymentSystem\Laravel\Validation\State;
use PaymentSystem\Repositories\BillingAddressRepositoryInterface;
use PaymentSystem\Repositories\DisputeRepositoryInterface;
use PaymentSystem\Repositories\PaymentIntentRepositoryInterface;
use PaymentSystem\Repositories\PaymentMethodRepositoryInterface;
use PaymentSystem\Repositories\RefundRepositoryInterface;
use PaymentSystem\Repositories\SubscriptionPlanRepositoryInterface;
use PaymentSystem\Repositories\SubscriptionRepositoryInterface;
use PaymentSystem\Repositories\TenderRepositoryInterface;
use PaymentSystem\Repositories\TokenRepositoryInterface;
use Symfony\Component\Serializer\Serializer;

class PaymentProvider extends ServiceProvider
{
    public $bindings = [
        DisputeRepositoryInterface::class => DisputeRepository::class,
        PaymentIntentRepositoryInterface::class => PaymentIntentRepository::class,
        PaymentMethodRepositoryInterface::class => PaymentMethodRepository::class,
        RefundRepositoryInterface::class => RefundRepository::class,
        TokenRepositoryInterface::class => TokenRepository::class,
        TenderRepositoryInterface::class => TenderRepository::class,
        BillingAddressRepositoryInterface::class => BillingAddressRepository::class,
        SubscriptionPlanRepositoryInterface::class => SubscriptionPlanRepository::class,
        SubscriptionRepositoryInterface::class => SubscriptionRepository::class,
        AccountableDisputeRepository::class => DisputeRepository::class,
        AccountablePaymentIntentRepository::class => PaymentIntentRepository::class,
        AccountablePaymentMethodRepository::class => PaymentMethodRepository::class,
        AccountableRefundRepository::class => RefundRepository::class,
        AccountableTokenRepository::class => TokenRepository::class,
        AccountableTenderRepository::class => TenderRepository::class,
        AccountableSubscriptionRepository::class => SubscriptionRepository::class,
    ];

    public $singletons = [
        IdentityMap::class,
        ClassNameInflector::class => DotSeparatedSnakeCaseInflector::class,
        EncryptInterface::class => Crypt::class,
        DecryptInterface::class => Crypt::class,
    ];

    public function provides(): array
    {
        return [
            ...array_keys($this->bindings),
            IdentityMap::class,
            ClassNameInflector::class,
            EncryptInterface::class,
            DecryptInterface::class,
            MessageRepository::class,
            SnapshotRepository::class,
            EventDispatcherAdapter::class,
            MakeTablesCommand::class
        ];
    }

    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/payment-es.php', 'payment-es');

        $this->app->tag([EventDispatcherAdapter::class], 'es_dispatchers');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__) . '/config' => $this->app->configPath(),
            ]);
        }

        $this->commands([MakeTablesCommand::class]);
        $this->app->bind(MessageRepository::class, function(Application $app, array $parameters) {
            return new IlluminateUuidV7MessageRepository(
                $app[DatabaseManager::class]->connection(),
                $app['config']->get('payment-es.events_table'),
                new ConstructingMessageSerializer(
                    payloadSerializer: new SymfonyPayloadSerializer(
                        new Serializer(iterator_to_array($app->tagged('normalizers'))),
                    ),
                ),
                tableSchema: $parameters['tableSchema'] ?? new DefaultTableSchema(),
            );
        });
        $this->app->singleton(SnapshotRepository::class, function(Application $app, array $parameters) {
            return new SnapshotRepository(
                $app[DatabaseManager::class]->connection(),
                tableName: $parameters['tableName'],
            );
        });

        $this->app->singleton(MessageDispatcher::class, function(Application $app) {
            return new MessageDispatcherCollector($app->tagged('es_dispatchers'));
        });

        $this->app->when(MakeTablesCommand::class)
            ->needs(MigrationTemplateInterface::class)
            ->giveTagged('payment-migrations');

        $this->app->tag([AccountsMigration::class, SnapshotsMigration::class, StoredEventsMigration::class], 'payment-migrations');
        $this->app->tag($this->app['config']->get('payment-es.normalizers'), 'normalizers');

        Validator::extend('country', function ($attribute, $value, $parameters, $validator) {
            return InvokableValidationRule::make(new Country(...$parameters))
                ->setValidator($validator)
                ->passes($attribute, $value);
        });
        Validator::extend('phone', function ($attribute, $value, $parameters, $validator) {
            return InvokableValidationRule::make(new Phone(...$parameters))
                ->setValidator($validator)
                ->passes($attribute, $value);
        });
        Validator::extend('state', function ($attribute, $value, $parameters, $validator) {
            return InvokableValidationRule::make(new State(...$parameters))
                ->setValidator($validator)
                ->passes($attribute, $value);
        });
        Validator::extend('currency', function ($attribute, $value, $parameters, $validator) {
            return InvokableValidationRule::make(new Currency())
                ->setValidator($validator)
                ->passes($attribute, $value);
        });
    }
}