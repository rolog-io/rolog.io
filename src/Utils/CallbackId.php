<?php

declare(strict_types=1);

namespace RologIo\Utils;

final readonly class CallbackId implements \Stringable
{
    /** @var non-empty-string */
    public string $id; // phpcs:ignore Inpsyde.CodeQuality.ForbiddenPublicProperty

    /**
     * @param object $object
     * @return CallbackId
     */
    public static function of(callable $callback): CallbackId
    {
        return new self($callback);
    }

    /**
     * @param callable $callback
     */
    private function __construct(callable $callback)
    {
        if ($callback instanceof \Closure) {
            $ref = new \ReflectionFunction($callback);
            $file = $ref->getFileName();
            $line = $ref->getEndLine();
            $this->id = sprintf('Closure#%s:%d:%s', $file, $line, spl_object_hash($callback));
            return;
        }

        if (is_string($callback)) {
            $this->id = $callback;
            return;
        }

        if (is_object($callback)) {
            $callback = [$callback, '__invoke'];
        }

        /** @var list{object|class-string,non-empty-string} $callback */
        $class = is_string($callback[0]) ? $callback[0] : get_class($callback[0]);
        $this->id = "{$class}::{$callback[1]}";
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->id;
    }
}
