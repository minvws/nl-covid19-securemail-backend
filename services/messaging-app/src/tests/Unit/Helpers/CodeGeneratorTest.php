<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Unit\Helpers;

use MinVWS\MessagingApp\Helpers\CodeGenerator;
use MinVWS\MessagingApp\Tests\TestCase;

use function str_split;
use function strlen;

class CodeGeneratorTest extends TestCase
{
    /**
     * @dataProvider generateDateProvider
     */
    public function testGenerate(string $allowedCharacters, int $length): void
    {
        /** @var CodeGenerator $codeGenerator */
        $codeGenerator = $this->getContainer()->get(CodeGenerator::class);
        $code = $codeGenerator->generate($allowedCharacters, $length);

        $this->assertEquals($length, strlen($code));

        $expectedSplit = str_split($code);
        foreach ($expectedSplit as $expectedCharacter) {
            $this->assertStringContainsString($expectedCharacter, $allowedCharacters);
        }
    }

    public function generateDateProvider(): array
    {
        return [
            ['A', 1],
            ['ABC', 1],
            ['ABCDEFGHIJKLMNOPQRSTUVWXYZ', 3],
            ['0123456789', 3],
            ['ABCDEFGHJKMNPQRSTUVWXYX123456789', 6],
        ];
    }
}
