<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Repository\Database;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use MinVWS\DBCO\Encryption\Security\EncryptionException;
use MinVWS\MessagingApp\Model\Message;
use MinVWS\MessagingApp\Repository\EntityNotFoundException;
use MinVWS\MessagingApp\Repository\MessageRepository;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

use function sprintf;

class DatabaseMessageRepository extends DatabaseRepository implements MessageRepository
{
    public const TABLE = 'message';
    public const FIELD_ALIAS_UUID = 'alias_uuid';
    public const FIELD_IDENTITY_REQUIRED = 'identity_required';
    public const FIELD_EXPIRES_AT = 'expires_at';
    public const FIELD_FOOTER = 'footer';
    public const FIELD_FROM_EMAIL = 'from_email';
    public const FIELD_FROM_NAME = 'from_name';
    public const FIELD_FIRST_READ_AT = 'first_read_at';
    public const FIELD_MAILBOX_UUID = 'mailbox_uuid';
    public const FIELD_PLATFORM = 'platform';
    public const FIELD_NOTIFICATION_SENT_AT = 'notification_sent_at';
    public const FIELD_SUBJECT = 'subject';
    public const FIELD_PHONE_NUMBER = 'phone_number';
    public const FIELD_TEXT = 'text';
    public const FIELD_TO_EMAIL = 'to_email';
    public const FIELD_TO_EMAIL_HASH = 'to_email_hash';
    public const FIELD_TO_NAME = 'to_name';
    public const FIELD_UUID = 'uuid';
    public const FIELD_OTP_INCORRECT_PHONE_AT = 'otp_incorrect_phone_at';
    public const FIELD_ATTACHMENT_COUNT = 'attachment_count';
    public const FIELD_ATTACHMENTS_ENCRYPTION_KEY = 'attachments_encryption_key';

    public function getTable(): string
    {
        return self::TABLE;
    }

    /**
     * @throws RepositoryException
     */
    public function delete(string $uuid): void
    {
        try {
            $builder = $this->getBuilder();
            $builder->where(self::FIELD_UUID, '=', $uuid);
            $builder->delete();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }
    }

    /**
     * @throws RepositoryException
     */
    public function deleteExpired(): void
    {
        try {
            $builder = $this->getBuilder();
            $builder->where(self::FIELD_EXPIRES_AT, '<', CarbonImmutable::now());
            $builder->orWhereNull([self::FIELD_ALIAS_UUID, self::FIELD_MAILBOX_UUID]);
            $builder->limit(1000);
            $builder->delete();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }
    }

    /**
     * @return Collection<Message>
     *
     * @throws RepositoryException
     */
    public function getByAliasUuid(string $aliasUuid): Collection
    {
        try {
            $builder = $this->getBuilder();
            $builder->select(sprintf('%s.*', self::TABLE));
            $builder->addSelect(
                $builder->raw(
                    sprintf(
                        'COUNT(%s.%s) as %s',
                        DatabaseAttachmentRepository::TABLE,
                        DatabaseAttachmentRepository::FIELD_UUID,
                        self::FIELD_ATTACHMENT_COUNT,
                    )
                )
            );
            $builder->leftJoin(
                DatabaseAttachmentRepository::TABLE,
                static function (JoinClause $join): void {
                    $join->on(
                        sprintf(
                            '%s.%s',
                            DatabaseAttachmentRepository::TABLE,
                            DatabaseAttachmentRepository::FIELD_MESSAGE_UUID,
                        ),
                        '=',
                        sprintf('%s.%s', self::TABLE, self::FIELD_UUID),
                    );
                }
            );
            $builder->where(self::FIELD_ALIAS_UUID, '=', $aliasUuid);
            $builder->where(static function (Builder $builder): void {
                $builder->whereNull(self::FIELD_EXPIRES_AT);
                $builder->orWhere(self::FIELD_EXPIRES_AT, '>', CarbonImmutable::now());
            });
            $builder->groupBy(self::FIELD_UUID);
            $builder->orderBy(self::FIELD_CREATED_AT, 'desc');
            $results = $builder->get();
        } catch (QueryException $queryException) {
            $this->logger->debug('message getByAliasUuid failed', ['error' => $queryException->getMessage()]);
            throw RepositoryException::fromThrowable($queryException);
        }

        return $this->convertToMessages($results);
    }

    /**
     * @return Collection<Message>
     *
     * @throws RepositoryException
     */
    public function getMessagesByPseudoBsn(string $pseudoBsn): Collection
    {
        try {
            $builder = $this->getBuilder();
            $builder->select(sprintf('%s.*', self::TABLE));
            $builder->addSelect(
                $builder->raw(
                    sprintf(
                        'COUNT(%s.%s) as %s',
                        DatabaseAttachmentRepository::TABLE,
                        DatabaseAttachmentRepository::FIELD_UUID,
                        self::FIELD_ATTACHMENT_COUNT,
                    )
                )
            );
            $builder->join(
                DatabaseMailboxRepository::TABLE,
                static function (JoinClause $join): void {
                    $join->on(
                        sprintf('%s.%s', DatabaseMailboxRepository::TABLE, DatabaseMailboxRepository::FIELD_UUID),
                        '=',
                        sprintf('%s.%s', self::TABLE, self::FIELD_MAILBOX_UUID),
                    );
                }
            );
            $builder->leftJoin(
                DatabaseAttachmentRepository::TABLE,
                static function (JoinClause $join): void {
                    $join->on(
                        sprintf(
                            '%s.%s',
                            DatabaseAttachmentRepository::TABLE,
                            DatabaseAttachmentRepository::FIELD_MESSAGE_UUID,
                        ),
                        '=',
                        sprintf('%s.%s', self::TABLE, self::FIELD_UUID),
                    );
                }
            );
            $builder->where(
                sprintf(
                    '%s.%s',
                    DatabaseMailboxRepository::TABLE,
                    DatabaseMailboxRepository::FIELD_PSEUDO_BSN
                ),
                '=',
                $pseudoBsn
            );
            $builder->where(static function (Builder $builder): void {
                $builder->whereNull(self::FIELD_EXPIRES_AT);
                $builder->orWhere(self::FIELD_EXPIRES_AT, '>', CarbonImmutable::now());
            });
            $builder->groupBy(self::FIELD_UUID);
            $builder->orderBy(self::FIELD_CREATED_AT, 'desc');
            $this->logger->info($builder->toSql());
            $results = $builder->get();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }

        return $this->convertToMessages($results);
    }

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByUuid(string $uuid): Message
    {
        try {
            $builder = $this->getBuilder();
            $builder->select(sprintf('%s.*', self::TABLE));
            $builder->addSelect(
                sprintf('%s.%s', DatabaseMailboxRepository::TABLE, DatabaseMailboxRepository::FIELD_PSEUDO_BSN)
            );
            $builder->addSelect(
                $builder->raw(
                    sprintf(
                        'COUNT(%s.%s) as %s',
                        DatabaseAttachmentRepository::TABLE,
                        DatabaseAttachmentRepository::FIELD_UUID,
                        self::FIELD_ATTACHMENT_COUNT,
                    )
                )
            );
            $builder->leftJoin(
                sprintf('%s', DatabaseMailboxRepository::TABLE),
                static function (JoinClause $join): void {
                    $join->on(
                        sprintf('%s.%s', DatabaseMailboxRepository::TABLE, DatabaseMailboxRepository::FIELD_UUID),
                        '=',
                        sprintf('%s.%s', self::TABLE, self::FIELD_MAILBOX_UUID)
                    );
                }
            );
            $builder->leftJoin(
                DatabaseAttachmentRepository::TABLE,
                static function (JoinClause $join): void {
                    $join->on(
                        sprintf(
                            '%s.%s',
                            DatabaseAttachmentRepository::TABLE,
                            DatabaseAttachmentRepository::FIELD_MESSAGE_UUID,
                        ),
                        '=',
                        sprintf('%s.%s', self::TABLE, self::FIELD_UUID),
                    );
                }
            );
            $builder->where(sprintf('%s.%s', self::TABLE, self::FIELD_UUID), '=', $uuid);
            $builder->where(static function (Builder $builder): void {
                $builder->whereNull(self::FIELD_EXPIRES_AT);
                $builder->orWhere(self::FIELD_EXPIRES_AT, '>', CarbonImmutable::now());
            });
            $builder->groupBy(self::FIELD_UUID);
            $result = $builder->first();
        } catch (QueryException $queryException) {
            $this->logger->debug('getByUuid failed', ['error' => $queryException->getMessage()]);
            throw RepositoryException::fromThrowable($queryException);
        }

        return $this->convertToMessage($result);
    }

    /**
     * @throws RepositoryException
     */
    public function save(Message $message): void
    {
        $this->logger->debug('saving message', ['messageUuid' => $message->uuid]);

        try {
            $createdAt = $message->createdAt;

            $builder = $this->getBuilder();
            $builder->updateOrInsert([self::FIELD_UUID => $message->uuid], [
                self::FIELD_PLATFORM => $message->platform,
                self::FIELD_ALIAS_UUID => $message->aliasUuid,
                self::FIELD_MAILBOX_UUID => $message->mailboxUuid,
                self::FIELD_IDENTITY_REQUIRED => $message->identityRequired,
                self::FIELD_FROM_NAME => $this->seal($message->fromName, $createdAt),
                self::FIELD_FROM_EMAIL => $this->seal($message->fromEmail, $createdAt),
                self::FIELD_TO_NAME => $this->seal($message->toName, $createdAt),
                self::FIELD_TO_EMAIL => $this->seal($message->toEmail, $createdAt),
                self::FIELD_TO_EMAIL_HASH => $this->hash($message->toEmail),
                self::FIELD_ATTACHMENTS_ENCRYPTION_KEY => $this->seal($message->attachmentsEncryptionKey, $createdAt),
                self::FIELD_PHONE_NUMBER => $this->seal($message->phoneNumber, $createdAt),
                self::FIELD_SUBJECT => $this->seal($message->subject, $createdAt),
                self::FIELD_TEXT => $this->seal($message->text, $createdAt),
                self::FIELD_FOOTER => $this->seal($message->footer, $createdAt),
                self::FIELD_EXPIRES_AT => $message->expiresAt,
                self::FIELD_NOTIFICATION_SENT_AT => $message->notificationSentAt,
                self::FIELD_FIRST_READ_AT => $message->firstReadAt,
                self::FIELD_OTP_INCORRECT_PHONE_AT => $message->otpIncorrectPhoneAt,
                self::FIELD_CREATED_AT => $createdAt,
                self::FIELD_UPDATED_AT => CarbonImmutable::now(),
            ]);
            $this->logger->debug('message saved', ['messageUuid' => $message->uuid]);
        } catch (QueryException $queryException) {
            $this->logger->debug('message save failed', ['error' => $queryException->getMessage()]);
            throw new RepositoryException(
                sprintf('%s (code: %s)', $queryException->getMessage(), $queryException->getCode()),
                0,
                $queryException,
            );
        }
    }

    /**
     * @throws RepositoryException
     */
    public function updateMailboxUuidByAliasUuid(?string $mailboxUuid, string $aliasUuid): void
    {
        try {
            $builder = $this->getBuilder();
            $builder->where(self::FIELD_ALIAS_UUID, '=', $aliasUuid);
            $updateCount = $builder->update([
                    self::FIELD_MAILBOX_UUID => $mailboxUuid,
                    self::FIELD_UPDATED_AT => CarbonImmutable::now(),
                ]);
            $this->logger->debug('message update success', ['updateCount' => $updateCount]);
        } catch (QueryException $queryException) {
            $this->logger->debug('message update failed', ['error' => $queryException->getMessage()]);
            throw RepositoryException::fromThrowable($queryException);
        }
    }

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    private function convertToMessage(?object $result): Message
    {
        if ($result === null) {
            throw new EntityNotFoundException('Message not found');
        }

        try {
            return new Message(
                $result->{self::FIELD_UUID},
                $result->{self::FIELD_PLATFORM},
                $result->{self::FIELD_ALIAS_UUID},
                $result->{self::FIELD_MAILBOX_UUID},
                $this->unseal($result->{self::FIELD_FROM_NAME}),
                $this->unseal($result->{self::FIELD_FROM_EMAIL}),
                $this->unseal($result->{self::FIELD_TO_NAME}),
                $this->unseal($result->{self::FIELD_TO_EMAIL}),
                $this->unsealOptional($result->{self::FIELD_PHONE_NUMBER}),
                $this->unseal($result->{self::FIELD_SUBJECT}),
                $this->unseal($result->{self::FIELD_TEXT}),
                $this->unseal($result->{self::FIELD_FOOTER}),
                $this->unsealOptional($result->{self::FIELD_ATTACHMENTS_ENCRYPTION_KEY}),
                $this->convertStringToDate($result->{self::FIELD_EXPIRES_AT}),
                $this->convertStringToDate($result->{self::FIELD_CREATED_AT}, true),
                (bool) $result->{self::FIELD_IDENTITY_REQUIRED},
                $this->convertStringToDate($result->{self::FIELD_NOTIFICATION_SENT_AT}),
                $this->convertStringToDate($result->{self::FIELD_FIRST_READ_AT}),
                $this->convertStringToDate($result->{self::FIELD_OTP_INCORRECT_PHONE_AT}),
                $result->{self::FIELD_ATTACHMENT_COUNT},
            );
        } catch (EncryptionException $encryptionException) {
            $this->logger->error('decryption of message-data failed', [
                'uuid' => $result->{self::FIELD_UUID},
                'exceptionCode' => $encryptionException->getCode(),
                'exceptionMessage' => $encryptionException->getMessage(),
            ]);

            throw RepositoryException::fromThrowable($encryptionException);
        }
    }

    /**
     * @param Collection<object> $results
     *
     * @return Collection<Message>
     */
    private function convertToMessages(Collection $results): Collection
    {
        $messages = new Collection();

        foreach ($results as $result) {
            try {
                $messages->push($this->convertToMessage($result));
            } catch (RepositoryException) {
                // skip this message and continue
            }
        }

        return $messages;
    }
}
