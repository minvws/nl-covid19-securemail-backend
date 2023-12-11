<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Action;

use Cake\Validation\Validator;
use Exception;
use Illuminate\Encryption\Encrypter;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Helpers\PHPDocHelper;
use MinVWS\Audit\Models\AuditEvent;
use MinVWS\Audit\Models\AuditObject;
use MinVWS\Audit\Models\AuditUser;
use MinVWS\MessagingApi\Enum\MessageType;
use MinVWS\MessagingApi\Middleware\JwtAuthenticationHelper;
use MinVWS\MessagingApi\Model\SaveAttachment;
use MinVWS\MessagingApi\Model\SaveMessage;
use MinVWS\MessagingApi\Repository\MessageWriteRepository;
use MinVWS\MessagingApi\Service\AttachmentService;
use MinVWS\MessagingApi\Service\UuidService;
use MinVWS\MessagingApi\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use Selective\Validation\Converter\CakeValidationConverter;
use Selective\Validation\Exception\ValidationException;

use function array_key_exists;
use function base64_decode;

class MessagePostAction extends Action
{
    public function __construct(
        private readonly AttachmentService $attachmentService,
        protected AuditService $auditService,
        protected LoggerInterface $logger,
        private readonly MessageWriteRepository $messageRepository,
        private readonly ValidationService $validationService,
    ) {
        parent::__construct($logger, $auditService);
    }

    /**
     * @auditEventDescription Registreer bericht
     *
     * @throws Exception
     */
    protected function action(): ResponseInterface
    {
        return $this->auditService->registerHttpEvent(
            AuditEvent::create(
                __METHOD__,
                AuditEvent::ACTION_CREATE,
                PHPDocHelper::getTagAuditEventDescriptionByActionName(__METHOD__),
            ),
            fn (AuditEvent $auditEvent) => $this->doMessagePostAction($auditEvent),
        );
    }

    private function doMessagePostAction(AuditEvent $auditEvent): ResponseInterface
    {
        $platform = $this->request->getAttribute(JwtAuthenticationHelper::PLATFORM_IDENTIFIER_ATTRIBUTE);
        $requestBody = (array) $this->request->getParsedBody();
        $attachmentsEncryptionKey = Encrypter::generateKey('aes-128-cbc');

        try {
            $attachments = $this->getAttachmentsFromRequestBody($requestBody, $attachmentsEncryptionKey);
            $message = $this->getMessageFromRequestBody(
                $platform,
                $requestBody,
                $attachments,
                $attachmentsEncryptionKey,
            );
            $this->setAuditData($message, $auditEvent);
        } catch (ValidationException $validationException) {
            $this->logger->error('repository exception', ['exception' => $validationException->getTraceAsString()]);
            $firstError = $validationException->getValidationResult()->getFirstError();
            return $this->jsonResponse([
                'error' => 'Validation failed',
                'field' => $firstError->getField(),
                'message' => $firstError->getMessage(),
            ])->withStatus(422);
        }

        $this->logger->info('processing message', ['uuid' => $message->uuid]);

        try {
            $this->attachmentService->saveAttachments($attachments);
            $this->messageRepository->save($message);
        } catch (RepositoryException $repositoryException) {
            $this->logger->error('repository exception', ['exception' => $repositoryException]);
            return $this->jsonResponse(['Error' => 'Message could not be saved'])
                ->withStatus(500);
        }

        return $this->jsonResponse(['id' => $message->uuid])
            ->withStatus(201);
    }

    /**
     * @param SaveAttachment[] $attachments
     *
     * @throws ValidationException
     */
    private function getMessageFromRequestBody(
        string $platform,
        array $requestBody,
        array $attachments,
        string $attachmentsEncryptionKey,
    ): SaveMessage {
        /** @var MessageType $messageType */
        $messageType = $this->validationService->getValueByTypeFromArray($requestBody, 'type', MessageType::class);

        $aliasExpiresAt = $this->validationService->getDateValueByArray($requestBody, 'aliasExpiresAt', false);
        $expiresAt = $this->validationService->getDateValueByArray($requestBody, 'expiresAt', false);
        $identityRequired = (bool) $this->validationService->getValueFromArray($requestBody, 'identityRequired');

        $attachmentData = [];
        foreach ($attachments as $attachment) {
            $attachmentData[] = [
                'uuid' => $attachment->uuid,
                'filename' => $attachment->filename,
                'mime_type' => $attachment->mimeType,
            ];
        }

        return new SaveMessage(
            UuidService::generate(),
            $messageType,
            $platform,
            $this->validationService->getValueFromArray($requestBody, 'aliasId'),
            $aliasExpiresAt,
            $this->validationService->getValueFromArray($requestBody, 'fromName'),
            $this->validationService->getValueFromArray($requestBody, 'fromEmail'),
            $this->validationService->getValueFromArray($requestBody, 'toName'),
            $this->validationService->getValueFromArray($requestBody, 'toEmail'),
            $this->validationService->getValueFromArray($requestBody, 'phoneNumber', false),
            $this->validationService->getValueFromArray($requestBody, 'subject'),
            $this->validationService->getValueFromArray($requestBody, 'text'),
            $this->validationService->getValueFromArray($requestBody, 'footer'),
            $attachmentData,
            $attachmentsEncryptionKey,
            $expiresAt,
            $identityRequired,
            $this->validationService->getValueFromArray($requestBody, 'pseudoBsnToken', $identityRequired),
        );
    }

    private function setAuditData(SaveMessage $message, AuditEvent $auditEvent): void
    {
        $auditUser = AuditUser::create($message->platform, $message->platformIdentifier);
        $auditEvent->user($auditUser);

        $auditObject = AuditObject::create('message', $message->uuid);
        $auditObject->detail('type', $message->type);
        $auditObject->detail('platform-identifier', $message->platformIdentifier);
        $auditObject->detail('pseudo-bsn-token', $message->pseudoBsnToken ?? 'unknown');
        $auditEvent->object($auditObject);
    }

    /**
     * @return SaveAttachment[]
     */
    private function getAttachmentsFromRequestBody(array $requestBody, string $encryptionKey): array
    {
        if (!array_key_exists('attachments', $requestBody) || $requestBody['attachments'] === null) {
            return [];
        }

        $attachmentValidator = new Validator();
        $attachmentValidator->requirePresence(['filename', 'content', 'mime_type']);
        $attachmentValidator->add('filename', 'not-blank', ['rule' => 'notBlank']);
        $attachmentValidator->add('filename', 'utf8', ['rule' => 'utf8']);
        $attachmentValidator->add('content', 'not-blank', ['rule' => 'notBlank']);
        $attachmentValidator->add('content', 'utf8', ['rule' => 'utf8']);
        $attachmentValidator->add('content', 'base64-encoded', ['rule' => function (string $value): bool {
            return base64_decode($value, true) !== false;
        }]);
        $attachmentValidator->add('mime_type', 'not-blank', ['rule' => 'notBlank']);
        $attachmentValidator->add('mime_type', 'utf8', ['rule' => 'utf8']);

        $validator = new Validator();
        $validator->addNestedMany('attachments', $attachmentValidator);

        $validation = CakeValidationConverter::createValidationResult($validator->validate($requestBody));
        if ($validation->fails()) {
            throw new ValidationException('Validation failed', $validation);
        }

        $encrypter = new Encrypter($encryptionKey, 'aes-128-cbc');

        $attachments = [];
        foreach ($requestBody['attachments'] as $attachment) {
            $attachments[] = new SaveAttachment(
                UuidService::generate(),
                $attachment['filename'],
                $encrypter->encrypt(base64_decode($attachment['content'])),
                $attachment['mime_type'],
            );
        }

        return $attachments;
    }
}
