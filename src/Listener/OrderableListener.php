<?php

declare(strict_types=1);

namespace RologIo\Listener;

use RologIo\Utils\Option;

final class OrderableListener implements DelegateListener, OptionAwareListener
{
    /** @var callable */
    private $listener;

    /**
     * @param callable $listener
     * @param Option $option
     * @return OrderableListener
     */
    public static function new(callable $listener, Option $option): OrderableListener
    {
        return new self($listener, $option);
    }

    /**
     * @param callable $listener
     * @param Option $option
     */
    public function __construct(
        callable $listener,
        private readonly Option $option
    ) {

        $this->listener = $listener;
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

    /**
     * @return Option
     */
    public function option(): Option
    {
        return $this->option;
    }
}
