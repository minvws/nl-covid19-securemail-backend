<?php

namespace SecureMail\Shared\Application\Helpers;

use Monolog\Formatter\LineFormatter;

class LineFormatterFactory
{
    public static function getDefaultFormatter(): LineFormatter
    {
        return new LineFormatter(null, null, false, true);
    }
}
