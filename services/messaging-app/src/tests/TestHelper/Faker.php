<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\TestHelper;

use Faker\Factory;
use Faker\Generator;

abstract class Faker
{
    public static function create(): Generator
    {
        return Factory::create('nl_NL');
    }
}
