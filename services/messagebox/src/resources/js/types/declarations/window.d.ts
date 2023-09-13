declare interface Window {
    app: Vue & {
        $modal: import('@/plugins/modal').BaseModalMethods;
    };
    config: import('@/env').Environment;
    isLoggedIn: boolean;
    language?: import('@/i18n/locales').Locales;
    sessionMessageUuid?: string;
    pairingCodeResponse: import('@/store/sessionStore/sessionStore').PairingCodeResponse | null;
    digidResponse: import('@/store/sessionStore/sessionStore').DigidResponse | null;
}
