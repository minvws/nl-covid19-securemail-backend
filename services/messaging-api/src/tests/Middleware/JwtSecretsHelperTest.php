<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Tests\Middleware;

use MinVWS\MessagingApi\Middleware\JwtSecretsException;
use MinVWS\MessagingApi\Middleware\JwtSecretsHelper;
use MinVWS\MessagingApi\Tests\TestCase;

class JwtSecretsHelperTest extends TestCase
{
    /**
     * @dataProvider validSecretsFromStringDataProvider
     */
    public function testGetSecretsFromString(
        string $input,
        array $expectedResult,
        string $kidSecretSeparator = ':',
        string $platformSeparator = ',',
    ): void {
        $jwtSecrets = JwtSecretsHelper::getSecretsFromString($input, $kidSecretSeparator, $platformSeparator);

        $this->assertEquals($expectedResult, $jwtSecrets);
    }

    public function validSecretsFromStringDataProvider(): array
    {
        return [
            'single platform/secret pair' => ['foo:bar', ['foo' => 'bar']],
            'double platform/secret pair' => ['foo:bar,bar:foo', ['foo' => 'bar', 'bar' => 'foo']],
            'other kid separators' => ['foo|bar', ['foo' => 'bar'], '|'],
            'other platform separators' => ['foo:bar|bar:foo', ['foo' => 'bar', 'bar' => 'foo'], ':', '|'],
        ];
    }

    /**
     * @dataProvider invalidSecretsFromStringDataProvider
     */
    public function testInvalidSecretsFromString(string $input, string $expectedExceptionMessage): void
    {
        $this->expectException(JwtSecretsException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        JwtSecretsHelper::getSecretsFromString($input, ':', ',');
    }

    public function invalidSecretsFromStringDataProvider(): array
    {
        return [
            'none given' => ['', 'invalid platform/secret pair'],
            'single secret without platform identifier' => ['foo', 'invalid platform/secret pair'],
            'two secrets without platform identifier' => ['foo,bar', 'invalid platform/secret pair'],
            'two secrets, one without platform identifier' => ['foo:bar,baz', 'invalid platform/secret pair'],
            'invalid secret separator' => ['foo|bar', 'invalid platform/secret pair'],
            'invalid platform separator' => ['foo:bar|bar:foo', 'invalid platform/secret pair'],
            'too many parameters' => ['foo:bar:baz', 'invalid platform/secret pair'],
            'none unique secret' => ['foo:bar,bar:bar', 'non unique secret found'],
            'none unique platform identifier' => ['foo:bar,foo:foo', 'non unique platform identifier found'],
        ];
    }
}
