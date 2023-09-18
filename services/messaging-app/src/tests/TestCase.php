<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests;

use DI\Container;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase as PHPUnit_TestCase;
use Slim\App;

use function sprintf;

abstract class TestCase extends PHPUnit_TestCase
{
    use ApplicationTrait;

    protected ?App $app = null;
    protected ?Container $container = null;
    protected Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = require sprintf('%s/config/application.php', APP_ROOT);
        $this->container = $this->app->getContainer();
        $this->faker = Factory::create();
    }
}
