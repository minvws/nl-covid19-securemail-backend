<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Queue\Task;

use Exception;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Helpers\PHPDocHelper;
use MinVWS\Audit\Models\AuditEvent;
use MinVWS\Audit\Models\AuditObject;
use MinVWS\MessagingApp\Queue\QueueException;
use MinVWS\MessagingApp\Queue\Task\DTO;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

use function array_map;
use function base64_decode;

class MailProcessor implements TaskProcessor
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MailerInterface $mailer,
        private readonly string $defaultFromEmail,
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * @auditEventDescription Email bericht via smtp
     *
     * @throws QueueException
     * @throws Exception
     */
    public function process(DTO\Dto $task): void
    {
        if (!$task instanceof DTO\Mail) {
            throw new QueueException('task is not of type mail');
        }

        $this->logger->debug('process message (mail)');

        $this->auditService->registerEvent(
            AuditEvent::create(__METHOD__, AuditEvent::ACTION_EXECUTE, PHPDocHelper::getTagAuditEventDescriptionByActionName(__METHOD__))
                ->object(AuditObject::create('message', $task->uuid)),
            function (AuditEvent $auditEvent) use ($task) {
                $this->doProcess($task);
            }
        );

        $this->auditService->finalizeEvent();
    }

    /**
     * @throws QueueException
     */
    protected function doProcess(DTO\Mail $task): void
    {
        try {
            $email = new Email();
            $email->from(new Address($this->defaultFromEmail, $task->fromName));
            $email->to(new Address($task->toEmail, $task->toName));
            $email->subject($task->subject);
            $email->html($task->html);

            if ($attachments = $task->attachments) {
                array_map(function ($attachment) use ($email) {
                    $email->attach(base64_decode($attachment['blob']), $attachment['name'], $attachment['mime']);
                }, $attachments);
            }

            try {
                $this->logger->debug('sending email', [
                    'to' => $task->toEmail,
                    'subject' => $task->subject,
                ]);

                $this->mailer->send($email);
            } catch (TransportExceptionInterface $transportException) {
                throw MailerException::fromThrowable($transportException);
            }

            $this->logger->debug('message sent');
        } catch (MailerException $exception) {
            throw QueueException::fromThrowable($exception);
        }
    }
}
