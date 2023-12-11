<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests;

use DI\Container;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase as PHPUnit_TestCase;
use Symfony\Component\Console\Application;

abstract class CommandTestCase extends PHPUnit_TestCase
{
    use ApplicationTrait;

    protected ?Application $app = null;
    protected ?Container $container = null;
    protected Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $app = require APP_ROOT . '/config/console.php';
        $this->app = $app['app'];
        $this->container = $app['container'];
        $this->faker = Factory::create();
    }
}
