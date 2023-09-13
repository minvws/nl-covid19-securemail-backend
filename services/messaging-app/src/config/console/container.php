<?php

declare(strict_types=1);

use Laminas\Config\Config;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\MarkdownConverterInterface;
use MinVWS\Audit\AuditService;
use MinVWS\MessagingApp\Queue\Task\MailProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

return [
    // mail
    MailerInterface::class => function (ContainerInterface $container): Mailer {
        $config = $container->get(Config::class)->get('smtp');

        if ($config->get('user') !== null && $config->get('pass') !== null) {
            $auth = sprintf('%s:%s@', $config->get('user'), $config->get('pass'));
        } else {
            $auth = null;
        }

        $dsn = sprintf('smtp://%s%s:%s', $auth, $config->get('host'), $config->get('port'));

        return new Mailer(Transport::fromDsn($dsn));
    },
    MailProcessor::class => function (ContainerInterface $container): MailProcessor {
        return new MailProcessor(
            $container->get(LoggerInterface::class),
            $container->get(MailerInterface::class),
            $container->get(Config::class)->get('mail')->get('default_from_address'),
            $container->get(AuditService::class)
        );
    },

    // markdown
    MarkdownConverterInterface::class => function (ContainerInterface $container): CommonMarkConverter {
        return new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
        ]);
    },

    // twig
    Environment::class => function (): Environment {
        $loader = new FilesystemLoader(sprintf('%s/../../templates/', __DIR__));
        return new Environment($loader, [
            'cache' => sprintf('%s/../../var/cache', __DIR__),
            'auto_reload' => true,
        ]);
    },
];
