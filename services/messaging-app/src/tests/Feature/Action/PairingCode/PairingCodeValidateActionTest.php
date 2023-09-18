<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Action\PairingCode;

use Carbon\CarbonImmutable;
use MinVWS\MessagingApp\Tests\Feature\Action\ActionTestCase;

class PairingCodeValidateActionTest extends ActionTestCase
{
    public function testValid(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::instance($this->faker->dateTime));

        $toName = $this->faker->name;
        $code = $this->faker->lexify('??????');

        $message = $this->createMessage([
            'toEmail' => 'foo@bar.com',
            'toName' => $toName,
        ]);
        $pairingCode = $this->createPairingCode([
            'messageUuid' => $message->uuid,
            'code' => $code,
        ]);

        $response = $this->postAuthenticated('/api/v1/pairing-code', [
            'emailAddress' => 'foo@bar.com',
            'pairingCode' => $code,
        ]);

        $expectedResponse = [
            'uuid' => $pairingCode->uuid,
            'messageUuid' => $pairingCode->messageUuid,
            'emailAddress' => 'f****@b****.com',
            'toName' => $toName,
            'validUntil' => $pairingCode->validUntil->format('c'),
        ];

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonDataFromResponse($expectedResponse, $response);
    }

    /**
     * @dataProvider validationDataProvider
     */
    public function testValidation(array $postBody): void
    {
        $response = $this->postAuthenticated('/api/v1/pairing-code', $postBody);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function validationDataProvider(): array
    {
        return [
            'empty body' => [[]],
            'missing email' => [['pairingCode' => 'foo']],
            'null email' => [['emailAddress' => null, 'pairingCode' => 'foo']],
            'empty email' => [['emailAddress' => '', 'pairingCode' => 'foo']],
            'missing pairingCode' => [['emailAddress' => 'foo@bar.com']],
            'null pairingCode' => [['emailAddress' => 'foo@bar.com', 'pairingCode' => null]],
            'empty pairingCode' => [['emailAddress' => 'foo@bar.com', 'pairingCode' => '']],
        ];
    }

    public function testNotFound(): void
    {
        $response = $this->postAuthenticated('/api/v1/pairing-code', [
            'emailAddress' => $this->faker->safeEmail,
            'pairingCode' => $this->faker->lexify('??????'),
        ]);

        $this->assertEquals(404, $response->getStatusCode());
    }
}
