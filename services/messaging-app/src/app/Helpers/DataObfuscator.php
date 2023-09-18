<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Helpers;

use function explode;
use function sprintf;
use function substr;

class DataObfuscator
{
    public static function obfuscateEmailAddress(string $emailAddress): string
    {
        list($local, $domain) = explode('@', $emailAddress);
        list($domainName, $extension) = explode('.', $domain);

        return sprintf('%s****@%s****.%s', substr($local, 0, 1), substr($domainName, 0, 1), $extension);
    }

    public static function obfuscatePhoneNumber(string $phoneNumber): string
    {
        return sprintf('*******%s', substr($phoneNumber, -3, 3));
    }
}
