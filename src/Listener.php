<?php

declare(strict_types=1);

namespace RologIo;

use RologIo\Utils\Option;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
final readonly class Listener
{
    /**
     * @param Listener $attribute
     * @return Option
     */
    public static function readOption(Listener $attribute): Option
    {
        return match (true) {
            ($attribute->priority !== null) => Option::PRIORITY($attribute->priority),
            ($attribute->before !== null) => Option::BEFORE($attribute->before),
            ($attribute->after !== null) => Option::AFTER($attribute->after),
            default => Option::UNDEFINED(),
        };
    }

    /**
     * @param int|null $priority
     * @param non-empty-string|null $before
     * @param non-empty-string|null $after
     * @param non-empty-string|null $id
     */
    public function __construct(
        public ?int $priority = null,
        public ?string $before = null,
        public ?string $after = null,
        public ?string $id = null,
    ) {

        $found = false;
        foreach ([$priority, $before, $after] as $type) {
            if ($type === null) {
                continue;
            }
            if ($found) {
                throw new \Error(
                    'A listener can only have one of "priority", "before", or "after" option.'
                );
            }
            $found = true;
        }

        $noEmpty = 'A listener %s can not be an empty string.';
        /** @psalm-suppress TypeDoesNotContainType */
        if ($before === '') {
            throw new \Error(sprintf($noEmpty, '"before" option'));
        }
        /** @psalm-suppress TypeDoesNotContainType */
        if ($after === '') {
            throw new \Error(sprintf($noEmpty, '"after" option'));
        }
        /** @psalm-suppress TypeDoesNotContainType */
        if ($id === '') {
            throw new \Error(sprintf($noEmpty, 'ID'));
        }
    }
}
