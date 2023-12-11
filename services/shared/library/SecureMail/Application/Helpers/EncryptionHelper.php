<?php

declare(strict_types=1);

namespace SecureMail\Shared\Application\Helpers;

use SecureMail\Shared\Application\Exceptions\EncryptionException;
use Exception;
use SodiumException;

use function base64_decode;
use function base64_encode;
use function config;
use function json_decode;
use function random_bytes;
use function sodium_crypto_box;
use function sodium_crypto_box_keypair_from_secretkey_and_publickey;
use function sodium_crypto_box_open;
use function str_starts_with;
use function substr;

use const SODIUM_CRYPTO_BOX_NONCEBYTES;

class EncryptionHelper
{
    /**
     * @throws EncryptionException
     */
    public static function encrypt(string $privateKey, string $publicKey, mixed $data): string
    {
        try {
            $nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
            $encryptionKey = sodium_crypto_box_keypair_from_secretkey_and_publickey($privateKey, $publicKey);

            $encodedData = json_encode($data);
            if (!$encodedData) {
                throw new EncryptionException('Error encoding encrypted data');
            }
            $encrypted = sodium_crypto_box($encodedData, $nonce, $encryptionKey);
            return base64_encode($nonce . $encrypted);
        } catch (Exception $exception) {
            throw EncryptionException::fromThrowable($exception);
        }
    }
    
    /**
     * @return mixed
     *
     * @throws EncryptionException
     */
    public static function decrypt(string $privateKey, string $publicKey, string $data)
    {
        try {
            $data = base64_decode($data);
            $nonce = substr($data, 0, SODIUM_CRYPTO_BOX_NONCEBYTES);
            $encryptedData = substr($data, SODIUM_CRYPTO_BOX_NONCEBYTES);
            $decryptionKey = sodium_crypto_box_keypair_from_secretkey_and_publickey($privateKey, $publicKey);

            $result = sodium_crypto_box_open($encryptedData, $nonce, $decryptionKey);
            if ($result === false) {
                throw new EncryptionException('Failed to unseal data');
            }

            return json_decode($result);
        } catch (Exception $exception) {
            throw EncryptionException::fromThrowable($exception);
        }
    }


    /**
     * Used for the Mittens mock(LocalPseudoBsnRepository) login only!
     *
     * @param string $value
     *
     * @return string
     *
     * @throws EncryptionException
     */
    public static function encryptMittensIdentityData(string $value): string
    {
        try {
            $mittensPublicKey = config('services.mittens.mock.encryption.public_key');
            $mittensPrivateKey = config('services.mittens.mock.encryption.private_key');
            if (str_starts_with($mittensPublicKey, 'base64:')) {
                $mittensPublicKey = substr($mittensPublicKey, 7);
            }
            if (str_starts_with($mittensPrivateKey, 'base64:')) {
                $mittensPrivateKey = substr($mittensPrivateKey, 7);
            }
            $nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
            $encryptionKey = sodium_crypto_box_keypair_from_secretkey_and_publickey(base64_decode($mittensPrivateKey), base64_decode($mittensPublicKey));
            $encrypted = sodium_crypto_box($value, $nonce, $encryptionKey);

            return base64_encode($nonce . $encrypted);
        } catch (SodiumException $sodiumException) {
            throw EncryptionException::fromThrowable($sodiumException);
        }
    }
}
