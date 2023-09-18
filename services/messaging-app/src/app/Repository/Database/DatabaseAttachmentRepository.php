<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Repository\Database;

use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use MinVWS\MessagingApp\Model\Attachment;
use MinVWS\MessagingApp\Repository\AttachmentRepository;
use MinVWS\MessagingApp\Repository\EntityNotFoundException;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

use function sprintf;

class DatabaseAttachmentRepository extends DatabaseRepository implements AttachmentRepository
{
    public const TABLE = 'attachment';
    public const FIELD_UUID = 'uuid';
    public const FIELD_MESSAGE_UUID = 'message_uuid';
    public const FIELD_FILENAME = 'filename';
    public const FIELD_MIME_TYPE = 'mime_type';

    protected function getTable(): string
    {
        return self::TABLE;
    }

    /**
     * @throws RepositoryException
     */
    public function delete(Attachment $attachment): void
    {
        try {
            $builder = $this->getBuilder();
            $builder->where(self::FIELD_UUID, '=', $attachment->uuid);
            $builder->delete();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }
    }

    /**
     * @throws RepositoryException
     */
    public function getByUuidAndMessageUuid(string $attachmentUuid, string $messageUuid): Attachment
    {
        try {
            $builder = $this->getBuilder();
            $builder->select(sprintf('%s.*', self::TABLE));
            $builder->where(sprintf('%s.%s', self::TABLE, self::FIELD_UUID), '=', $attachmentUuid);
            $builder->where(sprintf('%s.%s', self::TABLE, self::FIELD_MESSAGE_UUID), '=', $messageUuid);
            $result = $builder->first();
        } catch (QueryException $queryException) {
            $this->logger->debug('getByUuidAndMessageUuid failed', ['error' => $queryException->getMessage()]);
            throw RepositoryException::fromThrowable($queryException);
        }

        return $this->convertToAttachment($result);
    }

    /**
     * @throws RepositoryException
     */
    public function getByMessageUuid(string $messageUuid): Collection
    {
        try {
            $builder = $this->getBuilder();
            $builder->select(sprintf('%s.*', self::TABLE));
            $builder->where(sprintf('%s.%s', self::TABLE, self::FIELD_MESSAGE_UUID), '=', $messageUuid);
            $results = $builder->get();
        } catch (QueryException $queryException) {
            $this->logger->debug('getByMessageUuid failed', ['error' => $queryException->getMessage()]);
            throw RepositoryException::fromThrowable($queryException);
        }

        return $this->convertToAttachments($results);
    }

    /**
     * @return Collection<Attachment>
     *
     * @throws RepositoryException
     */
    public function getExpired(): Collection
    {
        try {
            $builder = $this->getBuilder();
            $builder->whereNull(self::FIELD_MESSAGE_UUID);
            $builder->limit(1000);
            $results = $builder->get();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }

        return $this->convertToAttachments($results);
    }

    /**
     * @throws RepositoryException
     */
    public function save(Attachment $attachment): void
    {
        try {
            $builder = $this->getBuilder();
            $builder->updateOrInsert([self::FIELD_UUID => $attachment->uuid], [
                self::FIELD_MESSAGE_UUID => $attachment->messageUuid,
                self::FIELD_FILENAME => $attachment->filename,
                self::FIELD_MIME_TYPE => $attachment->mimeType,
            ]);
            $this->logger->debug('attachment saved', ['attachmentUuid' => $attachment->uuid]);
        } catch (QueryException $queryException) {
            $this->logger->debug('attachment save failed', ['error' => $queryException->getMessage()]);
            throw RepositoryException::fromThrowable($queryException);
        }
    }

    /**
     * @throws EntityNotFoundException
     */
    private function convertToAttachment(?object $result): Attachment
    {
        if ($result === null) {
            throw new EntityNotFoundException('Attachment not found');
        }

        return new Attachment(
            $result->{self::FIELD_UUID},
            $result->{self::FIELD_MESSAGE_UUID},
            $result->{self::FIELD_FILENAME},
            $result->{self::FIELD_MIME_TYPE},
        );
    }

    /**
     * @param Collection<object> $results
     *
     * @return Collection<Attachment>
     *
     * @throws RepositoryException
     */
    private function convertToAttachments(Collection $results): Collection
    {
        $messages = new Collection();

        foreach ($results as $result) {
            $messages->push($this->convertToAttachment($result));
        }

        return $messages;
    }
}
