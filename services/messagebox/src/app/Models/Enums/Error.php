<?php
namespace App\Models\Enums;
/**
 * Error
 *
 * *** WARNING ***
 * This code is auto-generated. To change the items of this enum. Please edit Error.json!
 *
 * @method static Error messageExpired() messageExpired() MESSAGE_EXPIRED
 * @method static Error messageNotFound() messageNotFound() MESSAGE_NOT_FOUND
 * @method static Error messageRevoked() messageRevoked() MESSAGE_REVOKED
 * @method static Error messageUserNotAuthorized() messageUserNotAuthorized() MESSAGE_USER_NOT_AUTHORIZED
 * @method static Error otpInfoRetrievalFailed() otpInfoRetrievalFailed() OTP_INFO_RETRIEVAL_FAILED
 * @method static Error otpReportPhoneNumberFailed() otpReportPhoneNumberFailed() OTP_REPORT_PHONE_NUMBER_FAILED
 * @method static Error otpRequestFailed() otpRequestFailed() OTP_REQUEST_FAILED
 * @method static Error otpVerificationFailed() otpVerificationFailed() OTP_VERIFICATION_FAILED
 * @method static Error pairingCodeInvalid() pairingCodeInvalid() PAIRING_CODE_INVALID
 * @method static Error pairingCodeExpired() pairingCodeExpired() PAIRING_CODE_EXPIRED
 * @method static Error digidCanceled() digidCanceled() DIGID_CANCELED
 * @method static Error digidServiceUnavailable() digidServiceUnavailable() DIGID_SERVICE_UNAVAILABLE
 * @method static Error digidAuthError() digidAuthError() DIGID_AUTH_ERROR
 * @method static Error attachmentNotAvailable() attachmentNotAvailable() ATTACHMENT_NOT_AVAILABLE
 * @method static Error unauthenticated() unauthenticated() UNAUTHENTICATED
 * @method static Error unknown() unknown() UNKNOWN

 * @property-read string $value
*/
final class Error extends Enum
{

    /**
     * @inheritDoc
     */
    protected static function enumSchema(): object
    {
        return (object) array(
           'phpClass' => 'Error',
           'tsConst' => 'error',
           'description' => 'Error',
           'items' => 
          array (
            0 => 
            (object) array(
               'label' => 'MESSAGE_EXPIRED',
               'value' => 'message_expired',
               'name' => 'messageExpired',
            ),
            1 => 
            (object) array(
               'label' => 'MESSAGE_NOT_FOUND',
               'value' => 'message_not_found',
               'name' => 'messageNotFound',
            ),
            2 => 
            (object) array(
               'label' => 'MESSAGE_REVOKED',
               'value' => 'message_revoked',
               'name' => 'messageRevoked',
            ),
            3 => 
            (object) array(
               'label' => 'MESSAGE_USER_NOT_AUTHORIZED',
               'value' => 'message_user_not_authorized',
               'name' => 'messageUserNotAuthorized',
            ),
            4 => 
            (object) array(
               'label' => 'OTP_INFO_RETRIEVAL_FAILED',
               'value' => 'otp_info_retrieval_failed',
               'name' => 'otpInfoRetrievalFailed',
            ),
            5 => 
            (object) array(
               'label' => 'OTP_REPORT_PHONE_NUMBER_FAILED',
               'value' => 'otp_report_phone_number_failed',
               'name' => 'otpReportPhoneNumberFailed',
            ),
            6 => 
            (object) array(
               'label' => 'OTP_REQUEST_FAILED',
               'value' => 'otp_request_failed',
               'name' => 'otpRequestFailed',
            ),
            7 => 
            (object) array(
               'label' => 'OTP_VERIFICATION_FAILED',
               'value' => 'otp_verification_failed',
               'name' => 'otpVerificationFailed',
            ),
            8 => 
            (object) array(
               'label' => 'PAIRING_CODE_INVALID',
               'value' => 'pairing_code_invalid',
               'name' => 'pairingCodeInvalid',
            ),
            9 => 
            (object) array(
               'label' => 'PAIRING_CODE_EXPIRED',
               'value' => 'pairing_code_expired',
               'name' => 'pairingCodeExpired',
            ),
            10 => 
            (object) array(
               'label' => 'DIGID_CANCELED',
               'value' => 'digid_canceled',
               'name' => 'digidCanceled',
            ),
            11 => 
            (object) array(
               'label' => 'DIGID_SERVICE_UNAVAILABLE',
               'value' => 'digid_service_unavailable',
               'name' => 'digidServiceUnavailable',
            ),
            12 => 
            (object) array(
               'label' => 'DIGID_AUTH_ERROR',
               'value' => 'digid_auth_error',
               'name' => 'digidAuthError',
            ),
            13 => 
            (object) array(
               'label' => 'ATTACHMENT_NOT_AVAILABLE',
               'value' => 'attachment_not_available',
               'name' => 'attachmentNotAvailable',
            ),
            14 => 
            (object) array(
               'label' => 'UNAUTHENTICATED',
               'value' => 'unauthenticated',
               'name' => 'unauthenticated',
            ),
            15 => 
            (object) array(
               'label' => 'UNKNOWN',
               'value' => 'unknown',
               'name' => 'unknown',
            ),
          ),
        );
    }
}
