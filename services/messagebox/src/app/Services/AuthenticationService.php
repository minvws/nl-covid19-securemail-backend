<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Enums\LoginType;
use App\Models\MessageBoxUser;
use App\Models\OtpCode;
use App\Models\PairingCode;
use App\Models\User;
use App\Repositories\MessageRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class AuthenticationService
{
    public const SESSION_AUTHENTICATION_PAIRING_CODE = 'authentication.pairing_code';
    public const SESSION_AUTHENTICATION_PSEUDO_BSN = 'authentication.pseudo_bsn';
    public const SESSION_AUTHENTICATION_USER = 'authentication.user';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MessageRepository $messageRepository,
        private readonly Session $session,
    ) {
    }

    /**
     * @return Collection<LoginType>
     */
    public function getLoginTypes(PairingCode $pairingCode): Collection
    {
        $loginTypes = new Collection();

        try {
            $authenticationProperties = $this->messageRepository->getAuthenticationProperties(
                $pairingCode->messageUuid
            );
        } catch (RepositoryException $repositoryException) {
            $this->logger->error($repositoryException);

            return $loginTypes;
        }

        if ($authenticationProperties->identityRequired) {
            if ($authenticationProperties->hasIdentity) {
                $loginTypes->push(LoginType::digid());
            }
        } else {
            if ($authenticationProperties->hasIdentity) {
                $loginTypes->push(LoginType::digid());
            }

            if ($authenticationProperties->hasPhoneNumber()) {
                $loginTypes->push(LoginType::sms());
            }
        }

        return $loginTypes;
    }

    public function getOtpCode(): ?OtpCode
    {
        $otpCode = $this->session->get(OtpCodeService::SESSION_KEY);

        return $otpCode instanceof OtpCode ? $otpCode : null;
    }

    public function getPairingCode(): ?PairingCode
    {
        return $this->session->get(self::SESSION_AUTHENTICATION_PAIRING_CODE);
    }

    public function hasPairingCode(): bool
    {
        return $this->session->has(self::SESSION_AUTHENTICATION_PAIRING_CODE);
    }

    /**
     * @throws AuthenticationException
     */
    public function getUser(): User
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            throw new AuthenticationException();
        }

        return $user;
    }

    public function registerPairingCode(PairingCode $pairingCode): void
    {
        $this->session->put(self::SESSION_AUTHENTICATION_PAIRING_CODE, $pairingCode);
    }

    public function loginUser(MessageBoxUser $messageBoxUser): void
    {
        $user = new User($messageBoxUser->getAuthIdentifierName(), $messageBoxUser->getAuthIdentifier());
        $this->session->put(self::SESSION_AUTHENTICATION_USER, $user);

        Auth::login($user);
    }

    /**
     * @throws AuthenticationException
     */
    public function retrieveUserFromSession(string $identifier): User
    {
        /** @var User $user */
        $user = $this->session->get(self::SESSION_AUTHENTICATION_USER);

        if (!$user instanceof User) {
            throw new AuthenticationException('No User found in session');
        }

        if ($user->getAuthIdentifier() !== $identifier) {
            throw new AuthenticationException('User in session does not match given identifier');
        }

        return $user;
    }

    public function logout(): void
    {
        Auth::logout();
        $this->session->flush();
    }
}
