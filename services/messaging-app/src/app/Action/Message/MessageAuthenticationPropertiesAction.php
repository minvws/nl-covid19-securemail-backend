<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Action\Message;

use MinVWS\Audit\AuditService;
use MinVWS\MessagingApp\Action\Action;
use MinVWS\MessagingApp\Repository\EntityNotFoundException;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Resource\MessageAuthenticationPropertiesResource;
use MinVWS\MessagingApp\Resource\ResourceException;
use MinVWS\MessagingApp\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class MessageAuthenticationPropertiesAction extends Action
{
    public function __construct(
        protected AuditService $auditService,
        protected LoggerInterface $logger,
        private readonly MessageRepository $messageRepository,
        private readonly MessageAuthenticationPropertiesResource $messageAuthenticationPropertiesResource,
        private readonly ValidationService $validationService,
    ) {
        parent::__construct($auditService, $logger);
    }

    protected function action(): ResponseInterface
    {
        $this->auditService->setEventExpected(false);

        $messageUuid = $this->validationService->getValueFromArray($this->requestArguments, 'messageUuid');

        try {
            $message = $this->messageRepository->getByUuid($messageUuid);
            return $this->jsonResponse($this->messageAuthenticationPropertiesResource->convert($message));
        } catch (EntityNotFoundException) {
            return $this->notFoundResponse();
        } catch (ResourceException | RepositoryException $exception) {
            return $this->jsonResponse(['error' => $exception->getMessage()], 500);
        }
    }
}
