<?php

declare(strict_types=1);

namespace App\Resources;

use Carbon\CarbonInterface;

abstract class Resource
{
    protected function formatDate(CarbonInterface $date): string
    {
        return $date->format('c');
    }
}
