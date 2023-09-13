import { OTPType } from '@/types/enums/OTPType';
import instance from './defaults';

export const loginByCode = (emailAddress: string, pairingCode: string) =>
    instance
        .post('/api/v1/pairing_code', {
            emailAddress,
            pairingCode,
        })
        .then(res => res.data);
export const getLoginOptions = () => instance.get('/api/v1/auth/options').then(res => res.data);

// OTP
export const requestOtpInfo = () => instance.post('/api/v1/otp/info').then(res => res.data);
export const requestOtpCode = (loginType: OTPType) =>
    instance
        .post('/api/v1/otp/request', {
            loginType,
        })
        .then(res => res.data);
export const verifyOtpCode = (loginType: OTPType, otpCode: string) =>
    instance
        .post('/api/v1/otp', {
            loginType,
            otpCode,
        })
        .then(res => res.data);

export const reportWrongPhoneNumber = (phoneNumber: string) =>
    instance.post('/api/v1/otp/incorrect-phone', { phoneNumber }).then(res => res.data);

export const requestNewPairingCode = (pairingCodeUuid: string) =>
    instance.post('/api/v1/pairing_code/renew', {
        pairingCodeUuid,
    });

// Session
export const keepAlive = () => instance.get('/api/v1//auth/keep-alive').then(res => res.data);
export const logout = () => instance.get('/api/v1/auth/logout').then(res => res.data);
