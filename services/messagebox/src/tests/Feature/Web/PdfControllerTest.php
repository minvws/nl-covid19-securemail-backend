<?php

namespace Tests\Feature\Web;

use App\Models\User;
use App\Repositories\MessageRepository;
use App\Services\AuthenticationService;
use Mockery\MockInterface;
use Tests\Feature\ControllerTestCase;

use function sprintf;

class PdfControllerTest extends ControllerTestCase
{
    public function testDownloadMessagePdf(): void
    {
        $pseudoBsn = $this->faker->uuid;
        $message = $this->createMessage();
        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($message, $pseudoBsn): void {
            $mock->shouldReceive('getByUuidAndPseudoBsn')
                ->once()
                ->with($message->uuid, $pseudoBsn)
                ->andReturn($message);
        });

        $user = new User(User::AUTH_DIGID, $pseudoBsn);
        $response = $this->actingAs($user)
            ->withSession([AuthenticationService::SESSION_AUTHENTICATION_PSEUDO_BSN => $pseudoBsn])
            ->get(sprintf('messages/%s/pdf', $message->uuid));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
        $this->assertEquals(
            sprintf('attachment; filename="Bericht %s.pdf"', $message->subject),
            $response->headers->get('Content-Disposition'),
        );
    }
}
