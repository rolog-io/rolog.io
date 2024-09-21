<?php

declare(strict_types=1);

namespace RologIo\Tests\Utils;

use RologIo\Tests\TestCase;
use RologIo\Utils\Option;

class OptionTest extends TestCase
{
    /**
     * @test
     */
    public function testBeforeCanNotBeAnEmptyString(): void
    {
        $this->expectExceptionMessageMatches('/before.+?empty/i');

        Option::BEFORE('');
    }

    /**
     * @test
     */
    public function testAfterCanNotBeAnEmptyString(): void
    {
        $this->expectExceptionMessageMatches('/after.+?empty/i');

        Option::AFTER('');
    }
}
