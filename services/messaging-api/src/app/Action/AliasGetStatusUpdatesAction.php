<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Action;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\Exception as CarbonException;
use MinVWS\Audit\AuditService;
use MinVWS\MessagingApi\Repository\AliasReadRepository;
use MinVWS\MessagingApi\Resource\AliasResource;
use MinVWS\MessagingApi\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

use function count;

class AliasGetStatusUpdatesAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        AuditService $auditService,
        private readonly AliasReadRepository $aliasRepository,
        private readonly AliasResource $aliasResource,
        private readonly ValidationService $validationService,
    ) {
        parent::__construct($logger, $auditService);
    }

    protected function action(): ResponseInterface
    {
        $this->auditService->setEventExpected(false);

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
            $aliassCount = $this->aliasRepository->countStatusUpdates($sinceDate);
            $alias = $this->aliasRepository->getStatusUpdates($sinceDate, $limit);
            $responseData = [
                'total' => $aliassCount,
                'count' => count($alias),
                'aliases' => $this->aliasResource->convertCollection($alias),
            ];
        } else {
            $responseData = [
                'total' => 0,
                'count' => 0,
                'aliases' => [],
            ];
        }

        return $this->jsonResponse($responseData);
    }
}
