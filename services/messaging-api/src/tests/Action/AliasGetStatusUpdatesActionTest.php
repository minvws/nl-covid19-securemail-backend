<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Tests\Action;

use Carbon\CarbonImmutable;
use MinVWS\MessagingApi\Repository\AliasReadRepository;
use MinVWS\MessagingApi\Tests\TestHelper\GetAliasFactory;

class AliasGetStatusUpdatesActionTest extends ActionTestCase
{
    public function testGetStatusUpdates(): void
    {
        $since = CarbonImmutable::now()->format('c');
        $limit = 2;

        $alias1 = GetAliasFactory::generateModel('alias1');
        $alias2 = GetAliasFactory::generateModel('alias2');

        $aliasReadRepository = $this->mock(AliasReadRepository::class);
        $aliasReadRepository->method('countStatusUpdates')
            ->with(CarbonImmutable::createFromFormat('c', $since))
            ->willReturn(3);
        $aliasReadRepository->method('getStatusUpdates')
            ->with(CarbonImmutable::createFromFormat('c', $since), $limit)
            ->willReturn([
                $alias1,
                $alias2,
            ]);

        $response = $this->getAuthorized('api/v1/aliases/statusupdates', [
            'since' => $since,
            'limit' => $limit,
        ]);
        $expectedResponseBody = [
            'total' => 3,
            'count' => 2,
            'aliases' => [
                [
                    'id' => 'alias1',
                    'updatedAt' => $alias1->updatedAt->format('c'),
                    'status' => $alias1->status->getValue(),
                    'pseudoPrimaryIdentifier' => $alias1->digidIdentifier,
                    'pseudoSecondaryIdentifier' => null,
                ],
                [
                    'id' => 'alias2',
                    'updatedAt' => $alias2->updatedAt->format('c'),
                    'status' => $alias2->status->getValue(),
                    'pseudoPrimaryIdentifier' => $alias2->digidIdentifier,
                    'pseudoSecondaryIdentifier' => null,
                ],
            ],
        ];

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonDataFromResponse($expectedResponseBody, $response);
    }

    public function testGetStatusUpdatesWithoutLimit(): void
    {
        $since = CarbonImmutable::now()->format('c');

        $this->mock(AliasReadRepository::class)
            ->method('getStatusUpdates')
            ->with(CarbonImmutable::createFromFormat('c', $since), null)
            ->willReturn([GetAliasFactory::generateModel()]);

        $response = $this->getAuthorized('api/v1/aliases/statusupdates', [
            'since' => $since,
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetStatusUpdatesInvalidDate(): void
    {
        $since = 'invalidDateString';

        $response = $this->getAuthorized('api/v1/aliases/statusupdates', [
            'since' => $since,
        ]);

        $this->assertEquals(422, $response->getStatusCode());
    }
}
