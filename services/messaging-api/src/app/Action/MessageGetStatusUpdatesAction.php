<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Action;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\Exception as CarbonException;
use Exception;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Helpers\PHPDocHelper;
use MinVWS\Audit\Models\AuditEvent;
use MinVWS\MessagingApi\Repository\MessageReadRepository;
use MinVWS\MessagingApi\Resource\MessageResource;
use MinVWS\MessagingApi\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

use function count;

class MessageGetStatusUpdatesAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        AuditService $auditService,
        private readonly MessageReadRepository $messageRepository,
        private readonly MessageResource $messageResource,
        private readonly ValidationService $validationService,
    ) {
        parent::__construct($logger, $auditService);
    }

    /**
     * @auditEventDescription Status van bericht ophalen
     *
     * @throws Exception
     */
    protected function action(): ResponseInterface
    {
        return $this->auditService->registerHttpEvent(
            AuditEvent::create(
                __METHOD__,
                AuditEvent::ACTION_READ,
                PHPDocHelper::getTagAuditEventDescriptionByActionName(__METHOD__),
            ),
            fn (AuditEvent $auditEvent) => $this->doMessageGetStatusUpdatesAction(),
        );
    }

    /**
     * @throws RepositoryException
     */
    private function doMessageGetStatusUpdatesAction(): ResponseInterface
    {
        $queryParams = $this->request->getQueryParams();

        $since = $this->validationService->getValueFromArray($queryParams, 'since');
        try {
            $sinceDate = CarbonImmutable::createFromFormat('c', $since);
        } catch (CarbonException) {
            return $this->jsonResponse(['error' => 'Invalid date'])->withStatus(422);
        }

        $limit = $this->validationService->getValueFromArray($queryParams, 'limit', false);
        if ($limit !== null) {
            $limit = (int) $limit;
        }

        if ($sinceDate !== false) {
            $messagesCount = $this->messageRepository->countStatusUpdates($sinceDate);
            $messages = $this->messageRepository->getStatusUpdates($sinceDate, $limit);

            $responseData = [
                'total' => $messagesCount,
                'count' => count($messages),
                'messages' => $this->messageResource->convertCollection($messages),
            ];
        } else {
            $responseData = [
                'total' => 0,
                'count' => 0,
                'messages' => [],
            ];
        }

        return $this->jsonResponse($responseData);
    }
}
