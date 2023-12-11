<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Unit\Resources;

use Carbon\Carbon;
use MinVWS\MessagingApp\Model\OtpCode;
use MinVWS\MessagingApp\Resource\OtpCodeResource;
use MinVWS\MessagingApp\Tests\TestCase;

class OtpCodeResourceTest extends TestCase
{
    /**
     * @dataProvider obfuscatePhoneNumberDataProvider
     */
    public function testPhoneNumberObfuscation(string $phoneNumber, string $expectedResult): void
    {
        $otpCode = new OtpCode(
            'uuid',
            'messageUuid',
            'type',
            'code',
            Carbon::now(),
        );

        $otpCodeResource = new OtpCodeResource();
        $result = $otpCodeResource->convert($otpCode, $phoneNumber);

        $this->assertEquals($expectedResult, $result['phoneNumber']);
    }

    public function obfuscatePhoneNumberDataProvider(): array
    {
        return [
            ['0612345678', '*******678'],
            ['+31612345345', '*******345'],
            ['+31 (0) 612345123', '*******123'],
            ['', '*******'],
            ['1', '*******1'],
        ];
    }
}
