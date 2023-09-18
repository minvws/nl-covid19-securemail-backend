<?php

declare(strict_types=1);

namespace App\Application\Factory;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LoggerFactory
{
    private array $handler = [];
    private array $processors = [];

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

        foreach ($this->processors as $processor) {
            $logger->pushProcessor($processor);
        }
        $this->processors = [];

        return $logger;
    }

    public function addFileHandler(): self
    {
        /** @phpstan-ignore-next-line */
        $rotatingFileHandler = new RotatingFileHandler($this->path, 0, $this->level, true, 0777);

        $rotatingFileHandler->setFormatter(new LineFormatter(null, null, false, true));

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
        /** @phpstan-ignore-next-line */
        $streamHandler = new StreamHandler('php://stdout', $this->level);
        $streamHandler->setFormatter(new LineFormatter(null, null, false, true));

        $this->handler[] = $streamHandler;

        return $this;
    }
}
