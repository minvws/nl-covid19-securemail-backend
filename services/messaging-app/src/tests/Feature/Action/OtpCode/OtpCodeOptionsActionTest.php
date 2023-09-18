<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Action\OtpCode;

use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Repository\OtpCodeRepository;
use MinVWS\MessagingApp\Tests\Feature\Action\ActionTestCase;
use MinVWS\MessagingApp\Tests\TestHelper\Faker;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

use function sprintf;
use function substr;

class OtpCodeOptionsActionTest extends ActionTestCase
{
    public function testValidResponse(): void
    {
        $phoneNumber = $this->faker->phoneNumber;
        $message = $this->createMessage(['phoneNumber' => $phoneNumber]);
        $otpCode = $this->createOtpCode(['messageUuid' => $message->uuid]);

        $this->mock(OtpCodeRepository::class)
            ->expects($this->once())
            ->method('getByMessageUuidAndCode')
            ->willReturn($otpCode);

        $this->mock(MessageRepository::class)
            ->expects($this->once())
            ->method('getByUuid')
            ->willReturn($message);

        $requestData = [
            'messageUuid' => $this->faker->uuid,
            'otpCode' => $this->faker->lexify('??????'),
        ];

        $response = $this->getAuthenticatedJson('/api/v1/otp-code/options', [], [], $requestData);

        $expectedResponse = [
            'uuid' => $otpCode->uuid,
            'type' => $otpCode->type,
            'phoneNumber' => sprintf('*******%s', substr($phoneNumber, -3)),
            'validUntil' => $otpCode->validUntil->format('c'),
        ];

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonDataFromResponse($expectedResponse, $response);
    }

    /**
     * @dataProvider invalidValidationDataProvider
     */
    public function testValidationFails(array $requestData): void
    {
        $response = $this->getAuthenticatedJson('/api/v1/otp-code/options', [], [], $requestData);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function invalidValidationDataProvider(): array
    {
        $faker = Faker::create();

        return [
            'empty body' => [[]],
            'only messageUuid' => [['messageUuid' => $faker->uuid]],
            'only otpCode' => [['otpCode' => $faker->lexify('??????')]],
        ];
    }

    public function testMessageNotFoundResponseIfOtpCodeLookupFails(): void
    {
        $this->mock(OtpCodeRepository::class)
            ->expects($this->once())
            ->method('getByMessageUuidAndCode')
            ->willThrowException(new RepositoryException('not found'));

        $requestData = [
            'messageUuid' => $this->faker->uuid,
            'otpCode' => $this->faker->lexify('??????'),
        ];

        $response = $this->getAuthenticatedJson('/api/v1/otp-code/options', [], [], $requestData);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testMessageNotFoundResponseIfMessageLookupFails(): void
    {
        $this->mock(OtpCodeRepository::class)
            ->expects($this->once())
            ->method('getByMessageUuidAndCode')
            ->willReturn($this->createOtpCode());

        $this->mock(MessageRepository::class)
            ->expects($this->once())
            ->method('getByUuid')
            ->willThrowException(new RepositoryException('not found'));

        $requestData = [
            'messageUuid' => $this->faker->uuid,
            'otpCode' => $this->faker->lexify('??????'),
        ];

        $response = $this->getAuthenticatedJson('/api/v1/otp-code/options', [], [], $requestData);

        $this->assertSame(404, $response->getStatusCode());
    }
}
