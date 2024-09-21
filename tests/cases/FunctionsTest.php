<?php

declare(strict_types=1);

namespace RologIo\Tests;

use RologIo\Listener;
use Psr\EventDispatcher\ListenerProviderInterface;

use function RologIo\{
    before,
    listen,
    dispatch,
    identify,
    prioritize,
    subscribe,
    appendListenerProvider
};

/**
 * @runTestsInSeparateProcesses
 */
class FunctionsTest extends TestCase
{
    /**
     * @test
     */
    public function testNoOrder(): void
    {
        listen(static fn (object $object) => $object->collector[] = 'first');
        listen(static fn (object $object) => $object->collector[] = 'second');
        listen(static fn (object $object) => $object->collector[] = 'third');

        $initial = (object) ['collector' => []];
        $result = dispatch($initial);

        static::assertSame(['first', 'second', 'third'], $result->collector);
        static::assertSame($initial, $result);

        $initial->collector = [];
        $result = dispatch($initial);

        static::assertSame(['first', 'second', 'third'], $result->collector);
        static::assertSame($initial, $result);
    }

    /**
     * @test
     */
    public function testInheritance(): void
    {
        eval(
            <<<'PHP'
            class Foo {
                public array $messages = [];
            }
            class Bar extends Foo {}
            PHP
        );

        listen(static fn (\Foo $foo) => $foo->messages[] = 'First');

        listen(static fn (\Bar $bar) => $bar->messages[] = 'Second');

        $foo = dispatch(new \Foo());
        static::assertSame(['First'], $foo->messages);

        $bar = dispatch(new \Bar());
        static::assertSame(['First', 'Second'], $bar->messages);
    }

    /**
     * @test
     */
    public function testPriority(): void
    {
        $listener1 = #[Listener(priority: 12)]
        static fn (object $object) => $object->collector[] = '!';

        $listener2 = #[Listener(priority: 10)]
        static fn (object $object) => $object->collector[] = 'World';

        $listener3 = #[Listener(priority: 5)]
        static fn (object $object) => $object->collector[] = 'Hello';

        listen($listener1, $listener2, $listener3);

        $initial = (object) ['collector' => []];
        $result = dispatch($initial);

        static::assertSame(['Hello', 'World', '!'], $result->collector);
        static::assertSame($initial, $result);
    }

    /**
     * @test
     */
    public function testRelativeOrder(): void
    {
        $cb1 = #[Listener(after: 'hello')] static fn (object $obj) => $obj->collector[] = 'World';
        $listener1 = identify('world', $cb1);

        $cb2 = static fn (object $obj) => $obj->collector[] = '?';
        $listener2 = prioritize(15, identify('?', $cb2));

        $cb3 = static fn (object $obj) => $obj->collector[] = 'Hello';
        $listener3 = identify('hello', before('!', $cb3));

        $cb4 = #[Listener(priority: 12)] static fn (object $obj) => $obj->collector[] = '!';
        $listener4 = identify('!', $cb4);

        listen($listener1, $listener2, $listener3, $listener4);

        $initial = (object) ['collector' => []];
        $result = dispatch($initial);

        static::assertSame(['Hello', 'World', '!', '?'], $result->collector);
        static::assertSame($initial, $result);

        $initial->collector = [];
        $result = dispatch($initial);

        static::assertSame(['Hello', 'World', '!', '?'], $result->collector);
        static::assertSame($initial, $result);
    }

    /**
     * @test
     */
    public function testSubscriber(): void
    {
        $class = new class ()
        {
            #[Listener(id: 'world', after: 'hello')]
            public static function world(\stdClass $event): void
            {
                $event->collector[] = 'World';
            }

            #[Listener(id: '?', priority: 15)]
            public function question(\stdClass $event): void
            {
                $event->collector[] = '?';
            }

            public function iDoNothing(\stdClass $event): void
            {
                $event->collector[] = 'Nothing';
            }

            #[Listener(before: '!', id: 'hello')]
            public static function hello(\stdClass $event): void
            {
                $event->collector[] = 'Hello';
            }

            #[Listener(id: '!', priority: 12)]
            public function exclamation(\stdClass $event): void
            {
                $event->collector[] = '!';
            }
        };

        subscribe($class);

        $initial = (object) ['collector' => []];
        $result = dispatch($initial);

        static::assertSame(['Hello', 'World', '!', '?'], $result->collector);
        static::assertSame($initial, $result);
    }

    /**
     * @test
     */
    public function testAppendProvider(): void
    {
        $provider = new class () implements ListenerProviderInterface
        {
            // phpcs:disable Inpsyde.CodeQuality.NoAccessors
            public function getListenersForEvent(object $event): iterable
            {
                // phpcs:enable Inpsyde.CodeQuality.NoAccessors
                return [
                    static fn (object $object) => $object->message .= '!!!',
                ];
            }
        };
        appendListenerProvider($provider);

        listen(static fn (object $object) => $object->message .= 'Hello World');

        $result = dispatch((object) ['message' => '']);

        static::assertSame('Hello World!!!', $result->message);
    }
}
