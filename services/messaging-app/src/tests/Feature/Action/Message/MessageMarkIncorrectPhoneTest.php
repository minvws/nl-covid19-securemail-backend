<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Action\Message;

use Carbon\CarbonImmutable;
use MinVWS\MessagingApp\Tests\Feature\Action\ActionTestCase;

class MessageMarkIncorrectPhoneTest extends ActionTestCase
{
    public function testMarkIncorrectPhone(): void
    {
        $testNow = CarbonImmutable::instance($this->faker->dateTime);
        CarbonImmutable::setTestNow($testNow);
        $messageUuid = $this->faker->uuid;

        $this->createMessage([
            'uuid' => $messageUuid,
            'otpIncorrectPhone' => null,
        ]);

        $response = $this->postAuthenticated('/api/v1/messages/incorrect-phone', [
            'messageUuid' => $messageUuid,
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas('message', [
            'uuid' => $messageUuid,
            'otp_incorrect_phone_at' => $testNow->format('y-m-d H:i:s'),
        ]);
    }

    public function testMarkIncorrectPhoneMessageNotFound(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::instance($this->faker->dateTime));
        $messageUuid = $this->faker->uuid;

        $response = $this->postAuthenticated('/api/v1/messages/incorrect-phone', [
            'messageUuid' => $messageUuid,
        ]);

        $this->assertEquals(404, $response->getStatusCode());
    }
}
