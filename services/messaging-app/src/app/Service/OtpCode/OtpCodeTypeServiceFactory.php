<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Service\OtpCode;

use MinVWS\MessagingApp\Service\OtpCode\Sms\SmsService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class OtpCodeTypeServiceFactory
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @throws OtpCodeTypException
     */
    public function fromString(string $type): OtpCodeTypeService
    {
        try {
            switch ($type) {
                case 'sms':
                    return $this->container->get(SmsService::class);
                default:
                    throw new OtpCodeTypException('type not found');
            }
        } catch (ContainerExceptionInterface | NotFoundExceptionInterface $exception) {
            throw OtpCodeTypException::fromThrowable($exception);
        }
    }
}
