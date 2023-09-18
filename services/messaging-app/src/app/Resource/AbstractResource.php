<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Resource;

use Carbon\CarbonInterface;

abstract class AbstractResource
{
    protected function convertDate(?CarbonInterface $date): ?string
    {
        return $date?->format('c');
    }
}
