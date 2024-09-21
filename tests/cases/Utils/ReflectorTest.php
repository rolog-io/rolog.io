<?php

declare(strict_types=1);

namespace RologIo\Tests\Utils;

use RologIo\Listener;
use RologIo\Tests\TestCase;
use RologIo\Utils\Reflector;
use Toobo\TypeChecker\Type;

/**
 * @runTestsInSeparateProcesses
 */
class ReflectorTest extends TestCase
{
    /**
     * @return void
     */
    public function testReflectionCallbackTypesAreCached(): void
    {
        $exec = new class ()
        {
            public function __invoke(\ArrayObject $object)
            {
            }
        };

        $attr1 = Reflector::readCallbackType($exec);
        $attr2 = Reflector::readCallbackType($exec);

        $attr3 = Reflector::readCallbackType('strtolower');
        $attr4 = Reflector::readCallbackType('strtolower');

        $attr5 = Reflector::readCallbackType(__CLASS__ . '::throwException');
        $attr6 = Reflector::readCallbackType(__CLASS__ . '::throwException');
        $attr7 = Reflector::readCallbackType([__CLASS__, 'throwException']);
        $attr8 = Reflector::readCallbackType([__CLASS__, 'throwException']);

        static::assertTrue($attr1 instanceof Type);
        static::assertSame(\ArrayObject::class, (string) $attr1);
        static::assertSame($attr1, $attr2);

        static::assertTrue($attr3 instanceof Type);
        static::assertSame('string', (string) $attr3);
        static::assertSame($attr3, $attr4);

        static::assertTrue($attr5 instanceof Type);
        static::assertSame(\Throwable::class, (string) $attr5);
        static::assertSame($attr5, $attr6);
        static::assertSame($attr5, $attr7);
        static::assertSame($attr5, $attr8);
    }

    /**
     * @return void
     */
    public function testReflectionCallbackAttributesAreCached(): void
    {
        $exec = new class ()
        {
            #[Listener(id:'myID')]
            public function __invoke()
            {
            }
        };

        $attr1 = Reflector::readCallbackAttribute($exec);
        $attr2 = Reflector::readCallbackAttribute($exec);

        static::assertTrue($attr1 instanceof Listener);
        static::assertSame($attr1, $attr2);
    }

    /**
     * @return void
     */
    public function testReflectionCallbackForExecutableClass(): void
    {
        $exec1 = new class ()
        {
            #[Listener(id:'myID')]
            public function __invoke(object $object): void
            {
            }
        };

        static::assertSame('myID', Reflector::readCallbackAttribute($exec1)?->id);
    }

    /**
     * @return void
     */
    public function testReflectionClassIsCached(): void
    {
        $ref1 = Reflector::reflectClass($this);
        $ref2 = Reflector::reflectClass($this);

        static::assertSame($ref1, $ref2);
    }
}
