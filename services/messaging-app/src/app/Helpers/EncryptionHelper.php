<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Helpers;

use Exception;

use function base64_encode;
use function json_encode;
use function random_bytes;
use function sodium_crypto_box;
use function sodium_crypto_box_keypair_from_secretkey_and_publickey;

use const SODIUM_CRYPTO_BOX_NONCEBYTES;

class EncryptionHelper
{
    /**
     * @throws EncryptionException
     */
    public static function encrypt(string $privateKey, string $publicKey, string $data): string
    {
        try {
            $nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
            $encryptionKey = sodium_crypto_box_keypair_from_secretkey_and_publickey($privateKey, $publicKey);

            $encrypted = sodium_crypto_box(json_encode($data), $nonce, $encryptionKey);
            return base64_encode($nonce . $encrypted);
        } catch (Exception $exception) {
            throw EncryptionException::fromThrowable($exception);
        }
    }
}
