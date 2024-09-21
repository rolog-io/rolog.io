<?php

declare(strict_types=1);

namespace RologIo\Listener;

use RologIo\Utils\Option;

interface OptionAwareListener extends Listener
{
    public function option(): Option;
}
