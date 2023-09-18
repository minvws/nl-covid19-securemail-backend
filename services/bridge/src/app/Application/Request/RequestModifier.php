<?php

declare(strict_types=1);

namespace App\Application\Request;

use DI\Definition\ValueDefinition;
use MinVWS\Bridge\Server\Models\Request as BridgeRequest;
use Psr\Http\Message\RequestInterface;

use function array_key_exists;
use function DI\value;

class RequestModifier
{
    public static function getLanesRequestModifier(): ValueDefinition
    {
        return value(function (RequestInterface $httpRequest, BridgeRequest $bridgeRequest): RequestInterface {
            if (array_key_exists('Authorization', $bridgeRequest->params)) {
                $httpRequest = $httpRequest->withHeader('Authorization', $bridgeRequest->params['Authorization']);
            }
            $httpRequest = $httpRequest->withHeader('Content-Type', 'application/json');
            return $httpRequest;
        });
    }
}
