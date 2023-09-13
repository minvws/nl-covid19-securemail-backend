<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Tests\TestHelper;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory as RamseyUuidFactory;
use Ramsey\Uuid\UuidInterface;

class UuidFactory extends RamseyUuidFactory
{
    private UuidInterface $uuid4;

    public function uuid4(): UuidInterface
    {
        return $this->uuid4;
    }

    public function setUuid4(string $uuid): void
    {
        $this->uuid4 = Uuid::fromString($uuid);
    }
}
