<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\AttachmentRepository;
use App\Repositories\Bridge\BridgeAttachmentRepository;
use App\Repositories\Bridge\BridgeMessageRepository;
use App\Repositories\Bridge\BridgeOtpCodeRepository;
use App\Repositories\Bridge\BridgePairingCodeRepository;
use App\Repositories\MessageRepository;
use App\Repositories\OtpCodeRepository;
use App\Repositories\PairingCodeRepository;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use MinVWS\Audit\Repositories\AuditRepository;
use MinVWS\Audit\Repositories\LogAuditRepository;
use MinVWS\Bridge\Client\Client as BridgeClient;
use Predis\Client;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Repositories\Local\LocalPseudoBsnRepository;
use SecureMail\Shared\Application\Repositories\Mittens\MittensPseudoBsnRepository;
use SecureMail\Shared\Application\Repositories\PseudoBsnRepository;

use function config;

class RepositoryServiceProvider extends ServiceProvider
{
    private const BRIDGE_REDIS = 'bridge-redis';

    /**
     * @throws BindingResolutionException
     */
    public function register()
    {
        switch (config('services.pseudo_bsn_service')) {
            case 'mittens':
                $this->bindMittensPseudoBsnRepository();
                break;
            case 'local':
                $this->app->bind(PseudoBsnRepository::class, LocalPseudoBsnRepository::class);
                break;
            default:
                throw new BindingResolutionException('no (valid) pseudo_bsn_service found');
        }

        $this->app->bind(AttachmentRepository::class, BridgeAttachmentRepository::class);
        $this->app->bind(MessageRepository::class, BridgeMessageRepository::class);
        $this->app->bind(PairingCodeRepository::class, BridgePairingCodeRepository::class);
        $this->app->bind(OtpCodeRepository::class, BridgeOtpCodeRepository::class);

        $this->app->bind(self::BRIDGE_REDIS, function (): Client {
            return Redis::connection('bridge')->client();
        });

        $this->app->when(BridgeClient::class)
            ->needs(RedisClient::class)
            ->give(self::BRIDGE_REDIS);

        $this->app->bind(AuditRepository::class, LogAuditRepository::class);
    }

    private function bindMittensPseudoBsnRepository(): void
    {
        $this->app->bind(PseudoBsnRepository::class, MittensPseudoBsnRepository::class);

        $this->app->bind(MittensPseudoBsnRepository::class, function (): MittensPseudoBsnRepository {
            return new MittensPseudoBsnRepository(
                new GuzzleClient(config('services.mittens.client_options')),
                config('services.mittens.digid_access_token'),
                $this->app->get(LoggerInterface::class)
            );
        });
    }
}
