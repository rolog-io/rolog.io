<?php

declare(strict_types=1);

namespace RologIo;

use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * @return ListenerProvider
 */
function provider(): ListenerProvider
{
    static $provider;
    $provider ??= ListenerProvider::new();

    return $provider;
}

/**
 * @return EventDispatcher
 */
function dispatcher(): EventDispatcher
{
    static $dispatcher;
    $dispatcher ??= EventDispatcher::new(provider());

    return $dispatcher;
}

/**
 * @param ListenerProviderInterface $provider
 * @return void
 */
function appendListenerProvider(ListenerProviderInterface $provider): void
{
    dispatcher()->appendProvider($provider);
}

/**
 * @param object $event
 * @return object
 */
function dispatch(object $event): object
{
    return dispatcher()->dispatch($event);
}

/**
 * @param callable $listener
 * @param callable ...$listeners
 * @return void
 */
function listen(callable $listener, callable ...$listeners): void
{
    provider()->addListeners($listener, ...$listeners);
}

/**
 * @param object $subscriber
 * @return void
 */
function subscribe(object $subscriber): void
{
    provider()->addSubscriber($subscriber);
}

/**
 * @param string $id
 * @param callable $listener
 * @return Listener\CallbackIdListener
 */
function identify(string $id, callable $listener): Listener\CallbackIdListener
{
    return Listener\CallbackIdListener::new($id, $listener);
}

/**
 * @param int $priority
 * @param callable $listener
 * @return Listener\OrderableListener
 */
function prioritize(int $priority, callable $listener): Listener\OrderableListener
{
    return Listener\OrderableListener::new($listener, Utils\Option::PRIORITY($priority));
}

/**
 * @param string $id
 * @param callable $listener
 * @return Listener\OrderableListener
 */
function before(string $id, callable $listener): Listener\OrderableListener
{
    return Listener\OrderableListener::new($listener, Utils\Option::BEFORE($id));
}

/**
 * @param string $id
 * @param callable $listener
 * @return Listener\OrderableListener
 */
function after(string $id, callable $listener): Listener\OrderableListener
{
    return Listener\OrderableListener::new($listener, Utils\Option::AFTER($id));
}
