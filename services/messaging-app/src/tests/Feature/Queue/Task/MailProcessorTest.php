<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Queue\Task;

use DI\DependencyException;
use DI\NotFoundException;
use MinVWS\Audit\AuditService;
use MinVWS\MessagingApp\Queue\QueueException;
use MinVWS\MessagingApp\Queue\Task\DTO;
use MinVWS\MessagingApp\Queue\Task\MailProcessor;
use MinVWS\MessagingApp\Tests\Feature\FeatureTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailProcessorTest extends FeatureTestCase
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws QueueException
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws DependencyException
     */
    public function testProcess(): void
    {
        $defaultFromAddress = 'foo@bar.com'; // same as env-var in phpunit.xml

        $fromName = $this->faker->company;
        $toName = $this->faker->name;
        $toEmail = $this->faker->safeEmail;
        $subject = $this->faker->sentence;
        $html = $this->faker->randomHtml;
        $attachments = null;
        $messageUuid = $this->faker->uuid;

        $mailDto = new DTO\Mail($fromName, $toEmail, $toName, $subject, $html, $attachments, $messageUuid);
        $email = new Email();
        $email->from(new Address($defaultFromAddress, $fromName));
        $email->to(new Address($toEmail, $toName));
        $email->subject($subject);
        $email->html($html);

        /** @var MailerInterface|MockObject $mailer */
        $mailer = $this->mock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($email);

        $mailProcessor = new MailProcessor(
            new NullLogger(),
            $mailer,
            $defaultFromAddress,
            $this->getContainer()->get(AuditService::class),
        );
        $mailProcessor->process($mailDto);
    }
}
