<?php

declare(strict_types=1);

namespace RologIo\Listener;

interface DelegateListener extends Listener
{
    public function delegated(): callable;
}
