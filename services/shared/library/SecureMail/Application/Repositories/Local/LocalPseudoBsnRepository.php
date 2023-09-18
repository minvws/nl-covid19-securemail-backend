<?php

declare(strict_types=1);

namespace SecureMail\Shared\Application\Repositories\Local;

use SecureMail\Shared\Application\Exceptions\EncryptionException;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use SecureMail\Shared\Application\Helpers\EncryptionHelper;
use SecureMail\Shared\Application\Models\MittensUserInfo;
use SecureMail\Shared\Application\Models\PseudoBsn;
use SecureMail\Shared\Application\Repositories\PseudoBsnRepository;

use function json_encode;

class LocalPseudoBsnRepository implements PseudoBsnRepository
{
    public function getByBsn(string $bsn): string
    {
        return $bsn;
    }

    /**
     * @throws RepositoryException
     */
    public function getByDigidToken(string $idToken): MittensUserInfo
    {
        $encryptData = json_encode([
            'censored_bsn' => '******007',
            'guid' => '0fb60aa4-1ef6-4f05-aec3-5b84b62cbcf4',
            'pc3' => '123',
            'ggd_region' => '1234',
            'birth_date' => '1950-01-02',
            'first_name' => 'Frits',
            'last_name' => 'Smits',
            'prefix' => 'Van De',
            'gender' => 'V'
        ]);

        if (!$encryptData) {
            throw new RepositoryException('Error encoding data');
        }

        try {
            $encryptedData = EncryptionHelper::encryptMittensIdentityData($encryptData);
        } catch (EncryptionException $encryptionException) {
            throw RepositoryException::fromThrowable($encryptionException);
        }

        return new MittensUserInfo(
            'Frits',
            'Van De',
            'Smits',
            'V',
            '0fb60aa4-1ef6-4f05-aec3-5b84b62cbcf4',
            $encryptedData
        );
    }

    public function getByToken(string $pseudoBsnToken): PseudoBsn
    {
        return new PseudoBsn(
            '0fb60aa4-1ef6-4f05-aec3-5b84b62cbcf4',
            '******007',
            'FS',
            null
        );
    }
}
