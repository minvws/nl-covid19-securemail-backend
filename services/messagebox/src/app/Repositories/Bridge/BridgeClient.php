<?php

declare(strict_types=1);

namespace App\Repositories\Bridge;

use MinVWS\Bridge\Client\Client as MinVWSBridgeClient;
use Predis\ClientInterface;

class BridgeClient extends MinVWSBridgeClient
{
    public function __construct(ClientInterface $client)
    {
        parent::__construct($client);
    }

    public function isHealty(): bool
    {
        return true;
    }
}
