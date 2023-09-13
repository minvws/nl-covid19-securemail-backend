<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Models\Enums\Error;
use App\Services\AttachmentService;
use App\Services\MessageService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use League\Flysystem\FilesystemException;
use MinVWS\Audit\Models\AuditEvent;
use MinVWS\Audit\Models\AuditObject;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;
use function strlen;

class AttachmentController
{
    public function __construct(
        private readonly AttachmentService $attachmentService,
        private readonly FilesystemAdapter $filesystem,
        private readonly LoggerInterface $logger,
        private readonly MessageService $messageService,
        private readonly Redirector $redirector,
    ) {
    }

    public function download(string $messageUuid, string $attachmentUuid, AuditEvent $auditEvent): Response
    {
        $auditEvent->object(AuditObject::create('attachment', $attachmentUuid));

        try {
            // validates if access to message is allowed
            $message = $this->messageService->getByUuidAndSession($messageUuid);
        } catch (AuthenticationException) {
            return $this->buildRedirectResponseFromError(Error::unauthenticated());
        } catch (RepositoryException) {
            return $this->buildRedirectResponseFromError(Error::attachmentNotAvailable());
        }

        if ($message->attachmentsEncryptionKey === null) {
            $this->logger->debug('attachments encryption key not set');

            return $this->buildRedirectResponseFromError(Error::attachmentNotAvailable());
        }

        try {
            $attachment = $this->attachmentService->getAttachment($attachmentUuid, $messageUuid);
        } catch (RepositoryException $repositoryException) {
            $this->logger->debug('attachment not found', ['exception' => $repositoryException]);

            return $this->buildRedirectResponseFromError(Error::attachmentNotAvailable());
        }

        try {
            $this->logger->debug('reading file', ['attachmentUuid' => $attachment->uuid]);
            $encryptedFileContents = $this->filesystem->read($attachment->uuid);
        } catch (FilesystemException $filesystemException) {
            $this->logger->error('unable to read file', ['exception' => $filesystemException]);

            return $this->buildRedirectResponseFromError(Error::attachmentNotAvailable());
        }

        try {
            $this->logger->debug('decrypting file contents', ['attachmentUuid' => $attachment->uuid]);
            $encrypter = new Encrypter($message->attachmentsEncryptionKey, 'aes-128-cbc');
            $fileContents = $encrypter->decrypt($encryptedFileContents);
        } catch (DecryptException $decryptException) {
            $this->logger->error('unable to decrypt file', ['exception' => $decryptException]);

            return $this->buildRedirectResponseFromError(Error::attachmentNotAvailable());
        }

        return new Response($fileContents, 200, [
            'Content-Type' => $attachment->mimeType,
            'Content-Length' => strlen($fileContents),
            'Content-Disposition' => sprintf('attachment; filename="%s"', $attachment->name),
        ]);
    }

    private function buildRedirectResponseFromError(Error $error): RedirectResponse
    {
        return $this->redirector->to(sprintf('error/%s', $error->value));
    }
}
