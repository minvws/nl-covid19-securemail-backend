<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Unit;

use MinVWS\MessagingApp\Helpers\DataObfuscator;
use MinVWS\MessagingApp\Tests\TestCase;

class DataObfuscatorTest extends TestCase
{
    /**
     * @dataProvider obfuscateEmailAddressDataProvider
     */
    public function testObfuscateEmailAddress(string $input, string $expectedOutput): void
    {
        $output = DataObfuscator::obfuscateEmailAddress($input);

        $this->assertEquals($expectedOutput, $output);
    }

    public function obfuscateEmailAddressDataProvider(): array
    {
        return [
            ['foo@bar.com', 'f****@b****.com'],
            ['foofoofoo@barbarbar.com', 'f****@b****.com'],
            ['b@f.nl', 'b****@f****.nl'],
        ];
    }

    /**
     * @dataProvider obfuscatePhoneNumberDataProvider
     */
    public function testObfuscatePhoneNumber(string $input, string $expectedOutput): void
    {
        $output = DataObfuscator::obfuscatePhoneNumber($input);

        $this->assertEquals($expectedOutput, $output);
    }

    public function obfuscatePhoneNumberDataProvider(): array
    {
        return [
            ['06 1234 5678', '*******678'],
            ['+31 6 1234 5678', '*******678'],
            ['+3212345789', '*******789'],
            ['1', '*******1'],
        ];
    }
}
