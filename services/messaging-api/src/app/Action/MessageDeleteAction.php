<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Action;

use Exception;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Helpers\PHPDocHelper;
use MinVWS\Audit\Models\AuditEvent;
use MinVWS\MessagingApi\Repository\EntityNotFoundException;
use MinVWS\MessagingApi\Repository\MessageWriteRepository;
use MinVWS\MessagingApi\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class MessageDeleteAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        AuditService $auditService,
        private readonly MessageWriteRepository $messageRepository,
        private readonly ValidationService $validationService,
    ) {
        parent::__construct($logger, $auditService);
    }

    /**
     * @auditEventDescription Bericht verwijderen
     *
     * @throws Exception
     */
    protected function action(): ResponseInterface
    {
        return $this->auditService->registerHttpEvent(
            AuditEvent::create(
                __METHOD__,
                AuditEvent::ACTION_DELETE,
                PHPDocHelper::getTagAuditEventDescriptionByActionName(__METHOD__),
            ),
            fn (AuditEvent $auditEvent) => $this->doMessageDeleteAction(),
        );
    }

    private function doMessageDeleteAction(): ResponseInterface
    {
        $messageUuid = $this->validationService->getValueFromArray($this->requestArguments, 'uuid');
        try {
            $this->messageRepository->delete($messageUuid);
        } catch (EntityNotFoundException) {
            return $this->jsonResponse(['Error' => 'Message could not be found'])
                ->withStatus(404);
        } catch (RepositoryException) {
            return $this->jsonResponse(['Error' => 'Message could not be deleted',])
                ->withStatus(500);
        }

        return $this->jsonResponse()->withStatus(204);
    }
}
