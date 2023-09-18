<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Service\OtpCode\Sms;

use Carbon\Carbon;
use Laminas\Config\Config;
use MinVWS\MessagingApp\Service\OtpCode\Sms\SmsMessage;
use MinVWS\MessagingApp\Service\OtpCode\Sms\SmsSpryngService;
use MinVWS\MessagingApp\Tests\Feature\FeatureTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Spryng\SpryngRestApi\ApiResource;
use Spryng\SpryngRestApi\Http\Response;
use Spryng\SpryngRestApi\Objects\Message;
use Spryng\SpryngRestApi\Objects\MessageCollection;
use Spryng\SpryngRestApi\Resources\MessageClient;
use Spryng\SpryngRestApi\Spryng;

use function json_encode;
use function sprintf;

class SmsSpryngServiceTest extends FeatureTestCase
{
    public function testSend(): void
    {
        $messageUuid = $this->faker->uuid;
        $smsBody = $this->faker->sentence;
        $smsRecipient = $this->faker->phoneNumber;
        $smsSenderName = $this->faker->name;
        $smsSenderReference = $this->faker->uuid;

        $representableSpryngBody = [
            'id' => $messageUuid,
            'encoding' => 'plain',
            'originator' => 'GGD',
            'body' => 'Uw verificatiecode is: 481901',
            'reference' => 'c009951c-ddbf-4d0b-83a9-3690501ff939',
            'credits' => 1.2,
            'scheduled_at' => Carbon::now()->format('c'),
            'canceled_at' => '',
            'created_at' => Carbon::now()->format('c'),
            'updated_at' => Carbon::now()->format('c'),
            'links' => [
                'self' => sprintf('https://rest.spryngsms.com/v1/messages/%s', $messageUuid),
            ],
        ];

        /** @var Response&MockObject $response */
        $response = $this->createMock(Response::class);
        $response->method('getRawBody')
            ->will($this->returnCallback(static function () use ($representableSpryngBody): string {
                return (string) json_encode($representableSpryngBody);
            }));
        $response->method('getResponseCode')
            ->will($this->returnCallback(function (): int {
                return 200;
            }));
        $response->method('toObject')
            ->will($this->returnCallback(
                static function () use ($representableSpryngBody): array|ApiResource|MessageCollection {
                    return ApiResource::deserializeFromRaw(json_encode($representableSpryngBody), Message::class);
                }
            ));

        /** @var MessageClient&MockObject $messageClient */
        $messageClient = $this->createMock(MessageClient::class);
        $messageClient->method('create')
            ->with(self::callback(static function (Message $message) use (
                $smsBody,
                $smsRecipient,
                $smsSenderName,
                $smsSenderReference,
            ): bool {
                // if no route is configured, "business" is the default
                if ($message->route !== 'business') {
                    return false;
                }
                if ($message->body !== $smsBody) {
                    return false;
                }
                if ($message->recipients !== [$smsRecipient]) {
                    return false;
                }
                if ($message->originator !== $smsSenderName) {
                    return false;
                }
                if ($message->reference !== $smsSenderReference) {
                    return false;
                }

                return true;
            }))
            ->will($this->returnCallback(static function () use ($response): Response {
                return $response;
            }));

        /** @var Spryng&MockObject $spryng */
        $spryng = $this->createMock(Spryng::class);
        $spryng->message = $messageClient;

        $smsSpryngService = new SmsSpryngService(
            $this->getContainer()->get(Config::class)->get('sms')->get('spryng'),
            new NullLogger(),
            $spryng
        );

        $smsMessage = new SmsMessage(
            $smsBody,
            $smsRecipient,
            $smsSenderName,
            $smsSenderReference,
        );
        $messageIdentifier = $smsSpryngService->send($smsMessage);

        $this->assertEquals($messageUuid, $messageIdentifier);
    }

    public function testSendWithRouteParameter(): void
    {
        $messageUuid = $this->faker->uuid;
        $configuredSpryngRoute = $this->faker->uuid;

        $this->getConfig()->merge(new Config([
            'sms' => [
                'spryng' => [
                    'route' => $configuredSpryngRoute,
                ],
            ],
        ]));

        $representableSpryngBody = [
            'id' => $messageUuid,
            'encoding' => 'plain',
            'originator' => 'GGD',
            'body' => 'Uw verificatiecode is: 543248',
            'reference' => '875791c0-da0b-4207-b4c8-51b877912c9c',
            'credits' => 1.2,
            'scheduled_at' => Carbon::now()->format('c'),
            'canceled_at' => '',
            'created_at' => Carbon::now()->format('c'),
            'updated_at' => Carbon::now()->format('c'),
            'links' => [
                'self' => sprintf('https://rest.spryngsms.com/v1/messages/%s', $messageUuid),
            ],
        ];

        /** @var Response&MockObject $response */
        $response = $this->createMock(Response::class);
        $response->method('getRawBody')
            ->will($this->returnCallback(static function () use ($representableSpryngBody): string {
                return (string) json_encode($representableSpryngBody);
            }));
        $response->method('getResponseCode')
            ->will($this->returnCallback(function (): int {
                return 200;
            }));
        $response->method('toObject')
            ->will($this->returnCallback(
                static function () use ($representableSpryngBody): array|ApiResource|MessageCollection {
                    return ApiResource::deserializeFromRaw(json_encode($representableSpryngBody), Message::class);
                }
            ));

        /** @var MessageClient&MockObject $messageClient */
        $messageClient = $this->createMock(MessageClient::class);
        $messageClient->method('create')
            ->with(self::callback(static function (Message $message) use ($configuredSpryngRoute): bool {
                if ($message->route !== $configuredSpryngRoute) {
                    return false;
                }

                return true;
            }))
            ->will($this->returnCallback(static function () use ($response): Response {
                return $response;
            }));

        /** @var Spryng&MockObject $spryng */
        $spryng = $this->createMock(Spryng::class);
        $spryng->message = $messageClient;

        $smsSpryngService = new SmsSpryngService(
            $this->getContainer()->get(Config::class)->get('sms')->get('spryng'),
            new NullLogger(),
            $spryng
        );

        $smsMessage = new SmsMessage(
            $this->faker->sentence,
            $this->faker->phoneNumber,
            $this->faker->name,
            $this->faker->uuid,
        );
        $messageIdentifier = $smsSpryngService->send($smsMessage);

        $this->assertEquals($messageUuid, $messageIdentifier);
    }
}
