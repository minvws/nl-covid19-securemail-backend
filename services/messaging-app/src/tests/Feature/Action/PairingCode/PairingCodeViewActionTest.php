<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Action\PairingCode;

use Carbon\CarbonImmutable;
use MinVWS\MessagingApp\Tests\Feature\Action\ActionTestCase;

use function sprintf;

class PairingCodeViewActionTest extends ActionTestCase
{
    public function testValid(): void
    {
        $message = $this->createMessage([
            'toEmail' => 'foo@bar.com',
            'expiresAt' => CarbonImmutable::tomorrow(),
        ]);
        $pairingCode = $this->createPairingCode([
            'messageUuid' => $message->uuid,
            'validUntil' => CarbonImmutable::tomorrow(),
        ]);

        $response = $this->getAuthenticatedJson(sprintf('/api/v1/pairing-code/%s', $pairingCode->uuid));

        $expectedResponse = [
            'uuid' => $pairingCode->uuid,
            'messageUuid' => $pairingCode->messageUuid,
            'emailAddress' => 'f****@b****.com',
            'toName' => $message->toName,
            'validUntil' => $pairingCode->validUntil->format('c'),
        ];

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonDataFromResponse($expectedResponse, $response);
    }

    public function testNotFound(): void
    {
        $response = $this->getAuthenticatedJson('/api/v1/pairing-code/notfound');

        $this->assertEquals(404, $response->getStatusCode());
    }
}
