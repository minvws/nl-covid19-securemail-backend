<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Tests;

use Carbon\CarbonImmutable;
use DI\Container;
use Faker\Factory;
use Faker\Generator;
use Laminas\Config\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use MinVWS\MessagingApi\Tests\TestHelper\UuidFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as PHPUnit_TestCase;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Slim\App;
use UnderflowException;

use function sprintf;

abstract class TestCase extends PHPUnit_TestCase
{
    protected ?App $app;
    protected ?ContainerInterface $container;
    protected ?Filesystem $filesystem;
    protected Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = require sprintf('%s/config/bootstrap.php', APP_ROOT);
        $this->container = $this->app->getContainer();
        $this->faker = Factory::create();

        if ($this->container instanceof Container) {
            $this->filesystem = new Filesystem(new InMemoryFilesystemAdapter());
            $this->container->set(FilesystemOperator::class, $this->filesystem);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->app = null;
        $this->container = null;

        CarbonImmutable::setTestNow(); // reset
    }

    protected function getConfig(): Config
    {
        $config = $this->getContainer()->get(Config::class);
        if ($config instanceof Config) {
            return $config;
        }
        throw new UnderflowException('Config object not available');
    }

    protected function getContainer(): Container
    {
        if ($this->container instanceof Container) {
            return $this->container;
        }
        throw new UnderflowException('Container object not available');
    }

    protected function mock(string $class): MockObject
    {
        $mock = $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($this->container instanceof Container) {
            $this->container->set($class, $mock);
        }

        return $mock;
    }

    protected function mockUuid(string $uuid): void
    {
        $uuidFactory = new UuidFactory();
        Uuid::setFactory($uuidFactory);

        $uuidFactory->setUuid4($uuid);
    }
}
