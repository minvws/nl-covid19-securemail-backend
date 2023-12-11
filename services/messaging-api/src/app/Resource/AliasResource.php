<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Resource;

use Carbon\CarbonInterface;
use MinVWS\MessagingApi\Model\GetAlias;

use function array_map;

class AliasResource
{
    public function convert(GetAlias $alias): array
    {
        return [
            'id' => $alias->uuid,
            'updatedAt' => $this->convertDate($alias->updatedAt),
            'status' => $alias->status->getValue(),
            'pseudoPrimaryIdentifier' => $alias->digidIdentifier,
            'pseudoSecondaryIdentifier' => null,
        ];
    }

    /**
     * @param GetAlias[] $aliases
     */
    public function convertCollection(array $aliases): array
    {
        return array_map([$this, 'convert'], $aliases);
    }

    private function convertDate(?CarbonInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return $date->format('c');
    }
}
