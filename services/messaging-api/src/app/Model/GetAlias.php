<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Model;

use Carbon\CarbonImmutable;
use MinVWS\MessagingApi\Enum\AliasStatus;

class GetAlias
{
    public function __construct(
        public readonly string $uuid,
        public readonly AliasStatus $status,
        public readonly CarbonImmutable $updatedAt,
        public readonly ?string $digidIdentifier,
    ) {
    }
}
