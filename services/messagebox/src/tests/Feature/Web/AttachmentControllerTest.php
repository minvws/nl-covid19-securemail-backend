<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Attachment;
use App\Models\Enums\Error;
use App\Models\User;
use App\Repositories\MessageRepository;
use App\Services\AttachmentService;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use Mockery\MockInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use Tests\Feature\ControllerTestCase;

use function base64_encode;
use function sprintf;

class AttachmentControllerTest extends ControllerTestCase
{
    public function testDownloadAttachment(): void
    {
        $filesystemDiskAttachments = 'attachments-local';
        $this->config->set('filesystems.attachments', $filesystemDiskAttachments);

        $message = $this->createMessage(['attachmentsEncryptionKey' => Encrypter::generateKey('aes-128-cbc')]);
        $pseudoBsn = $this->faker->uuid;
        $attachmentUuid = $this->faker->uuid;
        $filename = sprintf('%s.%s', $this->faker->word, $this->faker->fileExtension());

        $encrypter = new Encrypter($message->attachmentsEncryptionKey);
        $fileContents = $encrypter->encrypt(base64_encode($this->faker->word));
        Storage::disk($filesystemDiskAttachments)->put($attachmentUuid, $fileContents);

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($message, $pseudoBsn): void {
            $mock->shouldReceive('getByUuidAndPseudoBsn')
                ->once()
                ->with($message->uuid, $pseudoBsn)
                ->andReturn($message);
        });
        $this->mock(
            AttachmentService::class,
            function (MockInterface $mock) use ($filename, $attachmentUuid, $message): void {
                $mock->shouldReceive('getAttachment')
                    ->once()
                    ->with($attachmentUuid, $message->uuid)
                    ->andReturn($this->createAttachment($attachmentUuid, $filename));
            }
        );

        $user = new User(User::AUTH_DIGID, $pseudoBsn);
        $response = $this
            ->be($user)
            ->get(sprintf('messages/%s/attachment/%s/download', $message->uuid, $attachmentUuid));
        $response->assertStatus(200);
        $response->assertDownload($filename);

        Storage::disk($filesystemDiskAttachments)->delete($attachmentUuid);
    }

    public function testDownloadFailsWhenNoAttachmentsEncryptionKeySet(): void
    {
        $filesystemDiskAttachments = 'attachments-local';
        $this->config->set('filesystems.attachments', $filesystemDiskAttachments);

        $message = $this->createMessage(['attachmentsEncryptionKey' => null]);
        $pseudoBsn = $this->faker->uuid;
        $attachmentUuid = $this->faker->uuid;

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($message, $pseudoBsn): void {
            $mock->shouldReceive('getByUuidAndPseudoBsn')
                ->once()
                ->with($message->uuid, $pseudoBsn)
                ->andReturn($message);
        });

        $user = new User(User::AUTH_DIGID, $pseudoBsn);
        $response = $this
            ->be($user)
            ->get(sprintf('messages/%s/attachment/%s/download', $message->uuid, $attachmentUuid));
        $response->assertRedirect('/error/attachment_not_available');
    }

    public function testDownloadAttachmentUserNotAuthorized(): void
    {
        $response = $this->get(sprintf('messages/%s/attachment/%s/download', $this->faker->uuid, $this->faker->uuid));
        $response->assertRedirect(sprintf('/error/%s', Error::unauthenticated()->value));
    }

    public function testDownloadAttachmentNotAvailable(): void
    {
        $message = $this->createMessage(['attachmentsEncryptionKey' => Encrypter::generateKey('aes-128-cbc')]);
        $pseudoBsn = $this->faker->uuid;
        $attachmentUuid = $this->faker->uuid;

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($message, $pseudoBsn): void {
            $mock->shouldReceive('getByUuidAndPseudoBsn')
                ->once()
                ->with($message->uuid, $pseudoBsn)
                ->andReturn($message);
        });
        $this->mock(AttachmentService::class, function (MockInterface $mock) use ($attachmentUuid, $message) {
            $mock->shouldReceive('getAttachment')
                ->once()
                ->with($attachmentUuid, $message->uuid)
                ->andThrow(new RepositoryException());
        });

        $user = new User(User::AUTH_DIGID, $pseudoBsn);
        $response = $this
            ->be($user)
            ->get(sprintf('messages/%s/attachment/%s/download', $message->uuid, $attachmentUuid));
        $response->assertRedirect('/error/attachment_not_available');
    }

    public function testDownloadAttachmentReadFails(): void
    {
        $message = $this->createMessage(['attachmentsEncryptionKey' => Encrypter::generateKey('aes-128-cbc')]);
        $pseudoBsn = $this->faker->uuid;
        $attachmentUuid = $this->faker->uuid;

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($message): void {
            $mock->shouldReceive('getByUuidAndPseudoBsn')
                ->andReturn($message);
        });
        $this->mock(AttachmentService::class, function (MockInterface $mock) use ($attachmentUuid) {
            $mock->shouldReceive('getAttachment')
                ->andReturn(new Attachment($attachmentUuid, $this->faker->name, $this->faker->mimeType()));
        });
        $this->mock(FilesystemAdapter::class, function (MockInterface $mock) {
            $mock->shouldReceive('read')
                ->andThrow(new UnableToReadFile($this->faker->sentence));
        });

        $user = new User(User::AUTH_DIGID, $pseudoBsn);
        $response = $this
            ->be($user)
            ->get(sprintf('messages/%s/attachment/%s/download', $message->uuid, $attachmentUuid));
        $response->assertRedirect('/error/attachment_not_available');
    }
}
