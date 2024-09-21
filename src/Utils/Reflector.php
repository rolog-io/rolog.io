<?php

declare(strict_types=1);

namespace RologIo\Utils;

use RologIo\Listener;
use Toobo\TypeChecker\Type;

/**
 * @internal Might not fulfil the backward compatibility promise of SEMVER.
 */
final class Reflector
{
    /** @var array<string, \ReflectionFunctionAbstract|\ReflectionClass> */
    private static array $reflections = [];

    /** @var null|\SplObjectStorage<\Reflector, Type>  */
    private static ?\SplObjectStorage $callbackTypes = null;

    /** @var null|\SplObjectStorage<\Reflector, Listener>  */
    private static ?\SplObjectStorage $callbackAttributes = null;

    /**
     * @param callable $callback
     * @return Type
     */
    public static function readCallbackType(callable $callback): Type
    {
        $reflection = static::reflectListener($callback, forceFunction: true);
        static::$callbackTypes ??= new \SplObjectStorage();

        /** @psalm-suppress InvalidArgument */
        if (!static::$callbackTypes->contains($reflection)) {
            $parameter = $reflection->getParameters()[0] ?? null;
            $parameterType = $parameter?->getType() ?? null;
            /** @var Type $type */
            $type = $parameterType ? Type::byReflectionType($parameterType) : Type::mixed();
            static::$callbackTypes->attach($reflection, $type);
        }
        /** @psalm-suppress InvalidArgument */
        return static::$callbackTypes[$reflection];
    }

    /**
     * @param callable $callback
     * @return Listener|null
     */
    public static function readCallbackAttribute(callable $callback): ?Listener
    {
        $reflection = static::reflectListener($callback, forceFunction: false);
        static::$callbackAttributes ??= new \SplObjectStorage();
        /** @psalm-suppress InvalidArgument */
        if (static::$callbackAttributes->contains($reflection)) {
            /** @psalm-suppress InvalidArgument */
            return static::$callbackAttributes[$reflection];
        }

        $refAttribute = $reflection->getAttributes(Listener::class)[0] ?? null;

        if ($refAttribute) {
            $attribute = $refAttribute->newInstance();
            /** @psalm-suppress InvalidArgument */
            static::$callbackAttributes[$reflection] = $attribute;

            return $attribute;
        }

        if ($reflection instanceof \ReflectionClass) {
            $innerReflection = static::reflectListener($callback, forceFunction: true);
            $refAttribute = $innerReflection->getAttributes(Listener::class)[0] ?? null;
            if ($refAttribute) {
                $attribute = $refAttribute->newInstance();
                /** @psalm-suppress InvalidArgument */
                static::$callbackAttributes[$reflection] = $attribute;
                /** @psalm-suppress InvalidArgument */
                static::$callbackAttributes[$innerReflection] = $attribute;

                return $attribute;
            }
        }

        if ($callback instanceof Listener\DelegateListener) {
            return static::readCallbackAttribute($callback->delegated());
        }

        return null;
    }

    /**
     * @param object $subscriber
     * @return list<list{callable, Listener}>
     */
    public static function readSubscriberListeners(object $subscriber): array
    {
        $ref = static::reflectClass($subscriber);
        $listeners = [];
        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $attribute = $method->getAttributes(Listener::class)[0] ?? null;
            if ($attribute === null) {
                continue;
            }

            $class = $method->isStatic() ? $ref->getName() : $subscriber;
            $listeners[] = [[$class, $method->getName()], $attribute->newInstance()];
        }

        return $listeners;
    }

    /**
     * @param object $callback
     * @return \ReflectionClass
     */
    public static function reflectClass(object $object): \ReflectionClass
    {
        $class = get_class($object);
        static::$reflections[$class] ??= new \ReflectionClass($class);
        /** @var \ReflectionClass */
        return static::$reflections[$class];
    }

    /**
     * @param callable $callback
     * @param bool $forceFunction
     * @return ($forceFunction is true
     *  ? \ReflectionFunctionAbstract
     *  : \ReflectionFunctionAbstract|\ReflectionClass)
     *
     * phpcs:disable Generic.Metrics.CyclomaticComplexity
     */
    private static function reflectListener(
        callable $callback,
        bool $forceFunction
    ): \ReflectionFunctionAbstract|\ReflectionClass {
        // phpcs:enable Generic.Metrics.CyclomaticComplexity

        if ($callback instanceof \Closure) {
            $ref = new \ReflectionFunction($callback);
            $hash = spl_object_hash($callback);
            $id = sprintf('Closure#%s:%s:%s', $ref->getFileName(), $ref->getEndLine(), $hash);
            static::$reflections[$id] ??= $ref;

            return static::$reflections[$id];
        }

        if (is_object($callback)) {
            if (!$forceFunction) {
                /** @var object $callback */
                $class = get_class($callback);
                static::$reflections[$class] ??= new \ReflectionClass($callback);

                return static::$reflections[$class];
            }
            $callback = [$callback, '__invoke'];
        } elseif (is_string($callback)) {
            if (!str_contains($callback, '::')) {
                $id = "$callback()";
                static::$reflections[$id] ??= new \ReflectionFunction($callback);

                return static::$reflections[$id];
            }

            $callback = explode('::', $callback);
        }

        /** @var list{class-string|object, string} $callback */

        $class = is_object($callback[0]) ? get_class($callback[0]) : $callback[0];
        $id = "{$class}::{$callback[1]}";

        /** @psalm-suppress PossiblyInvalidArgument */
        static::$reflections[$id] ??= new \ReflectionMethod(...$callback);

        return static::$reflections[$id];
    }
}
