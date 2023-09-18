<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Action\PairingCode;

use Carbon\CarbonImmutable;
use MinVWS\MessagingApp\Queue\QueueClient;
use MinVWS\MessagingApp\Queue\Task\DTO\Notification;
use MinVWS\MessagingApp\Tests\Feature\Action\ActionTestCase;

class PairingCodeRenewActionTest extends ActionTestCase
{
    public function testPairingCodeRenew(): void
    {
        $pairingCodeUuid = $this->faker->uuid;

        $pairingCode = $this->createPairingCode([
            'uuid' => $pairingCodeUuid,
            'validUntil' => CarbonImmutable::yesterday(),
        ]);

        $this->mock(QueueClient::class)
            ->expects($this->once())
            ->method('pushTask')
            ->with('notification', new Notification($pairingCode->messageUuid, $pairingCode->aliasUuid));

        $response = $this->postAuthenticated('/api/v1/pairing-code/renew', [
            'pairingCodeUuid' => $pairingCodeUuid,
        ]);

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testPairingCodeRenewNotYetExpired(): void
    {
        $pairingCodeUuid = $this->faker->uuid;

        $this->createPairingCode([
            'uuid' => $pairingCodeUuid,
            'validUntil' => CarbonImmutable::tomorrow(),
        ]);

        $this->mock(QueueClient::class)
            ->expects($this->never())
            ->method('pushTask');

        $response = $this->postAuthenticated('/api/v1/pairing-code/renew', [
            'pairingCodeUuid' => $pairingCodeUuid,
        ]);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPairingCodeRenewNotFound(): void
    {
        $response = $this->postAuthenticated('/api/v1/pairing-code/renew', [
            'pairingCodeUuid' => 'notfound',
        ]);

        $this->assertEquals(404, $response->getStatusCode());
    }
}
