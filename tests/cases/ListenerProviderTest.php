<?php

declare(strict_types=1);

namespace RologIo\Tests;

use RologIo\Listener;
use RologIo\ListenerProvider;

use function RologIo\after;
use function RologIo\before;
use function RologIo\identify;
use function RologIo\prioritize;

class ListenerProviderTest extends TestCase
{
    /**
     * @test
     */
    public function testAddByPriority(): void
    {
        $cb1 = static fn (object $obj) => $obj;
        $cb2 = static fn (object $obj) => $obj;
        $cb3 = #[Listener(priority: 5)] static fn (object $obj) => $obj;
        $cb4 = static fn (object $obj) => $obj;
        $cb5 = prioritize(3, static fn (object $obj) => $obj);
        $cb6 = static fn (object $obj) => $obj;

        $provider = ListenerProvider::new();
        $provider->addListeners($cb1, $cb2, $cb3, $cb4, $cb5, $cb6);

        $actual = [];
        foreach ($provider->getListenersForEvent(new \stdClass()) as $item) {
            $actual[] = $item;
        }
        static::assertSame([$cb1, $cb2, $cb5, $cb3, $cb4, $cb6], $actual);
    }

    /**
     * @return void
     */
    public function testFindIds(): void
    {
        $first = identify('two', #[Listener(after:'one')] static fn (object $obj) => $obj);
        $second = prioritize(1, [$this, 'dummy']);
        $third = after('two', static fn (object $obj) => $obj);
        $fourth = before(
            __CLASS__ . '::dummy',
            identify('one', static fn (object $obj) => $obj)
        );

        $provider = ListenerProvider::new();
        $provider->addListeners($first, $second, $third, $fourth);

        $actual = [];
        foreach ($provider->getListenersForEvent(new \stdClass()) as $item) {
            $actual[] = $item;
        }

        static::assertSame([$fourth, $first, $third, $second], $actual);
    }

    /**
     * @param object $object
     * @return object
     */
    public function dummy(object $object): object
    {
        return $object;
    }
}
