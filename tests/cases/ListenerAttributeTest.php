<?php

declare(strict_types=1);

namespace RologIo\Tests;

use RologIo\Listener;

class ListenerAttributeTest extends TestCase
{
    /**
     * @test
     */
    public function testPriorityCanNotBeUsedWithBefore(): void
    {
        $this->expectExceptionMessageMatches('/one of/i');

        new Listener(priority: 2, before: 'x');
    }

    /**
     * @test
     */
    public function testPriorityCanNotBeUsedWithAfter(): void
    {
        $this->expectExceptionMessageMatches('/one of/i');

        new Listener(priority: 2, after: 'x');
    }

    /**
     * @test
     */
    public function testBeforeCanNotBeUsedWithAfter(): void
    {
        $this->expectExceptionMessageMatches('/one of/i');

        new Listener(before: 'y', after: 'x');
    }

    /**
     * @test
     */
    public function testBeforeCanNotBeAnEmptyString(): void
    {
        $this->expectExceptionMessageMatches('/before.+?empty/i');

        new Listener(before: '');
    }

    /**
     * @test
     */
    public function testAfterCanNotBeAnEmptyString(): void
    {
        $this->expectExceptionMessageMatches('/after.+?empty/i');

        new Listener(after: '');
    }

    /**
     * @test
     */
    public function testIdCanNotBeAnEmptyString(): void
    {
        $this->expectExceptionMessageMatches('/id.+?empty/i');

        new Listener(id: '');
    }

    /**
     * @test
     */
    public function testReadOptionFRomEmpty(): void
    {
        static::assertTrue(Listener::readOption(new Listener(id: 'x'))->isUndefined());
        static::assertTrue(Listener::readOption(new Listener())->isUndefined());
    }
}
