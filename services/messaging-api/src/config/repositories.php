<?php

declare(strict_types=1);

use MinVWS\Audit\Repositories\AuditRepository;
use MinVWS\Audit\Repositories\LogAuditRepository;
use MinVWS\MessagingApi\Repository\AliasReadRepository;
use MinVWS\MessagingApi\Repository\AttachmentWriteRepository;
use MinVWS\MessagingApi\Repository\Database\DatabaseAliasRepository;
use MinVWS\MessagingApi\Repository\Database\DatabaseMessageRepository;
use MinVWS\MessagingApi\Repository\Filesystem\FilesystemAttachmentRepository;
use MinVWS\MessagingApi\Repository\MessageReadRepository;
use MinVWS\MessagingApi\Repository\MessageWriteRepository;
use MinVWS\MessagingApi\Repository\Redis\RedisMessageRepository;

use function DI\autowire;

return [
    AliasReadRepository::class => autowire(DatabaseAliasRepository::class),
    AttachmentWriteRepository::class => autowire(FilesystemAttachmentRepository::class),
    AuditRepository::class => autowire(LogAuditRepository::class),
    MessageReadRepository::class => autowire(DatabaseMessageRepository::class),
    MessageWriteRepository::class => autowire(RedisMessageRepository::class),
];
