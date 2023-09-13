<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Factory;

use Monolog\Handler\NullHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Helpers\LineFormatterFactory;

final class LoggerFactory
{
    private array $handler = [];

    public function __construct(
        private readonly string $level,
        private readonly string $path,
    ) {
    }

    public function createInstance(string $name): LoggerInterface
    {
        $logger = new Logger($name);

        foreach ($this->handler as $handler) {
            $logger->pushHandler($handler);
        }

        $this->handler = [];

        return $logger;
    }

    public function addFileHandler(): self
    {
        $rotatingFileHandler = new RotatingFileHandler($this->path, 0, $this->level, true, 0777);

        $rotatingFileHandler->setFormatter(LineFormatterFactory::getDefaultFormatter());

        $this->handler[] = $rotatingFileHandler;

        return $this;
    }

    public function addNullHandler(): self
    {
        $this->handler[] = new NullHandler();

        return $this;
    }

    public function addConsoleHandler(): self
    {
        $streamHandler = new StreamHandler('php://stdout', $this->level);
        $streamHandler->setFormatter(LineFormatterFactory::getDefaultFormatter());

        $this->handler[] = $streamHandler;

        return $this;
    }

    public function addTestHandler(): self
    {
        $testHandler = new TestHandler();
        $testHandler->setFormatter(LineFormatterFactory::getDefaultFormatter());

        $this->handler[] = $testHandler;

        return $this;
    }
}
