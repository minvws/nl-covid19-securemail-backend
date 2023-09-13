<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Middleware;

use function array_key_exists;
use function array_values;
use function count;
use function explode;
use function in_array;

class JwtSecretsHelper
{
    /**
     * @throws JwtSecretsException
     */
    public static function getSecretsFromString(
        string $input,
        string $secretSeparator,
        string $platformSeparator,
    ): array {
        $resultSet = [];
        $platformSecretPairs = explode($platformSeparator, $input);

        foreach ($platformSecretPairs as $platformSecretPair) {
            $pair = explode($secretSeparator, $platformSecretPair);

            if (count($pair) !== 2) {
                throw new JwtSecretsException('invalid platform/secret pair');
            }

            $jwtPlatformIdentifier = $pair[0];
            $jwtSecret = $pair[1];

            if (array_key_exists($jwtPlatformIdentifier, $resultSet)) {
                throw new JwtSecretsException('non unique platform identifier found');
            }

            if (in_array($jwtSecret, array_values($resultSet))) {
                throw new JwtSecretsException('non unique secret found');
            }

            $resultSet[$jwtPlatformIdentifier] = $jwtSecret;
        }

        return $resultSet;
    }
}
