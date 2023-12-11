<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Service\OtpCode;

use MinVWS\MessagingApp\Helpers\CodeGenerator;
use MinVWS\MessagingApp\Repository\OtpCodeRepository;
use MinVWS\MessagingApp\Service\OtpCode\OtpCodeService;
use MinVWS\MessagingApp\Service\OtpCode\OtpCodeTypeServiceFactory;
use MinVWS\MessagingApp\Tests\Feature\FeatureTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class OtpCodeServiceTest extends FeatureTestCase
{
    /**
     * @dataProvider createFromMessageUuidAndTypeDataProvider
     */
    public function testCreateFromMessageUuidAndType(bool $testMode, string $generatedCode, string $expectedCode): void
    {
        $message = $this->createMessage();

        /** @var CodeGenerator|MockObject $codeGenerator */
        $codeGenerator = $this->mock(CodeGenerator::class);
        $codeGenerator->expects($testMode ? $this->never() : $this->once())
            ->method('generate')
            ->willReturn($generatedCode);

        $otpCodeService = new OtpCodeService(
            $codeGenerator,
            $this->getContainer()->get(OtpCodeRepository::class),
            $this->getContainer()->get(OtpCodeTypeServiceFactory::class),
            $testMode
        );

        $otpCode = $otpCodeService->createFromMessageUuidAndType($message->uuid, $this->faker->word);

        $this->assertEquals($expectedCode, $otpCode->code);
    }

    public function createFromMessageUuidAndTypeDataProvider(): array
    {
        return [
            [false, '123', '123'],
            [false, '456', '456'],
            [true, '456', '123456'],
        ];
    }
}
