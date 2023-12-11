<?php

declare(strict_types=1);

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Contracts\Container\BindingResolutionException;
use Laminas\Config\Config;
use MinVWS\Audit\Repositories\AuditRepository;
use MinVWS\Audit\Repositories\LogAuditRepository;
use MinVWS\MessagingApp\Repository\AliasRepository;
use MinVWS\MessagingApp\Repository\AttachmentRepository;
use MinVWS\MessagingApp\Repository\Database\DatabaseAliasRepository;
use MinVWS\MessagingApp\Repository\Database\DatabaseAttachmentRepository;
use MinVWS\MessagingApp\Repository\Database\DatabaseMailboxRepository;
use MinVWS\MessagingApp\Repository\Database\DatabaseMessageRepository;
use MinVWS\MessagingApp\Repository\Database\DatabaseOtpCodeRepository;
use MinVWS\MessagingApp\Repository\Database\DatabasePairingCodeRepository;
use MinVWS\MessagingApp\Repository\MailboxRepository;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Repository\OtpCodeRepository;
use MinVWS\MessagingApp\Repository\PairingCodeRepository;
use MinVWS\MessagingApp\Service\BsnService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Repositories\Local\LocalPseudoBsnRepository;
use SecureMail\Shared\Application\Repositories\Mittens\MittensPseudoBsnRepository;
use SecureMail\Shared\Application\Repositories\PseudoBsnRepository;

use function DI\autowire;

return [
    AliasRepository::class => autowire(DatabaseAliasRepository::class),
    AttachmentRepository::class => autowire(DatabaseAttachmentRepository::class),
    AuditRepository::class => autowire(LogAuditRepository::class),
    MailboxRepository::class => autowire(DatabaseMailboxRepository::class),
    MessageRepository::class => autowire(DatabaseMessageRepository::class),
    OtpCodeRepository::class => autowire(DatabaseOtpCodeRepository::class),
    PairingCodeRepository::class => autowire(DatabasePairingCodeRepository::class),

    PseudoBsnRepository::class => function (ContainerInterface $container): PseudoBsnRepository {
        $config = $container->get(Config::class);
        switch ($config->get('pseudo_bsn_service', 'mittens')) {
            case 'mittens':
                return new MittensPseudoBsnRepository(
                    new GuzzleClient($config->get('mittens')->toArray()['client_options']),
                    $config->get('mittens')->toArray()['digid_access_token'],
                    $container->get(LoggerInterface::class)
                );
            case 'local':
                return new LocalPseudoBsnRepository();
            default:
                throw new BindingResolutionException('no (valid) pseudo_bsn_service found');
        }
    },
    BsnService::class => function (ContainerInterface $container) {
        return new BsnService(
            $container->get(PseudoBsnRepository::class)
        );
    },
];
