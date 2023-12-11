<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Enums\LoginType;
use App\Models\OtpCode;
use App\Models\PairingCode;
use App\Repositories\MessageRepository;
use App\Repositories\OtpCodeRepository;
use Illuminate\Contracts\Session\Session;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class OtpCodeService
{
    public const SESSION_KEY = 'otp_code';

    public function __construct(
        private readonly MessageRepository $messageRepository,
        private readonly OtpCodeRepository $otpCodeRepository,
        private readonly Session $session,
    ) {
    }

    public function clear(): void
    {
        $this->session->remove(self::SESSION_KEY);
    }

    /**
     * @throws RepositoryException
     */
    public function getInfo(PairingCode $pairingCode): array
    {
        $messageAuthenticationProperties = $this->messageRepository->getAuthenticationProperties(
            $pairingCode->messageUuid
        );

        return [
            'phoneNumber' => $messageAuthenticationProperties->phoneNumber,
        ];
    }

    /**
     * @throws OtpCodeException
     */
    public function request(LoginType $loginType, string $messageUuid): OtpCode
    {
        try {
            $otpCode = $this->otpCodeRepository->requestByTypeAndMessageUuid($loginType, $messageUuid);
        } catch (RepositoryException $repositoryException) {
            throw OtpCodeException::fromThrowable($repositoryException);
        }

        $this->session->put(self::SESSION_KEY, $otpCode);

        return $otpCode;
    }

    /**
     * @throws OtpCodeException
     */
    public function validate(string $messageUuid, string $postedOtpCode): void
    {
        try {
            $otpCodeUuid = $this->otpCodeRepository->getByMessageUuidAndOtpCode($messageUuid, $postedOtpCode);
        } catch (RepositoryException $repositoryException) {
            throw OtpCodeException::fromThrowable($repositoryException);
        }

        /** @var OtpCode|null $otpCodeInSession */
        $otpCodeInSession = $this->session->get(self::SESSION_KEY);
        if ($otpCodeInSession === null || $otpCodeInSession->uuid !== $otpCodeUuid) {
            throw new OtpCodeException('otp_code not found');
        }
    }

    /**
     * @throws RepositoryException
     */
    public function reportIncorrectPhone(PairingCode $pairingCode): void
    {
        $this->messageRepository->reportIncorrectPhone($pairingCode->messageUuid);
    }
}
