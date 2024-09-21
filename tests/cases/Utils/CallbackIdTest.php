<?php

declare(strict_types=1);

namespace RologIo\Tests\Utils;

use Psr\EventDispatcher\StoppableEventInterface;
use RologIo\Tests\TestCase;
use RologIo\Utils\CallbackId;

class CallbackIdTest extends TestCase
{
    /**
     * @test
     */
    public function testCallbackIds(): void
    {
        static::assertSame('strtolower', CallbackId::of('strtolower')->id);
        static::assertSame('strtolower', (string) CallbackId::of('strtolower'));

        static::assertSame(
            __CLASS__ . '::throwException',
            CallbackId::of(__CLASS__ . '::throwException')->id
        );
        static::assertSame(
            __CLASS__ . '::throwException',
            (string) CallbackId::of(__CLASS__ . '::throwException')
        );

        static::assertSame(
            __CLASS__ . '::throwException',
            CallbackId::of([__CLASS__, 'throwException'])->id
        );
        static::assertSame(
            __CLASS__ . '::throwException',
            (string) CallbackId::of([__CLASS__, 'throwException'])
        );

        static::assertSame(__METHOD__, CallbackId::of([$this, __FUNCTION__])->id);
        static::assertSame(__METHOD__, (string) CallbackId::of([$this, __FUNCTION__]));

        $ofClosure1 = CallbackId::of(static fn (StoppableEventInterface $object) => true);
        $ofClosure2 = CallbackId::of(static fn (object $object) => true);
        static::assertTrue(str_starts_with($ofClosure1->id, 'Closure#'));
        static::assertTrue(str_starts_with($ofClosure2->id, 'Closure#'));
        static::assertNotSame($ofClosure1->id, $ofClosure2->id);

        $ofClass1 = CallbackId::of(new class ()
        {
            public function __invoke()
            {
            }
        });
        $ofClass2 = CallbackId::of(new class ()
        {
            public function __invoke()
            {
            }
        });
        static::assertTrue(str_starts_with($ofClass1->id, 'class@anonymous'));
        static::assertTrue(str_starts_with($ofClass2->id, 'class@anonymous'));
        static::assertNotSame($ofClass1->id, $ofClass2->id);
    }
}
