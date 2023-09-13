<?php

declare(strict_types=1);

use App\Application\Request\RequestModifier;
use MinVWS\Bridge\Server\Destinations\HttpDestination;
use MinVWS\Bridge\Server\Sources\RedisSource;

use function DI\autowire;
use function DI\get;

return [
    // attachments
    [
        'name' => 'attachment-by-uuid',
        'description' => 'Get attachment by uuid',
        'source' => autowire(RedisSource::class)
            ->constructorParameter('key', 'attachment-by-uuid'),
        'destination' => autowire(HttpDestination::class)
            ->constructorParameter('client', get('messagingAppGuzzleClient'))
            ->constructorParameter('method', 'GET')
            ->constructorParameter('path', 'api/v1/attachments/{attachmentUuid}')
            ->method('setRequestModifier', RequestModifier::getLanesRequestModifier()),
    ],
    // message
    [
        'name' => 'messages',
        'description' => 'Get messages',
        'source' => autowire(RedisSource::class)
            ->constructorParameter('key', 'messages'),
        'destination' => autowire(HttpDestination::class)
            ->constructorParameter('client', get('messagingAppGuzzleClient'))
            ->constructorParameter('method', 'GET')
            ->constructorParameter('path', 'api/v1/messages')
            ->method('setRequestModifier', RequestModifier::getLanesRequestModifier()),
    ],
    [
        'name' => 'messages-authentication-properties',
        'description' => 'Get message authentication properties',
        'source' => autowire(RedisSource::class)
            ->constructorParameter('key', 'messages-authentication-properties'),
        'destination' => autowire(HttpDestination::class)
            ->constructorParameter('client', get('messagingAppGuzzleClient'))
            ->constructorParameter('method', 'GET')
            ->constructorParameter('path', 'api/v1/messages/authentication-properties/{messageUuid}')
            ->method('setRequestModifier', RequestModifier::getLanesRequestModifier()),
    ],
    [
        'name' => 'messages-by-uuid',
        'description' => 'Get message preview by uuid',
        'source' => autowire(RedisSource::class)
            ->constructorParameter('key', 'messages-by-uuid'),
        'destination' => autowire(HttpDestination::class)
            ->constructorParameter('client', get('messagingAppGuzzleClient'))
            ->constructorParameter('method', 'GET')
            ->constructorParameter('path', 'api/v1/messages/{uuid}')
            ->method('setRequestModifier', RequestModifier::getLanesRequestModifier()),
    ],
    [
        'name' => 'messages-link',
        'description' => 'Link message to alias',
        'source' => autowire(RedisSource::class)
            ->constructorParameter('key', 'messages-link'),
        'destination' => autowire(HttpDestination::class)
            ->constructorParameter('client', get('messagingAppGuzzleClient'))
            ->constructorParameter('method', 'POST')
            ->constructorParameter('path', 'api/v1/message/link')
            ->method('setRequestModifier', RequestModifier::getLanesRequestModifier()),
    ],
    [
        'name' => 'messages-incorrect-phone',
        'description' => 'Report incorrect phone number for message',
        'source' => autowire(RedisSource::class)
            ->constructorParameter('key', 'messages-incorrect-phone'),
        'destination' => autowire(HttpDestination::class)
            ->constructorParameter('client', get('messagingAppGuzzleClient'))
            ->constructorParameter('method', 'POST')
            ->constructorParameter('path', 'api/v1/messages/incorrect-phone')
            ->method('setRequestModifier', RequestModifier::getLanesRequestModifier()),
    ],
    [
        'name' => 'messages-unlink',
        'description' => 'Unlink message from user',
        'source' => autowire(RedisSource::class)
            ->constructorParameter('key', 'messages-unlink'),
        'destination' => autowire(HttpDestination::class)
            ->constructorParameter('client', get('messagingAppGuzzleClient'))
            ->constructorParameter('method', 'POST')
            ->constructorParameter('path', 'api/v1/message/unlink')
            ->method('setRequestModifier', RequestModifier::getLanesRequestModifier()),
    ],

    // otp-code
    [
        'name' => 'get-otp-code',
        'description' => 'Get otp-code options',
        'source' => autowire(RedisSource::class)
            ->constructorParameter('key', 'get-otp-code'),
        'destination' => autowire(HttpDestination::class)
            ->constructorParameter('client', get('messagingAppGuzzleClient'))
            ->constructorParameter('method', 'GET')
            ->constructorParameter('path', 'api/v1/otp-code/options')
            ->method('setRequestModifier', RequestModifier::getLanesRequestModifier()),
    ],
    [
        'name' => 'request-otp-code',
        'description' => 'Request otp-code',
        'source' => autowire(RedisSource::class)
            ->constructorParameter('key', 'request-otp-code'),
        'destination' => autowire(HttpDestination::class)
            ->constructorParameter('client', get('messagingAppGuzzleClient'))
            ->constructorParameter('method', 'POST')
            ->constructorParameter('path', 'api/v1/otp-code/{messageUuid}/{type}')
            ->method('setRequestModifier', RequestModifier::getLanesRequestModifier()),
    ],

    // pairing-code
    [
        'name' => 'pairing-code',
        'description' => 'Post pairing-code (validate)',
        'source' => autowire(RedisSource::class)
            ->constructorParameter('key', 'pairing-code'),
        'destination' => autowire(HttpDestination::class)
            ->constructorParameter('client', get('messagingAppGuzzleClient'))
            ->constructorParameter('method', 'POST')
            ->constructorParameter('path', 'api/v1/pairing-code')
            ->method('setRequestModifier', RequestModifier::getLanesRequestModifier()),
    ],
    [
        'name' => 'pairing-code-by-uuid',
        'description' => 'Get pairing-code by uuid',
        'source' => autowire(RedisSource::class)
            ->constructorParameter('key', 'pairing-code-by-uuid'),
        'destination' => autowire(HttpDestination::class)
            ->constructorParameter('client', get('messagingAppGuzzleClient'))
            ->constructorParameter('method', 'GET')
            ->constructorParameter('path', 'api/v1/pairing-code/{uuid}')
            ->method('setRequestModifier', RequestModifier::getLanesRequestModifier()),
    ],
    [
        'name' => 'pairing-code-renew',
        'description' => 'Post pairing-code to renew expired',
        'source' => autowire(RedisSource::class)
            ->constructorParameter('key', 'pairing-code-renew'),
        'destination' => autowire(HttpDestination::class)
            ->constructorParameter('client', get('messagingAppGuzzleClient'))
            ->constructorParameter('method', 'POST')
            ->constructorParameter('path', 'api/v1/pairing-code/renew')
            ->method('setRequestModifier', RequestModifier::getLanesRequestModifier()),
    ],
];
