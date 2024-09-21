<?php

declare(strict_types=1);

namespace RologIo\Utils;

/**
 * @template T of Option::TYPE_*
 *
 * @internal Might not fulfil the backward compatibility promise of SEMVER.
 */
final class Option
{
    private const string TYPE_PRIORITY = 'priority';
    private const string TYPE_BEFORE = 'before';
    private const string TYPE_AFTER = 'after';
    private const string TYPE_UNDEFINED = 'undefined';

    /** @var array<string, Option|array<int|non-empty-string, Option>>  */
    private static array $instances = [];

    /**
     * @return Option<Option::TYPE_UNDEFINED>
     */
    public static function UNDEFINED(): Option
    {
        static::$instances[self::TYPE_UNDEFINED] ??= new self(self::TYPE_UNDEFINED);
        /** @var Option<Option::TYPE_UNDEFINED> */
        return static::$instances[self::TYPE_UNDEFINED];
    }

    /**
     * @param int $priority
     * @return Option<Option::TYPE_PRIORITY>
     *
     * @psalm-suppress MixedInferredReturnType
     */
    public static function PRIORITY(int $priority): Option
    {
        static::$instances[self::TYPE_PRIORITY] ??= [];
        /** @psalm-suppress UndefinedMethod */
        static::$instances[self::TYPE_PRIORITY][$priority] ??= new self(
            self::TYPE_PRIORITY,
            priority: $priority
        );
        /** @psalm-suppress MixedReturnStatement, UndefinedMethod */
        return static::$instances[self::TYPE_PRIORITY][$priority];
    }

    /**
     * @param non-empty-string $id
     * @return Option<Option::TYPE_BEFORE>
     *
     * @psalm-suppress MixedInferredReturnType
     */
    public static function BEFORE(string $id): Option
    {
        static::$instances[self::TYPE_BEFORE] ??= [];
        /** @psalm-suppress UndefinedMethod */
        static::$instances[self::TYPE_BEFORE][$id] ??= new self(self::TYPE_BEFORE, before: $id);
        /** @psalm-suppress MixedReturnStatement, UndefinedMethod */
        return static::$instances[self::TYPE_BEFORE][$id];
    }

    /**
     * @param non-empty-string $id
     * @return Option<Option::TYPE_AFTER>
     *
     * @psalm-suppress MixedInferredReturnType
     */
    public static function AFTER(string $id): Option
    {
        static::$instances[self::TYPE_AFTER] ??= [];
        /** @psalm-suppress UndefinedMethod */
        static::$instances[self::TYPE_AFTER][$id] ??= new self(self::TYPE_AFTER, after: $id);
        /** @psalm-suppress MixedReturnStatement, UndefinedMethod */
        return static::$instances[self::TYPE_AFTER][$id];
    }

    /**
     * @param T $type
     * @param int|null $priority
     * @param non-empty-string|null $before
     * @param non-empty-string|null $after
     */
    private function __construct(
        private readonly string $type,
        public readonly ?int $priority = null,
        public readonly ?string $before = null,
        public readonly ?string $after = null
    ) {

        $this->assertNonEmptyString($before, 'BEFORE');
        $this->assertNonEmptyString($after, 'AFTER');
    }

    /**
     * @return bool
     *
     * @psalm-assert-if-true Option<Option::TYPE_PRIORITY> $this
     * @psalm-assert-if-true int $this->PRIORITY
     * @psalm-assert-if-true null $this->BEFORE
     * @psalm-assert-if-true null $this->AFTER
     * @psalm-assert-if-false null $this->PRIORITY
     */
    public function isPriority(): bool
    {
        return $this->type === self::TYPE_PRIORITY;
    }

    /**
     * @return bool
     *
     * @psalm-assert-if-true Option<Option::TYPE_BEFORE> $this
     * @psalm-assert-if-true string $this->BEFORE
     * @psalm-assert-if-true null $this->PRIORITY
     * @psalm-assert-if-true null $this->AFTER
     * @psalm-assert-if-false null $this->BEFORE
     */
    public function isBefore(): bool
    {
        return $this->type === self::TYPE_BEFORE;
    }

    /**
     * @return bool
     *
     * @psalm-assert-if-true Option<Option::TYPE_AFTER> $this
     * @psalm-assert-if-true string $this->AFTER
     * @psalm-assert-if-true null $this->PRIORITY
     * @psalm-assert-if-true null $this->BEFORE
     * @psalm-assert-if-false null $this->AFTER
     */
    public function isAfter(): bool
    {
        return $this->type === self::TYPE_AFTER;
    }

    /**
     * @return bool
     *
     * @psalm-assert-if-true Option<Option::TYPE_UNDEFINED> $this
     * @psalm-assert-if-true null $this->PRIORITY
     * @psalm-assert-if-true null $this->BEFORE
     * @psalm-assert-if-true null $this->AFTER
     * @psalm-assert-if-false null $this->PRIORITY
     */
    public function isUndefined(): bool
    {
        return $this->type === self::TYPE_UNDEFINED;
    }

    /**
     * @param string|null $string
     * @param "BEFORE"|"AFTER" $key
     * @return void
     *
     * @psalm-assert non-empty-string|null $string
     */
    private function assertNonEmptyString(?string $string, string $key): void
    {
        if ($string === '') {
            throw new \TypeError(
                sprintf(
                    '%s::%s(): Argument #1 ($id) must be of type non-empty-string, '
                    . 'empty string given',
                    __CLASS__,
                    $key,
                )
            );
        }
    }
}
