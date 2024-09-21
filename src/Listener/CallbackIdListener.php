<?php

declare(strict_types=1);

namespace RologIo\Listener;

final class CallbackIdListener implements IdentifiableListener, DelegateListener
{
    /** @var callable */
    private $listener;

    /**
     * @param non-empty-string $id
     * @param callable $listener
     * @return CallbackIdListener
     */
    public static function new(string $id, callable $listener): CallbackIdListener
    {
        return new self($id, $listener);
    }

    /**
     * @param non-empty-string $id
     * @param callable $listener
     */
    private function __construct(
        private readonly string $id,
        callable $listener
    ) {

        $this->listener = $listener;
    }

    /**
     * @return non-empty-string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @param object $event
     * @return void
     */
    public function __invoke(object $event): void
    {
        ($this->listener)($event);
    }

    /**
     * @return callable
     */
    public function delegated(): callable
    {
        return $this->listener;
    }
}
