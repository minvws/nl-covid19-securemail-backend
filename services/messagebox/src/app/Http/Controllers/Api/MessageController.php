<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnlinkByUuidRequest;
use App\Models\MessagePreview;
use App\Repositories\MessageRepository;
use App\Resources\MessageResource;
use App\Services\MessageService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use MinVWS\Audit\Models\AuditEvent;
use MinVWS\Audit\Models\AuditObject;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

use function array_map;

class MessageController extends Controller
{
    public function __construct(
        private readonly MessageResource $messageResource,
        private readonly MessageRepository $messageRepository,
        private readonly MessageService $messageService,
        private readonly ResponseFactory $response,
    ) {
    }

    /**
     * @auditEventDescription Lees bericht
     */
    public function getByUuid(string $uuid, AuditEvent $auditEvent): JsonResponse
    {
        try {
            $message = $this->messageService->getByUuidAndSession($uuid);
        } catch (RepositoryException $e) {
            return $this->response->json(['error' => $e->getMessage()], $e->getCode());
        }

        $auditEvent->object(AuditObject::create('message', $message->uuid));

        return $this->response->json([
            'message' => $this->messageResource->convertToResource($message),
        ]);
    }

    /**
     * @auditEventDescription Berichten in mailbox ophalen
     *
     * @throws RepositoryException
     */
    public function getList(AuditEvent $auditEvent): JsonResponse
    {
        $messages = $this->messageService->getList();
        $messages = $messages->sortByDesc(function (MessagePreview $message): int|float|string {
            return $message->createdAt->timestamp;
        });

        $auditEvent->objects(
            array_map(fn (MessagePreview $m) => AuditObject::create('message', $m->uuid), $messages->all())
        );

        return $this->response->json($messages->values());
    }

    /**
     * @auditEventDescription Bericht ontkoppelen van mailbox
     *
     * @throws RepositoryException
     */
    public function unlinkByUUid(UnlinkByUuidRequest $request, AuditEvent $auditEvent): JsonResponse
    {
        $this->messageRepository->unlinkMessageByUuid(
            $request->getPostMessageUuid(),
            $request->getPostReason()
        );

        $auditEvent->object(AuditObject::create('message', $request->getPostMessageUuid()));

        return $this->response->json([
            'success' => 'message unlinked',
        ]);
    }
}
