<?php

namespace PaymentSystem\Laravel\Messages;

use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDispatcher;
use Illuminate\Contracts\Events\Dispatcher;

readonly class EventDispatcherAdapter implements MessageDispatcher
{
    public function __construct(private Dispatcher $dispatcher)
    {
    }

    public function dispatch(Message ...$messages): void
    {
        foreach ($messages as $message) {
            $this->dispatcher->dispatch($message->payload()::class, [$message->payload(), $message]);
        }
    }
}
