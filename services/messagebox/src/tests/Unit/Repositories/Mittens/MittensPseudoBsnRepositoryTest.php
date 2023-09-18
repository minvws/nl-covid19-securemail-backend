<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Mittens;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\NullLogger;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use SecureMail\Shared\Application\Repositories\Mittens\MittensPseudoBsnRepository;
use Tests\TestCase;

use function array_merge;
use function config;
use function json_encode;

/**
 * @group bsn
 */
class MittensPseudoBsnRepositoryTest extends TestCase
{
    public function testGetByBsn(): void
    {
        $bsn = 'foo';
        $responseGuid = '9fc3e93e-e24d-4064-5717-7b4b41cb8993';
        $responseCensoredBsn = 'bar';
        $responseLetters = 'EJ';

        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(202, ['Content-Type' => 'application/json'], json_encode([
                'data' => [
                    (object) [
                        'guid' => $responseGuid,
                        'censored_bsn' => $responseCensoredBsn,
                        'letters' => $responseLetters,
                    ],
                ]
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(array_merge(config('services.mittens.client_options'), ['handler' => $handlerStack]));

        $digidAccessToken = 'digidAccessToken';
        $mittensPseudoBsnRepository = $this->getMittensPseudoBsnRepository($client, $digidAccessToken);
        $mittensDigidIdentifier = $mittensPseudoBsnRepository->getByBsn($bsn);

        $this->assertEquals($responseGuid, $mittensDigidIdentifier);

        /** @var Request $request */
        $request = $container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/service/via_digid', $request->getUri()->getPath());
        $this->assertEquals('application/json', $request->getHeaders()['Accept'][0]);
        $this->assertEquals('application/json', $request->getHeaders()['Content-Type'][0]);

        $expectedRequestBody = [
            'digid_access_token' => $digidAccessToken,
            'BSN' => $bsn,
        ];
        $this->assertEquals(json_encode($expectedRequestBody), $request->getBody()->getContents());
    }

    public function testGetByBsnNoResults(): void
    {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(202, ['Content-Type' => 'application/json'], json_encode([
                'data' => [],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $mittensPseudoBsnRepository = $this->getMittensPseudoBsnRepository($client, 'foo');

        $this->expectException(RepositoryException::class);
        $mittensPseudoBsnRepository->getByBsn('bar');
    }

    public function testGetByBsnTwoResults(): void
    {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(202, ['Content-Type' => 'application/json'], json_encode([
                'data' => [
                    (object) ['guid' => '', 'censored_bsn' => '', 'letters' => ''],
                    (object) ['guid' => '', 'censored_bsn' => '', 'letters' => ''],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $mittensPseudoBsnRepository = $this->getMittensPseudoBsnRepository($client, 'foo');

        $this->expectException(RepositoryException::class);
        $mittensPseudoBsnRepository->getByBsn('bar');
    }

    public function testGetByBsnRequestException(): void
    {
        $exceptionMessage = 'some random error';

        $mock = new MockHandler([
                new ClientException(
                    $exceptionMessage,
                    new Request('POST', ''),
                    new Response(401, [], json_encode(['errors' => [$exceptionMessage]]))
                )
            ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $mittensPseudoBsnRepository = $this->getMittensPseudoBsnRepository($client);
        $mittensPseudoBsnRepository->getByBsn('foo');
    }

    public function testGetByBsnRequestServerException(): void
    {
        $exceptionMessage = 'some random error';

        $mock = new MockHandler([
            new ServerException($exceptionMessage, new Request('POST', ''), new Response(401, [], json_encode(['errors' => [$exceptionMessage]]))),
            new ServerException($exceptionMessage, new Request('POST', ''), new Response(401, [], json_encode(['errors' => [$exceptionMessage]]))),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('service unavailable');

        $mittensPseudoBsnRepository = $this->getMittensPseudoBsnRepository($client);
        $mittensPseudoBsnRepository->getByBsn('foo');
    }

    private function getMittensPseudoBsnRepository(
        Client $client,
        string $digidAccessToken = 'digid_access_token',
    ): MittensPseudoBsnRepository {
        return new MittensPseudoBsnRepository($client, $digidAccessToken, new NullLogger());
    }
}
