<?php

declare(strict_types=1);

namespace RologIo;

use Psr\EventDispatcher\ListenerProviderInterface;
use RologIo\Listener\DelegateListener;
use RologIo\Listener\IdentifiableListener;
use RologIo\Listener\OptionAwareListener;
use RologIo\Utils\CallbackId;
use RologIo\Utils\Option;
use RologIo\Utils\Reflector;

final class ListenerProvider implements ListenerProviderInterface
{
    private const string BEFORE = 'before';
    private const string AFTER = 'after';

    /** @var \SplMinHeap<list{int,callable}> */
    private readonly \SplMinHeap $queue;

    private int $nextPriority = 0;

    /**
     * @var \SplObjectStorage<Option, \SplQueue<callable>>
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private readonly \SplObjectStorage $relatedProviders;

    /**
     * @return ListenerProvider
     */
    public static function new(): ListenerProvider
    {
        return new self();
    }

    /**
     */
    private function __construct()
    {
        /** @psalm-suppress MixedPropertyTypeCoercion */
        $this->queue = new \SplMinHeap();
    }

    /**
     * @param callable $listener
     * @return static
     */
    public function addSubscriber(object $subscriber): static
    {
        foreach (Reflector::readSubscriberListeners($subscriber) as [$callback, $attribute]) {
            $this->doAddListener($callback, $attribute);
        }

        return $this;
    }

    /**
     * @param callable $listener
     * @param callable ...$listeners
     * @return static
     */
    public function addListeners(callable $listener, callable ...$listeners): static
    {
        $this->doAddListener($listener, Reflector::readCallbackAttribute($listener));
        foreach ($listeners as $listener) {
            $this->doAddListener($listener, Reflector::readCallbackAttribute($listener));
        }

        return $this;
    }

    /**
     * @param object $event
     * @return array<callable(object)>|\Traversable<callable(object)>
     *
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors
     */
    public function getListenersForEvent(object $event): iterable
    {
        // phpcs:enable Inpsyde.CodeQuality.NoAccessors
        $queue = clone $this->queue;

        yield from $this->yieldFromQueue($queue, $event);
    }

    /**
     * @param callable $listener
     * @param null|Listener $attribute
     * @return void
     */
    private function doAddListener(callable $listener, ?Listener $attribute): void
    {
        $option = $this->findOption($listener, $attribute);

        if (!$option) {
            $this->addListenerByPriority($listener, null);

            return;
        }

        /** @psalm-suppress TypeDoesNotContainType */

        match (true) {
            $option->isBefore(),
            $option->isAfter() => $this->addListenerByOption($listener, $option),
            $option->isPriority(),
            $option->isUndefined() => $this->addListenerByPriority($listener, $option->priority),
        };
    }

    /**
     * @param callable $listener
     * @param int|null $priority
     * @return void
     */
    private function addListenerByPriority(callable $listener, ?int $priority): void
    {
        $this->queue->insert([$priority ?? $this->nextPriority, $listener]);
        $this->nextPriority++;
        if (($priority !== null) && ($priority >= $this->nextPriority)) {
            $this->nextPriority = $priority + 1;
        }
    }

    /**
     * @param callable $listener
     * @param Option $option
     * @return void
     */
    private function addListenerByOption(callable $listener, Option $option): void
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->relatedProviders)) {
            /** @psalm-suppress InaccessibleProperty */
            $this->relatedProviders = new \SplObjectStorage();
        }

        if (!$this->relatedProviders->contains($option)) {
            $queue = new \SplQueue();
            /** @psalm-suppress MixedArgumentTypeCoercion */
            $this->relatedProviders->attach($option, $queue);
        }

        $this->relatedProviders[$option]->enqueue($listener);
    }

    /**
     * @param \SplQueue<callable>|\SplMinHeap<list{int,callable}> $queue
     * @param object $event
     * @return \Generator<callable(object)>
     */
    private function yieldFromQueue(\SplQueue|\SplMinHeap $queue, object $event): \Generator
    {
        $queue->rewind();

        while ($queue->valid()) {
            $current = $queue->current();

            /**
             * @var callable(object) $listener
             * @psalm-suppress PossiblyInvalidArrayAccess
             */
            $listener = ($queue instanceof \SplMinHeap) ? $current[1] : $current;
            $queue->next();

            if (Reflector::readCallbackType($listener)->satisfiedBy($event)) {
                yield from $this->searchRelative($listener, self::BEFORE, $event);
                yield $listener;
                yield from $this->searchRelative($listener, self::AFTER, $event);
            }
        }
    }

    /**
     * @param callable $callback
     * @param string $type
     * @param object $event
     * @return \Generator<callable(object)>
     */
    private function searchRelative(callable $callback, string $type, object $event): \Generator
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->relatedProviders)) {
            return;
        }

        $id = $this->findId($callback);

        $option = ($type === self::BEFORE) ? Option::BEFORE($id) : Option::AFTER($id);
        if ($this->relatedProviders->contains($option)) {
            $queue = $this->relatedProviders[$option];
            yield from $this->yieldFromQueue($queue, $event);
        }
    }

    /**
     * @param callable $callback
     * @param Listener|null $attribute
     * @return Option|null
     */
    private function findOption(callable $callback, ?Listener $attribute): ?Option
    {
        if ($callback instanceof OptionAwareListener) {
            return $callback->option();
        }

        if ($attribute) {
            return Listener::readOption($attribute);
        }

        if (!($callback instanceof DelegateListener)) {
            return null;
        }

        $callback = $callback->delegated();

        return $this->findOption($callback, Reflector::readCallbackAttribute($callback));
    }

    /**
     * @param callable $callback
     * @return non-empty-string
     */
    private function findId(callable $callback): string
    {
        if ($callback instanceof IdentifiableListener) {
            return $callback->id();
        }

        $id = Reflector::readCallbackAttribute($callback)?->id;
        if ($id !== null) {
            return $id;
        }

        return ($callback instanceof DelegateListener)
            ? $this->findId($callback->delegated())
            : CallbackId::of($callback)->id;
    }
}
