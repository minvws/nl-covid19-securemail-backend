<?php

namespace MinVWS\MessagingApp\Tests\Feature\Service;

use MinVWS\MessagingApp\Model\Alias;
use MinVWS\MessagingApp\Model\Attachment;
use MinVWS\MessagingApp\Model\Mailbox;
use MinVWS\MessagingApp\Model\Message;
use MinVWS\MessagingApp\Model\OtpCode;
use MinVWS\MessagingApp\Model\PairingCode;
use MinVWS\MessagingApp\Repository\AliasRepository;
use MinVWS\MessagingApp\Repository\AttachmentRepository;
use MinVWS\MessagingApp\Repository\MailboxRepository;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Repository\OtpCodeRepository;
use MinVWS\MessagingApp\Repository\PairingCodeRepository;
use MinVWS\MessagingApp\Tests\TestHelper\AliasFactory;
use MinVWS\MessagingApp\Tests\TestHelper\AttachmentFactory;
use MinVWS\MessagingApp\Tests\TestHelper\MailboxFactory;
use MinVWS\MessagingApp\Tests\TestHelper\MessageFactory;
use MinVWS\MessagingApp\Tests\TestHelper\OtpCodeFactory;
use MinVWS\MessagingApp\Tests\TestHelper\PairingCodeFactory;

use function array_key_exists;

trait CreateModelTrait
{
    protected function createAlias(array $attributes = []): Alias
    {
        if (!array_key_exists('mailboxUuid', $attributes)) {
            $attributes['mailboxUuid'] = $this->createMailbox()->uuid;
        }

        $alias = AliasFactory::create($attributes);
        $this->getContainer()->get(AliasRepository::class)->save($alias);

        return $alias;
    }

    protected function createAttachment(array $attributes = []): Attachment
    {
        if (!array_key_exists('messageUuid', $attributes)) {
            $attributes['messageUuid'] = $this->createMessage()->uuid;
        }

        $attachment = AttachmentFactory::create($attributes);
        $this->getContainer()->get(AttachmentRepository::class)->save($attachment);

        return $attachment;
    }

    protected function createMailbox(array $attributes = []): Mailbox
    {
        $mailbox = MailboxFactory::create($attributes);
        $this->getContainer()->get(MailboxRepository::class)->save($mailbox);

        return $mailbox;
    }

    protected function createMessage(array $attributes = []): Message
    {
        if (!array_key_exists('mailboxUuid', $attributes)) {
            $attributes['mailboxUuid'] = $this->createMailbox()->uuid;
        }

        if (!array_key_exists('aliasUuid', $attributes)) {
            $attributes['aliasUuid'] = $this->createAlias(['mailboxUuid' => $attributes['mailboxUuid']])->uuid;
        }

        $message = MessageFactory::generateModel($attributes);
        $this->getContainer()->get(MessageRepository::class)->save($message);

        return $message;
    }

    protected function createOtpCode(array $attributes = []): OtpCode
    {
        if (!array_key_exists('messageUuid', $attributes)) {
            $attributes['messageUuid'] = $this->createMessage()->uuid;
        }

        $otpCode = OtpCodeFactory::generateModel($attributes);
        $this->getContainer()->get(OtpCodeRepository::class)->save($otpCode);

        return $otpCode;
    }

    protected function createPairingCode(array $attributes = []): PairingCode
    {
        if (!array_key_exists('aliasUuid', $attributes)) {
            $attributes['aliasUuid'] = $this->createAlias()->uuid;
        }

        if (!array_key_exists('messageUuid', $attributes)) {
            $attributes['messageUuid'] = $this->createMessage(['aliasUuid' => $attributes['aliasUuid']])->uuid;
        }

        $pairingCode = PairingCodeFactory::generateModel($attributes);
        $this->getContainer()->get(PairingCodeRepository::class)->save($pairingCode);

        return $pairingCode;
    }
}
