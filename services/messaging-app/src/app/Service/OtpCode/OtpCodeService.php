<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Service\OtpCode;

use Carbon\CarbonImmutable;
use Exception;
use MinVWS\MessagingApp\Helpers\CodeGenerator;
use MinVWS\MessagingApp\Model\OtpCode;
use MinVWS\MessagingApp\Repository\OtpCodeRepository;
use MinVWS\MessagingApp\Service\OtpCode\Sms\SmsException;
use Ramsey\Uuid\Uuid;

class OtpCodeService
{
    private CodeGenerator $codeGenerator;
    private OtpCodeRepository $otpCodeRepository;
    private OtpCodeTypeServiceFactory $otpCodeTypeServiceFactory;
    private bool $testMode;

    public function __construct(
        CodeGenerator $codeGenerator,
        OtpCodeRepository $otpCodeRepository,
        OtpCodeTypeServiceFactory $otpCodeTypeServiceFactory,
        bool $testMode,
    ) {
        $this->codeGenerator = $codeGenerator;
        $this->otpCodeRepository = $otpCodeRepository;
        $this->otpCodeTypeServiceFactory = $otpCodeTypeServiceFactory;
        $this->testMode = $testMode;
    }

    /**
     * @throws OtpCodeException
     */
    public function createFromMessageUuidAndType(string $messageUuid, string $type): OtpCode
    {
        try {
            //Delete old otpCodes
            foreach ($this->otpCodeRepository->getByMessageUuid($messageUuid) as $otpCodeModel) {
                $this->otpCodeRepository->delete($otpCodeModel);
            }
            $code = $this->testMode ? '123456' : $this->codeGenerator->generate('0123456789', 6);

            $otpCode = new OtpCode(
                Uuid::uuid4()->toString(),
                $messageUuid,
                $type,
                $code,
                CarbonImmutable::now()->addMinutes(15)
            );
            $this->otpCodeRepository->save($otpCode);
        } catch (Exception $exception) {
            throw OtpCodeException::fromThrowable($exception);
        }

        return $otpCode;
    }

    /**
     * @throws OtpCodeException
     */
    public function send(OtpCode $otpCode): void
    {
        try {
            $otpCodeTypeService = $this->otpCodeTypeServiceFactory->fromString($otpCode->type);
            $otpCodeTypeService->sendOtpCode($otpCode);
        } catch (OtpCodeTypException | SmsException $smsException) {
            throw OtpCodeException::fromThrowable($smsException);
        }
    }
}
