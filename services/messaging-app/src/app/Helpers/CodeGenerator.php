<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Helpers;

use Exception;

use function implode;
use function mb_strlen;
use function random_int;

class CodeGenerator
{
    /**
     * @throws Exception
     */
    public function generate(string $allowedCharachters, int $length): string
    {
        $chars = [];

        $max = mb_strlen($allowedCharachters, '8bit') - 1;
        for ($i = 0; $i < $length; $i++) {
            $chars [] = $allowedCharachters[random_int(0, $max)];
        }

        return implode('', $chars);
    }
}
