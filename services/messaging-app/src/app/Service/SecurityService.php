<?php

namespace MinVWS\MessagingApp\Service;

use DateTimeZone;
use DBCO\Shared\Application\Helpers\DateTimeHelper;
use Exception;
use Generator;
use MinVWS\DBCO\Encryption\Security\SecurityCache;
use MinVWS\DBCO\Encryption\Security\SecurityModule;
use MinVWS\DBCO\Encryption\Security\StorageTerm;
use MinVWS\DBCO\Encryption\Security\StorageTermUnit;

use function sprintf;

class SecurityService
{
    public const MUTATION_CREATED = 'CREATED';
    public const MUTATION_LOADED = 'LOADED';
    public const MUTATION_DELETED = 'DELETED';
    public const MUTATION_ERROR = 'ERROR';

    private SecurityModule $securityModule;
    private SecurityCache $securityCache;
    private DateTimeHelper $dateTimeHelper;
    private string $storeKeyTimeZone;
    private array $storageTermIntervals;

    public function __construct(
        SecurityModule $securityModule,
        SecurityCache $securityCache,
        DateTimeHelper $dateTimeHelper,
        string $storeKeyTimeZone,
        array $storageTermIntervals,
    ) {
        $this->securityModule = $securityModule;
        $this->securityCache = $securityCache;
        $this->dateTimeHelper = $dateTimeHelper;
        $this->storeKeyTimeZone = $storeKeyTimeZone;
        $this->storageTermIntervals = $storageTermIntervals;
    }

    /**
     * Cache all security module keys.
     *
     * @throws Exception
     */
    public function cacheKeys(bool $force = true): void
    {
        // check if there is already a key in the cache, in which case we assume
        // all the other keys are already cached as well (by the manage process)
        if (!$force && $this->securityCache->hasSecretKey(SecurityModule::SK_KEY_EXCHANGE)) {
            return;
        }

        $this->cacheKeysForStorageTerm(StorageTerm::short());
    }

    /**
     * Manage / rotate store secret keys.
     *
     * @throws Exception
     */
    public function manageStoreSecretKeys(
        StorageTerm $storageTerm,
        ?StorageTermUnit $previousCurrentUnit,
        callable $mutationCallback,
        bool $createMissingPastKeys = false,
    ): StorageTermUnit {
        $storageTermInterval = $this->getStorageTermInterval($storageTerm);
        $activeInterval = $storageTermInterval['activeInterval'];
        $cleanUpInterval = $storageTermInterval['cleanUpInterval'];

        $timeZone = new DateTimeZone($this->storeKeyTimeZone);
        $currentUnit = $storageTerm->unitForDateTime($this->dateTimeHelper->now($timeZone));

        if ($previousCurrentUnit !== null && $currentUnit->equals($previousCurrentUnit)) {
            return $previousCurrentUnit; // nothing to do
        }

        $reportAlreadyLoaded = $previousCurrentUnit === null;

        $oldestValidUnit = $currentUnit->sub($activeInterval);
        $oldestExpiredUnit = $oldestValidUnit->sub($cleanUpInterval);
        $newestExpiredUnit = $oldestValidUnit->previous();

        // delete expired units
        foreach ($this->iterateStorageTermUnits($oldestExpiredUnit, $newestExpiredUnit) as $unit) {
            $this->deleteStorageTermUnit($unit, $mutationCallback);
        }

        // load active units
        foreach ($this->iterateStorageTermUnits($oldestValidUnit, $currentUnit->previous()) as $unit) {
            if ($createMissingPastKeys) {
                $this->createOrloadStorageTermUnit($unit, $mutationCallback, $reportAlreadyLoaded);
            } else {
                $this->loadStorageTermUnit($unit, $mutationCallback, $reportAlreadyLoaded);
            }
        }

        // create or load current unit
        $this->createOrLoadStorageTermUnit($currentUnit, $mutationCallback, $reportAlreadyLoaded);

        // create or load next unit; create early so it is already available after midnight
        $this->createOrLoadStorageTermUnit($currentUnit->next(), $mutationCallback, $reportAlreadyLoaded);

        return $currentUnit;
    }

    private function getStorageTermInterval(StorageTerm $storageTerm): array
    {
        return $this->storageTermIntervals[(string) $storageTerm];
    }

    /**
     * @throws Exception
     */
    private function cacheKeysForStorageTerm(StorageTerm $storageTerm): void
    {
        $activeInterval = $this->getStorageTermInterval($storageTerm)['activeInterval'];

        $timeZone = new DateTimeZone($this->storeKeyTimeZone);
        $currentUnit = $storageTerm->unitForDateTime($this->dateTimeHelper->now($timeZone));
        $oldestValidUnit = $currentUnit->sub($activeInterval);

        foreach ($this->iterateStorageTermUnits($oldestValidUnit, $currentUnit) as $unit) {
            $this->loadStorageTermUnit($unit);
        }
    }

    private function identifierForStorageTermUnit(StorageTermUnit $unit): string
    {
        return sprintf(SecurityModule::SK_STORE_TEMPLATE, (string)$unit);
    }

    /**
     * Iterates over the given storage term period. Both first and last units are inclusive.
     *
     * @return Generator<StorageTermUnit>
     */
    private function iterateStorageTermUnits(StorageTermUnit $first, StorageTermUnit $last): Generator
    {
        $current = $first;

        while (true) {
            yield $current;

            if ($current->equals($last)) {
                break;
            } else {
                $current = $current->next();
            }
        }
    }

    private function deleteStorageTermUnit(StorageTermUnit $unit, callable $mutationCallback): void
    {
        try {
            $identifier = $this->identifierForStorageTermUnit($unit);

            if (!$this->securityModule->hasSecretKey($identifier)) {
                return; // already deleted
            }

            // delete secret key from the security cache and module
            $this->securityCache->deleteSecretKey($identifier);
            $this->securityModule->deleteSecretKey($identifier);
            $mutationCallback($unit, self::MUTATION_DELETED);
        } catch (Exception $e) {
            $mutationCallback($unit, self::MUTATION_ERROR, $e);
        }
    }

    private function createOrLoadStorageTermUnit(
        StorageTermUnit $unit,
        callable $mutationCallback,
        bool $reportAlreadyLoaded = false,
    ): void {
        try {
            $identifier = $this->identifierForStorageTermUnit($unit);

            if ($this->securityCache->hasSecretKey($identifier)) {
                if ($reportAlreadyLoaded) {
                    $mutationCallback($unit, self::MUTATION_LOADED);
                }

                return; // already in cache
            }

            $exists = $this->securityModule->hasSecretKey($identifier);
            if ($exists) {
                $secretKey = $this->securityModule->getSecretKey($identifier);
            } else {
                $secretKey = $this->securityModule->generateSecretKey($identifier);
            }

            $this->securityCache->setSecretKey($identifier, $secretKey);
            $mutationCallback($unit, $exists ? self::MUTATION_LOADED : self::MUTATION_CREATED);
        } catch (Exception $e) {
            $mutationCallback($unit, self::MUTATION_ERROR, $e);
        }
    }

    private function loadStorageTermUnit(
        StorageTermUnit $unit,
        ?callable $mutationCallback = null,
        bool $reportAlreadyLoaded = false,
    ): void {
        $mutationCallback = $mutationCallback ?? fn () => null;

        try {
            $identifier = $this->identifierForStorageTermUnit($unit);

            if ($this->securityCache->hasSecretKey($identifier)) {
                if ($reportAlreadyLoaded) {
                    $mutationCallback($unit, self::MUTATION_LOADED);
                }

                return; // already in cache
            }

            if (!$this->securityModule->hasSecretKey($identifier)) {
                return; // doesn't exist
            }

            // store the key in the cache
            $secretKey = $this->securityModule->getSecretKey($identifier);
            $this->securityCache->setSecretKey($identifier, $secretKey);
            $mutationCallback($unit, self::MUTATION_LOADED);
        } catch (Exception $e) {
            $mutationCallback($unit, self::MUTATION_ERROR, $e);
        }
    }
}
