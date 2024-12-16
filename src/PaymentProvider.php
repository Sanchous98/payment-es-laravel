<?php

namespace PaymentSystem\Laravel;

use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\DotSeparatedSnakeCaseInflector;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\IlluminateMessageRepository\IlluminateMessageRepository;
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
use PaymentSystem\Laravel\Contracts\AccountableTenderRepository;
use PaymentSystem\Laravel\Contracts\AccountableTokenRepository;
use PaymentSystem\Laravel\Contracts\IdMapperInterface;
use PaymentSystem\Laravel\Contracts\MigrationTemplateInterface;
use PaymentSystem\Laravel\Messages\EventDispatcherAdapter;
use PaymentSystem\Laravel\Messages\MessageDispatcherCollector;
use PaymentSystem\Laravel\Migrations\AccountsMigration;
use PaymentSystem\Laravel\Migrations\IdMapperMigration;
use PaymentSystem\Laravel\Migrations\SnapshotsMigration;
use PaymentSystem\Laravel\Migrations\StoredEventsMigration;
use PaymentSystem\Laravel\Repository\DisputeRepository;
use PaymentSystem\Laravel\Repository\IdMapperRepository;
use PaymentSystem\Laravel\Repository\PaymentIntentRepository;
use PaymentSystem\Laravel\Repository\PaymentMethodRepository;
use PaymentSystem\Laravel\Repository\RefundRepository;
use PaymentSystem\Laravel\Repository\SnapshotRepository;
use PaymentSystem\Laravel\Repository\TenderRepository;
use PaymentSystem\Laravel\Repository\TokenRepository;
use PaymentSystem\Laravel\Serializer\SymfonyPayloadSerializer;
use PaymentSystem\Laravel\Validation\Country;
use PaymentSystem\Laravel\Validation\Currency;
use PaymentSystem\Laravel\Validation\Phone;
use PaymentSystem\Laravel\Validation\State;
use PaymentSystem\Repositories\DisputeRepositoryInterface;
use PaymentSystem\Repositories\PaymentIntentRepositoryInterface;
use PaymentSystem\Repositories\PaymentMethodRepositoryInterface;
use PaymentSystem\Repositories\RefundRepositoryInterface;
use PaymentSystem\Repositories\TenderRepositoryInterface;
use PaymentSystem\Repositories\TokenRepositoryInterface;
use Symfony\Component\Serializer\Serializer;

class PaymentProvider extends ServiceProvider
{
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

        $this->commands([
            MakeTablesCommand::class,
        ]);
        $this->app->bind(
            MessageRepository::class,
            fn(Application $app, array $parameters) => new IlluminateMessageRepository(
                $app[DatabaseManager::class]->connection(),
                $parameters['tableName'],
                serializer: new ConstructingMessageSerializer(
                    payloadSerializer: new SymfonyPayloadSerializer(
                        new Serializer(iterator_to_array($app->tagged('normalizers'))),
                    ),
                ),
                tableSchema: $parameters['tableSchema'] ?? null,
            )
        );

        $this->app->singleton(EncryptInterface::class, Crypt::class);
        $this->app->singleton(DecryptInterface::class, Crypt::class);

        $this->app->singleton(
            SnapshotRepository::class,
            fn(Application $app, array $parameters) => new SnapshotRepository(
                $app[DatabaseManager::class]->connection(),
                tableName: $parameters['tableName'],
            )
        );

        $this->app->when(PaymentMethodRepository::class)
            ->needs(MessageRepository::class)
            ->give(
                fn(Application $app) => $app
                    ->make(MessageRepository::class, ['tableName' => $app['config']->get('payment-es.events_table')])
            );

        $this->app->when(RefundRepository::class)
            ->needs(MessageRepository::class)
            ->give(
                fn(Application $app) => $app
                    ->make(MessageRepository::class, ['tableName' => $app['config']->get('payment-es.events_table')])
            );

        $this->app->when(DisputeRepository::class)
            ->needs(MessageRepository::class)
            ->give(
                fn(Application $app) => $app
                    ->make(MessageRepository::class, ['tableName' => $app['config']->get('payment-es.events_table')])
            );

        $this->app->when(PaymentIntentRepository::class)
            ->needs(MessageRepository::class)
            ->give(
                fn(Application $app) => $app
                    ->make(MessageRepository::class, ['tableName' => $app['config']->get('payment-es.events_table')])
            );

        $this->app->when(TokenRepository::class)
            ->needs(MessageRepository::class)
            ->give(
                fn(Application $app) => $app
                    ->make(MessageRepository::class, ['tableName' => $app['config']->get('payment-es.events_table')])
            );

        $this->app->when(TenderRepository::class)
            ->needs(MessageRepository::class)
            ->give(
                fn(Application $app) => $app
                    ->make(MessageRepository::class, ['tableName' => $app['config']->get('payment-es.events_table')])
            );

        $this->app->singleton(DisputeRepositoryInterface::class, DisputeRepository::class);
        $this->app->singleton(PaymentIntentRepositoryInterface::class, PaymentIntentRepository::class);
        $this->app->singleton(PaymentMethodRepositoryInterface::class, PaymentMethodRepository::class);
        $this->app->singleton(RefundRepositoryInterface::class, RefundRepository::class);
        $this->app->singleton(TokenRepositoryInterface::class, TokenRepository::class);
        $this->app->singleton(TenderRepositoryInterface::class, TenderRepository::class);
        $this->app->singleton(AccountableDisputeRepository::class, DisputeRepository::class);
        $this->app->singleton(AccountablePaymentIntentRepository::class, PaymentIntentRepository::class);
        $this->app->singleton(AccountablePaymentMethodRepository::class, PaymentMethodRepository::class);
        $this->app->singleton(AccountableRefundRepository::class, RefundRepository::class);
        $this->app->singleton(AccountableTokenRepository::class, TokenRepository::class);
        $this->app->singleton(AccountableTenderRepository::class, TenderRepository::class);

        $this->app->singleton(ClassNameInflector::class, DotSeparatedSnakeCaseInflector::class);
        $this->app->singleton(MessageDispatcher::class, fn(Application $app) => new MessageDispatcherCollector(
            $app->tagged('es_dispatchers'),
        ));

        $this->app->singleton(IdMapperInterface::class, IdMapperRepository::class);

        $this->app->when(MakeTablesCommand::class)
            ->needs(MigrationTemplateInterface::class)
            ->giveTagged('payment-migrations');

        $this->app->tag([AccountsMigration::class, SnapshotsMigration::class, StoredEventsMigration::class, IdMapperMigration::class], 'payment-migrations');
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