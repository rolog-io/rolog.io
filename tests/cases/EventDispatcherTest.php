<?php

declare(strict_types=1);

namespace RologIo\Tests;

use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use RologIo\EventDispatcher;

/**
 * @runTestsInSeparateProcesses
 */
class EventDispatcherTest extends TestCase
{
    /**
     * @return void
     */
    public function testMultipleProviders(): void
    {
        $dispatcher = $this->factoryDispatcher(7);
        $event = $this->factoryStoppableEvent(8);
        $dispatched = $dispatcher->dispatch($event);

        static::assertSame(7, $dispatched->executed);
    }

    /**
     * @return void
     */
    public function testEmptyProviders(): void
    {
        $dispatcher = $this->factoryDispatcher(0);
        $event = $this->factoryStoppableEvent(PHP_INT_MAX);
        $dispatched = $dispatcher->dispatch($event);

        static::assertSame(0, $dispatched->executed);
    }

    /**
     * @return void
     */
    public function testPropagationStopped(): void
    {
        $dispatcher = $this->factoryDispatcher(9);
        $event = $this->factoryStoppableEvent(7);
        $dispatched = $dispatcher->dispatch($event);

        static::assertSame(7, $dispatched->executed);
    }

    /**
     * @param positive-int $number
     * @return EventDispatcher
     */
    private function factoryDispatcher(int $number): EventDispatcher
    {
        assert($number >= 0);

        if ($number === 0) {
            return EventDispatcher::new();
        }

        $dispatcher = EventDispatcher::new($this->factoryListenerProvider(1));

        if ($number <= 2) {
            ($number === 2) and $dispatcher->appendProvider($this->factoryListenerProvider(1));

            return $dispatcher;
        }

        $number--;
        $provider2 = $this->factoryListenerProvider((int) floor($number / 2));
        $provider3 = $this->factoryListenerProvider((int) ceil($number / 2));

        return $dispatcher->appendProvider($provider2)->appendProvider($provider3);
    }

    /**
     * @param int $count
     * @return ListenerProviderInterface
     *
     * phpcs:disable Inpsyde.CodeQuality.NestingLevel
     */
    private function factoryListenerProvider(int $count = 0): ListenerProviderInterface
    {
        // phpcs:enable Inpsyde.CodeQuality.NestingLevel
        return new readonly class ($count) implements ListenerProviderInterface
        {
            public function __construct(private int $count)
            {
            }

            public function getListenersForEvent(object $event): iterable // phpcs:ignore
            {
                $listener = static function (object $event): object {
                    $event->executed++;
                    return $event;
                };
                $listeners = [];
                for ($i = 0; $i < $this->count; $i++) {
                    $listeners[] = clone $listener;
                }

                return $listeners;
            }
        };
    }

    /**
     * @param int $maxExecution
     * @return \stdClass&StoppableEventInterface
     */
    private function factoryStoppableEvent(int $maxExecution = 1): StoppableEventInterface
    {
        return new class ($maxExecution) extends \stdClass implements StoppableEventInterface
        {
            public int $executed = 0; // phpcs:ignore

            public function __construct(private readonly int $maxExecution)
            {
            }

            public function isPropagationStopped(): bool
            {
                static $count = 0;
                $count++;
                return $count >= $this->maxExecution;
            }
        };
    }
}
