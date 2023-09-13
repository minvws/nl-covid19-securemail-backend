<?php

declare(strict_types=1);

namespace App\Helpers;

use Exception;

use function base64_encode;
use function hash;
use function random_int;
use function rtrim;
use function strlen;
use function strtr;

class OidcHelper
{
    protected const RANDOM_STRING_PERMITTED_CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public static function getCodeChallenge(string $codeVerifier): string
    {
        $hashed = hash('sha256', $codeVerifier, true);

        return static::base64UrlEncode($hashed);
    }

    /**
     * @throws Exception
     */
    public static function getCodeVerifier(): string
    {
        return static::base64UrlEncode(static::generateString());
    }

    public static function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    /**
     * @throws Exception
     */
    protected static function generateString(int $strength = 32): string
    {
        $input_length = strlen(self::RANDOM_STRING_PERMITTED_CHARS);

        $random_string = '';
        for ($i = 0; $i < $strength; $i++) {
            $random_string .= self::RANDOM_STRING_PERMITTED_CHARS[random_int(0, $input_length - 1)];
        }

        return $random_string;
    }
}
