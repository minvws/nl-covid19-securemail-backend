<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Repository\Database;

use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use MinVWS\MessagingApp\Model\OtpCode;
use MinVWS\MessagingApp\Repository\EntityNotFoundException;
use MinVWS\MessagingApp\Repository\OtpCodeRepository;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class DatabaseOtpCodeRepository extends DatabaseRepository implements OtpCodeRepository
{
    public const TABLE = 'otp_code';
    public const FIELD_UUID = 'uuid';
    public const FIELD_MESSAGE_UUID = 'message_uuid';
    public const FIELD_TYPE = 'type';
    public const FIELD_CODE = 'code';
    public const FIELD_VALID_UNTIL = 'valid_until';

    protected function getTable(): string
    {
        return self::TABLE;
    }

    /**
     * @throws RepositoryException
     */
    public function delete(OtpCode $otpCode): void
    {
        try {
            $builder = $this->getBuilder();
            $builder
                ->where('uuid', $otpCode->uuid)
                ->delete();
            $this->logger->debug('otp_code deleted', ['otpCodeUuid' => $otpCode->uuid]);
        } catch (QueryException $queryException) {
            $this->logger->debug('otp_code delete failed', ['error' => $queryException->getMessage()]);
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
            $builder->where(self::FIELD_VALID_UNTIL, '<', CarbonImmutable::now());
            $builder->orWhereNull(self::FIELD_MESSAGE_UUID);
            $builder->limit(1000);
            $builder->delete();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }
    }

    /**
     * @throws RepositoryException
     */
    public function getByMessageUuidAndCode(string $messageUuid, string $code): OtpCode
    {
        try {
            $builder = $this->getBuilder();
            $builder->where(self::FIELD_MESSAGE_UUID, '=', $messageUuid);
            $builder->where(self::FIELD_CODE, '=', $code);
            $builder->where(self::FIELD_VALID_UNTIL, '>', CarbonImmutable::now());
            $result = $builder->first();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }

        return $this->convertToOtpCode($result);
    }

    /**
     * @throws RepositoryException
     */
    public function getByUuid(string $uuid): OtpCode
    {
        try {
            $builder = $this->getBuilder();
            $builder->where(self::FIELD_UUID, '=', $uuid);
            $builder->where(self::FIELD_VALID_UNTIL, '>', CarbonImmutable::now());
            $result = $builder->first();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }

        return $this->convertToOtpCode($result);
    }

    /**
     * @throws RepositoryException
     */
    public function save(OtpCode $otpCode): void
    {
        try {
            $builder = $this->getBuilder();
            $builder->insert(
                [
                    self::FIELD_UUID => $otpCode->uuid,
                    self::FIELD_MESSAGE_UUID => $otpCode->messageUuid,
                    self::FIELD_TYPE => $otpCode->type,
                    self::FIELD_CODE => $otpCode->code,
                    self::FIELD_VALID_UNTIL => $otpCode->validUntil,
                    self::FIELD_CREATED_AT => CarbonImmutable::now(),
                    self::FIELD_UPDATED_AT => CarbonImmutable::now(),
                ]
            );
            $this->logger->debug('otp_code saved', ['otpCodeUuid' => $otpCode->uuid]);
        } catch (QueryException $queryException) {
            $this->logger->debug('otp_code save failed', ['error' => $queryException->getMessage()]);
            throw RepositoryException::fromThrowable($queryException);
        }
    }

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    private function convertToOtpCode(?object $result): OtpCode
    {
        if ($result === null) {
            throw new EntityNotFoundException('OtpCode not found');
        }

        return new OtpCode(
            $result->{self::FIELD_UUID},
            $result->{self::FIELD_MESSAGE_UUID},
            $result->{self::FIELD_TYPE},
            $result->{self::FIELD_CODE},
            $this->convertStringToDate($result->{self::FIELD_VALID_UNTIL})
        );
    }

    public function getByMessageUuid(string $messageUuid): array
    {
        $otpModels = [];
        try {
            $builder = $this->getBuilder();
            $builder->where(self::FIELD_MESSAGE_UUID, '=', $messageUuid);
            foreach ($builder->get()->all() as $otpModel) {
                $otpModels[] = $this->convertToOtpCode($otpModel);
            }
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }

        return $otpModels;
    }
}
