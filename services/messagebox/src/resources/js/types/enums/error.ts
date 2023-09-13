/**
 * *** WARNING ***
 * This code is auto-generated. To change the items of this enum. Please edit Error.json!
 */

/**
 * Error values
 * All values are escaped with quotes and prefixed with VALUES_ to prevent generated errors
 * caused by unsupported characters or numeric values
 */
export enum Error {
    'VALUE_message_expired' = 'message_expired',
    'VALUE_message_not_found' = 'message_not_found',
    'VALUE_message_revoked' = 'message_revoked',
    'VALUE_message_user_not_authorized' = 'message_user_not_authorized',
    'VALUE_otp_info_retrieval_failed' = 'otp_info_retrieval_failed',
    'VALUE_otp_report_phone_number_failed' = 'otp_report_phone_number_failed',
    'VALUE_otp_request_failed' = 'otp_request_failed',
    'VALUE_otp_verification_failed' = 'otp_verification_failed',
    'VALUE_pairing_code_invalid' = 'pairing_code_invalid',
    'VALUE_pairing_code_expired' = 'pairing_code_expired',
    'VALUE_digid_canceled' = 'digid_canceled',
    'VALUE_digid_service_unavailable' = 'digid_service_unavailable',
    'VALUE_digid_auth_error' = 'digid_auth_error',
    'VALUE_attachment_not_available' = 'attachment_not_available',
    'VALUE_unauthenticated' = 'unauthenticated',
    'VALUE_unknown' = 'unknown',
}

/**
 * Error options to be used in the forms
 */
export const errorOptions = {
    [Error.VALUE_message_expired]: 'MESSAGE_EXPIRED',
    [Error.VALUE_message_not_found]: 'MESSAGE_NOT_FOUND',
    [Error.VALUE_message_revoked]: 'MESSAGE_REVOKED',
    [Error.VALUE_message_user_not_authorized]: 'MESSAGE_USER_NOT_AUTHORIZED',
    [Error.VALUE_otp_info_retrieval_failed]: 'OTP_INFO_RETRIEVAL_FAILED',
    [Error.VALUE_otp_report_phone_number_failed]: 'OTP_REPORT_PHONE_NUMBER_FAILED',
    [Error.VALUE_otp_request_failed]: 'OTP_REQUEST_FAILED',
    [Error.VALUE_otp_verification_failed]: 'OTP_VERIFICATION_FAILED',
    [Error.VALUE_pairing_code_invalid]: 'PAIRING_CODE_INVALID',
    [Error.VALUE_pairing_code_expired]: 'PAIRING_CODE_EXPIRED',
    [Error.VALUE_digid_canceled]: 'DIGID_CANCELED',
    [Error.VALUE_digid_service_unavailable]: 'DIGID_SERVICE_UNAVAILABLE',
    [Error.VALUE_digid_auth_error]: 'DIGID_AUTH_ERROR',
    [Error.VALUE_attachment_not_available]: 'ATTACHMENT_NOT_AVAILABLE',
    [Error.VALUE_unauthenticated]: 'UNAUTHENTICATED',
    [Error.VALUE_unknown]: 'UNKNOWN',
};
