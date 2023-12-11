<?php

namespace MinVWS\MessagingApp\Tests;

use Carbon\CarbonImmutable;
use DI\Container;
use Laminas\Config\Config;
use PHPUnit\Framework\MockObject\MockObject;
use UnderflowException;

trait ApplicationTrait
{
    public function getConfig(): Config
    {
        $config = $this->getContainer()->get(Config::class);
        if ($config instanceof Config) {
            return $config;
        }
        throw new UnderflowException('Config object not available');
    }

    public function getContainer(): Container
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

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->app = null;
        $this->container = null;

        CarbonImmutable::setTestNow(); // reset
    }
}
