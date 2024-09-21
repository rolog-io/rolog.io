<?php

declare(strict_types=1);

namespace RologIo\Listener;

interface Listener
{
    public function __invoke(object $event): void;
}
