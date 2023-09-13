<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Service;

use Carbon\CarbonImmutable;
use MinVWS\MessagingApp\Helpers\CodeGenerator;
use MinVWS\MessagingApp\Queue\QueueClient;
use MinVWS\MessagingApp\Repository\PairingCodeRepository;
use MinVWS\MessagingApp\Service\PairingCodeService;
use MinVWS\MessagingApp\Tests\Feature\FeatureTestCase;
use Psr\Log\NullLogger;

use function base64_decode;
use function json_decode;
use function sodium_crypto_box_keypair;
use function sodium_crypto_box_keypair_from_secretkey_and_publickey;
use function sodium_crypto_box_open;
use function sodium_crypto_box_publickey;
use function sodium_crypto_box_secretkey;
use function sprintf;
use function str_replace;
use function substr;
use function urldecode;

use const SODIUM_CRYPTO_BOX_NONCEBYTES;

class PairingCodeServiceTest extends FeatureTestCase
{
    /**
     * @dataProvider generateForMessageDataProvider
     */
    public function testGenerateForMessage(int $tokenLifetimeInHours, string $expectedDate): void
    {
        CarbonImmutable::setTestNow('2020-01-01');

        $pairingCodeService = new PairingCodeService(
            $this->getContainer()->get(CodeGenerator::class),
            new NullLogger(),
            $this->getContainer()->get(PairingCodeRepository::class),
            $this->getContainer()->get(QueueClient::class),
            $this->faker->lexify('??????'),
            $this->faker->randomDigit(),
            $tokenLifetimeInHours,
            $this->faker->url,
            $this->faker->word,
            $this->faker->word,
        );

        $message = $this->createMessage();
        $result = $pairingCodeService->generateForMessage($message);

        $this->assertEquals($expectedDate, $result->validUntil->format('c'));
    }

    public function generateForMessageDataProvider(): array
    {
        return [
            '24 hours' => [24, '2020-01-02T00:00:00+00:00'],
            '48 hours' => [48, '2020-01-03T00:00:00+00:00'],
        ];
    }

    public function testGenerateMessageboxUrl(): void
    {
        $pairingCodeUuid = $this->faker->uuid;
        $pairingCode = $this->createPairingCode(['uuid' => $pairingCodeUuid]);

        $messagingAppKeypair = sodium_crypto_box_keypair();
        $messagingAppPrivateKey = sodium_crypto_box_secretkey($messagingAppKeypair);
        $messagingAppPublicKey = sodium_crypto_box_publickey($messagingAppKeypair);

        $messageBoxKeypair = sodium_crypto_box_keypair();
        $messageBoxPrivateKey = sodium_crypto_box_secretkey($messageBoxKeypair);
        $messageBoxPublicKey = sodium_crypto_box_publickey($messageBoxKeypair);

        $domain = $this->faker->url;
        $pairingCodeService = new PairingCodeService(
            $this->getContainer()->get(CodeGenerator::class),
            new NullLogger(),
            $this->getContainer()->get(PairingCodeRepository::class),
            $this->getContainer()->get(QueueClient::class),
            $this->faker->lexify('??????'),
            $this->faker->randomDigit(),
            $this->faker->randomDigit(),
            $domain,
            $messagingAppPrivateKey,
            $messageBoxPublicKey,
        );

        $messageBoxUrl = $pairingCodeService->generateMessageboxUrl($pairingCode);
        $code = str_replace(sprintf('%s/inloggen/code/', $domain), '', $messageBoxUrl);

        // decrypt code
        $data = base64_decode(urldecode(urldecode($code)));
        $nonce = substr($data, 0, SODIUM_CRYPTO_BOX_NONCEBYTES);
        $encryptedData = substr($data, SODIUM_CRYPTO_BOX_NONCEBYTES);
        $decryptionKey = sodium_crypto_box_keypair_from_secretkey_and_publickey(
            $messageBoxPrivateKey,
            $messagingAppPublicKey
        );
        $decryptedPairingCodeUuid = json_decode(sodium_crypto_box_open($encryptedData, $nonce, $decryptionKey));

        $this->assertEquals($pairingCodeUuid, $decryptedPairingCodeUuid);
    }
}
