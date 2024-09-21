<?php

declare(strict_types=1);

namespace RologIo\Listener;

interface IdentifiableListener
{
    /**
     * @return non-empty-string
     */
    public function id(): string;

    /**
     * @param object $event
     * @return void
     */
    public function __invoke(object $event): void;
}
