<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Enums\LoginType;
use App\Models\Message;
use App\Models\MessagePreview;
use App\Models\OtpCode;
use App\Models\PairingCode;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTime;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Collection;
use League\CommonMark\CommonMarkConverter;
use Tests\TestCase;

use function array_key_exists;

class ControllerTestCase extends TestCase
{
    protected function convertMarkdownToHtml(string $markdown): string
    {
        $converter = new CommonMarkConverter();

        return $converter->convert($markdown)->getContent();
    }

    protected function createAttachment(string $uuid = null, string $filename = null): Attachment
    {
        return new Attachment(
            $uuid ?? $this->faker->uuid,
            $filename ?? $this->faker->word,
            $this->faker->mimeType(),
        );
    }

    protected function createAttachments(int $amount): Collection
    {
        $collection = new Collection();

        for ($i = 0; $i < $amount; $i++) {
            $collection->add($this->createAttachment());
        }

        return $collection;
    }

    protected function createMessage(array $attributes = [], ?int $numberOfAttachments = null): Message
    {
        /** @var DateTime|null $expiresAt */
        $expiresAt = $attributes['expiresAt'] ?? $this->faker->optional()->dateTime;
        $attachmentsEncryptionKey = array_key_exists('attachmentsEncryptionKey', $attributes)
            ? $attributes['attachmentsEncryptionKey']
            : $this->faker->optional()->passthrough(Encrypter::generateKey('aes-128-cbc'));

        return new Message(
            $attributes['uuid'] ?? $this->faker->uuid,
            $attributes['aliasId'] ?? $this->faker->uuid,
            $attributes['fromeName'] ?? $this->faker->name,
            $attributes['toName'] ?? $this->faker->name,
            $attributes['subject'] ?? $this->faker->sentence,
            $attributes['text'] ?? $this->faker->paragraph,
            $attributes['footer'] ?? $this->faker->paragraph,
            $attributes['createdAt'] ?? CarbonImmutable::instance($this->faker->dateTime),
            $expiresAt !== null ? CarbonImmutable::instance($expiresAt) : null,
            $this->createAttachments(
                $numberOfAttachments !== null ? $numberOfAttachments : $this->faker->randomDigit()
            ),
            $attachmentsEncryptionKey,
        );
    }

    protected function createMessagePreview(string $uuid = null, CarbonInterface $createdAt = null): MessagePreview
    {
        return new MessagePreview(
            $uuid ?? $this->faker->uuid,
            $this->faker->name,
            $this->faker->sentence,
            $createdAt ?? CarbonImmutable::instance($this->faker->dateTime),
            $this->faker->boolean,
            $this->faker->boolean,
        );
    }

    protected function createOtpCode(): OtpCode
    {
        return new OtpCode(
            $this->faker->uuid,
            $this->faker->randomElement(LoginType::all()),
            $this->faker->phoneNumber,
            CarbonImmutable::instance($this->faker->dateTimeBetween('-15 minutes')),
        );
    }

    protected function createPairingCode(string $messageUuid = null): PairingCode
    {
        return new PairingCode(
            $this->faker->uuid,
            $messageUuid ?? $this->faker->uuid,
            $this->faker->safeEmail,
            $this->faker->name,
            CarbonImmutable::instance($this->faker->dateTimeBetween('-3 days')),
        );
    }
}
