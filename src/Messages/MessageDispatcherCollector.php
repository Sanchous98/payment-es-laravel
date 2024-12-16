<?php

namespace PaymentSystem\Laravel\Messages;

use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDispatcher;

readonly class MessageDispatcherCollector implements MessageDispatcher
{
    /**
     * @param iterable<MessageDispatcher> $dispatchers
     */
    public function __construct(private iterable $dispatchers)
    {
    }

    public function dispatch(Message ...$messages): void
    {
        foreach ($this->dispatchers as $dispatcher) {
            $dispatcher->dispatch(...$messages);
        }
    }
}