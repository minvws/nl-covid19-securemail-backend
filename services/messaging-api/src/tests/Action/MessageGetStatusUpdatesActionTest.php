<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Tests\Action;

use Carbon\CarbonImmutable;
use MinVWS\MessagingApi\Repository\MessageReadRepository;
use MinVWS\MessagingApi\Tests\TestHelper\GetMessageFactory;

class MessageGetStatusUpdatesActionTest extends ActionTestCase
{
    public function testGetStatusUpdates(): void
    {
        $since = CarbonImmutable::now()->format('c');
        $limit = 2;

        $message1 = GetMessageFactory::generateModel('message1');
        $message2 = GetMessageFactory::generateModel('message2');

        $messageReadRepository = $this->mock(MessageReadRepository::class);
        $messageReadRepository->method('countStatusUpdates')
            ->with(CarbonImmutable::createFromFormat('c', $since))
            ->willReturn(3);
        $messageReadRepository->method('getStatusUpdates')
            ->with(CarbonImmutable::createFromFormat('c', $since), $limit)
            ->willReturn([
                $message1,
                $message2,
            ]);

        $response = $this->getAuthorized('api/v1/messages/statusupdates', [
            'since' => $since,
            'limit' => $limit,
        ]);
        $expectedResponseBody = [
            'total' => 3,
            'count' => 2,
            'messages' => [
                [
                    'messageUuid' => 'message1',
                    'notificationSentAt' => $this->formatDateOrNull($message1->notificationSentAt),
                    'receivedAt' => $this->formatDateOrNull($message1->receivedAt),
                    'bouncedAt' => $this->formatDateOrNull($message1->bouncedAt),
                    'otpAuthFailedAt' => $this->formatDateOrNull($message1->otpAuthFailedAt),
                    'otpIncorrectPhoneAt' => $this->formatDateOrNull($message1->otpIncorrectPhoneAt),
                    'digidAuthFailedAt' => $this->formatDateOrNull($message1->digidAuthFailedAt),
                    'firstReadAt' => $this->formatDateOrNull($message1->firstReadAt),
                    'revokedAt' => $this->formatDateOrNull($message1->revokedAt),
                    'expiredAt' => $this->formatDateOrNull($message1->expiredAt),
                ],
                [
                    'messageUuid' => 'message2',
                    'notificationSentAt' => $this->formatDateOrNull($message2->notificationSentAt),
                    'receivedAt' => $this->formatDateOrNull($message2->receivedAt),
                    'bouncedAt' => $this->formatDateOrNull($message2->bouncedAt),
                    'otpAuthFailedAt' => $this->formatDateOrNull($message2->otpAuthFailedAt),
                    'otpIncorrectPhoneAt' => $this->formatDateOrNull($message2->otpIncorrectPhoneAt),
                    'digidAuthFailedAt' => $this->formatDateOrNull($message2->digidAuthFailedAt),
                    'firstReadAt' => $this->formatDateOrNull($message2->firstReadAt),
                    'revokedAt' => $this->formatDateOrNull($message2->revokedAt),
                    'expiredAt' => $this->formatDateOrNull($message2->expiredAt),
                ],
            ],
        ];

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonDataFromResponse($expectedResponseBody, $response);
    }

    public function testGetStatusUpdatesWithoutLimit(): void
    {
        $since = CarbonImmutable::now()->format('c');

        $this->mock(MessageReadRepository::class)
            ->method('getStatusUpdates')
            ->with(CarbonImmutable::createFromFormat('c', $since), null)
            ->willReturn([GetMessageFactory::generateModel()]);

        $response = $this->getAuthorized('api/v1/messages/statusupdates', [
            'since' => $since,
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetStatusUpdatesInvalidDate(): void
    {
        $since = 'invalidDateString';

        $response = $this->getAuthorized('api/v1/messages/statusupdates', [
            'since' => $since,
        ]);

        $this->assertEquals(422, $response->getStatusCode());
    }

    private function formatDateOrNull(?CarbonImmutable $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return $date->format('c');
    }
}
