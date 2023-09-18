<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Repository\Database;

use MinVWS\MessagingApp\Repository\Database\DatabaseOtpCodeRepository;
use MinVWS\MessagingApp\Tests\Feature\FeatureTestCase;

class DatabaseOtpCodeRepositoryTest extends FeatureTestCase
{
    protected DatabaseOtpCodeRepository $otpCodeRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->otpCodeRepository = $this->getContainer()->get(DatabaseOtpCodeRepository::class);
    }

    public function testDeleteExpired(): void
    {
        $otpCode = $this->createOtpCode(['validUntil' => $this->faker->dateTime('-1 day')]);

        $this->otpCodeRepository->deleteExpired();

        $this->assertDatabaseCount('otp_code', ['uuid' => $otpCode->uuid], 0);
    }

    public function testDeleteExpiredWhenNoAttachedMessage(): void
    {
        $otpCode = $this->createOtpCode([
            'messageUuid' => null,
            'validUntil' => $this->faker->dateTimeBetween('+1 day', '+3 days'),
        ]);

        $this->otpCodeRepository->deleteExpired();

        $this->assertDatabaseCount('otp_code', ['uuid' => $otpCode->uuid], 0);
    }

    public function testNotDeleteNotExpired(): void
    {
        $otpCode = $this->createOtpCode(['validUntil' => $this->faker->dateTimeBetween('+1 day', '+3 days')]);

        $this->otpCodeRepository->deleteExpired();

        $this->assertDatabaseCount('otp_code', ['uuid' => $otpCode->uuid], 1);
    }
}
