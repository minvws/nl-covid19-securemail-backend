<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Repository\Database;

use Carbon\CarbonInterface;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\QueryException;
use MinVWS\MessagingApi\Model\GetMessage;
use MinVWS\MessagingApi\Repository\EntityNotFoundException;
use MinVWS\MessagingApi\Repository\MessageReadRepository;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

use function is_object;
use function sprintf;

class DatabaseMessageRepository extends DatabaseRepository implements MessageReadRepository
{
    public const TABLE = 'message';
    public const FIELD_BOUNCED_AT = 'bounced_at';
    public const FIELD_DIGID_AUTH_FAILED_AT = 'digid_auth_failed_at';
    public const FIELD_EXPIRED_AT = 'expired_at';
    public const FIELD_FIRST_READ_AT = 'first_read_at';
    public const FIELD_MAILBOX_UUID = 'mailbox_uuid';
    public const FIELD_IS_READ = 'is_read';
    public const FIELD_NOTIFICATION_SENT_AT = 'notification_sent_at';
    public const FIELD_PAIRING_CODE_PAIRED_AT = 'pairing_code_paired_at';
    public const FIELD_OTP_AUTH_FAILED_AT = 'otp_auth_failed_at';
    public const FIELD_OTP_INCORRECT_PHONE_AT = 'otp_incorrect_phone_at';
    public const FIELD_RECEIVED_AT = 'received_at';
    public const FIELD_REVOKED_AT = 'revoked_at';
    public const FIELD_UUID = 'uuid';

    public function getTable(): string
    {
        return self::TABLE;
    }

    /**
     * @throws RepositoryException
     */
    public function countStatusUpdates(CarbonInterface $since): int
    {
        try {
            $builder = $this->getBuilder();
            $builder->where(self::FIELD_UPDATED_AT, '>', $this->convertDateToString($since));
            return $builder->count();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }
    }

    /**
     * @return GetMessage[]
     *
     * @throws RepositoryException
     */
    public function getStatusUpdates(CarbonInterface $since, ?int $limit): array
    {
        try {
            $builder = $this->getBuilder();
            $builder->addSelect(sprintf('%s.*', self::TABLE));
            $builder->addSelect(sprintf(
                '%s.%s AS %s',
                DatabasePairingCodeRepository::TABLE,
                DatabasePairingCodeRepository::FIELD_PAIRED_AT,
                self::FIELD_PAIRING_CODE_PAIRED_AT,
            ));
            $builder->leftJoin(
                sprintf('%s AS pairing_code', DatabasePairingCodeRepository::TABLE),
                static function (JoinClause $join): void {
                    $join->on(
                        sprintf('pairing_code.%s', DatabasePairingCodeRepository::FIELD_MESSAGE_UUID),
                        '=',
                        sprintf('%s.%s', self::TABLE, self::FIELD_UUID)
                    );
                }
            );
            $builder->where(
                sprintf('%s.%s', self::TABLE, self::FIELD_UPDATED_AT),
                '>',
                $this->convertDateToString($since)
            );
            $builder->orderBy(sprintf('%s.%s', self::TABLE, self::FIELD_UPDATED_AT));

            if ($limit !== null) {
                $builder->limit($limit);
            }

            $results = $builder->get();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }

        $messages = [];
        foreach ($results as $result) {
            if (is_object($result)) {
                $messages[] = $this->convertToMessage($result);
            }
        }

        return $messages;
    }

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    private function convertToMessage(?object $result): ?GetMessage
    {
        if ($result === null) {
            throw new EntityNotFoundException('Message not found');
        }

        return new GetMessage(
            $result->{self::FIELD_UUID},
            $this->convertStringToDate($result->{self::FIELD_NOTIFICATION_SENT_AT}),
            $this->convertStringToDate($result->{self::FIELD_RECEIVED_AT}),
            $this->convertStringToDate($result->{self::FIELD_BOUNCED_AT}),
            $this->convertStringToDate($result->{self::FIELD_OTP_AUTH_FAILED_AT}),
            $this->convertStringToDate($result->{self::FIELD_OTP_INCORRECT_PHONE_AT}),
            $this->convertStringToDate($result->{self::FIELD_DIGID_AUTH_FAILED_AT}),
            $this->convertStringToDate($result->{self::FIELD_FIRST_READ_AT}),
            $this->convertStringToDate($result->{self::FIELD_REVOKED_AT}),
            $this->convertStringToDate($result->{self::FIELD_EXPIRED_AT}),
        );
    }
}
