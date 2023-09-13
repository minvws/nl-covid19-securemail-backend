import { authApi } from '@/api';
import env from '@/env';
import i18n from '@/i18n';
import store from '@/store';
import { StoreType } from '@/store/storeType';
import { SessionStoreAction } from '@/store/sessionStore';
import { parseDate } from '@/utils/date';
import { AxiosError, AxiosResponse } from 'axios';
import { add, intervalToDuration, isBefore } from 'date-fns';

let interval: NodeJS.Timer;
let countDownDate: Date | null = add(new Date(), { minutes: env.lifetime });

const sessionCallback = () => {
    if (!countDownDate) return;

    // If user not logged in, stop the session timer
    if (!store.state.session.isLoggedIn) {
        clearInterval(interval);
        return;
    }

    // Find the distance between now and the count down date
    const duration = intervalToDuration({
        start: new Date(),
        end: new Date(countDownDate),
    });

    if (isBefore(countDownDate, new Date())) {
        logout();
    } else if (typeof duration.minutes !== 'undefined' && duration.minutes < 5) {
        const minutes = duration.minutes?.toString().padStart(2, '0');
        const seconds = duration.seconds?.toString().padStart(2, '0');

        window.app.$modal.show({
            okOnly: true,
            hideHeaderClose: true,
            title: i18n.t('components.sessionModal.session.title').toString(),
            text: i18n
                .t('components.sessionModal.session.description', {
                    lifetime: env.lifetime,
                    minutes,
                    seconds,
                })
                .toString(),
            okTitle: i18n.t('components.sessionModal.session.button').toString(),
            onConfirm: keepAlive,
        });
    }
};

const keepAlive = () => {
    countDownDate = null;

    authApi.keepAlive().catch((error: AxiosError) => {
        if (error?.response?.status === 401) {
            logout();
            return;
        }

        window.app.$modal.show({
            okOnly: true,
            hideHeaderClose: true,
            title: i18n.t('components.sessionModal.connectivity.title').toString(),
            text: i18n
                .t('components.sessionModal.connectivity.description', {
                    lifetime: env.lifetime,
                })
                .toString(),
            okTitle: i18n.t('components.sessionModal.connectivity.button').toString(),
            onConfirm: keepAlive,
        });
    });
};

const logout = async () => {
    window.app.$modal.hide();

    countDownDate = null;
    clearInterval(interval);

    store.dispatch(`${StoreType.SESSION}/${SessionStoreAction.LOGOUT}`).finally(() => {
        window.app.$router.replace({ name: 'home' });

        window.app.$modal.show({
            okOnly: true,
            hideHeaderClose: true,
            title: i18n.t('components.sessionModal.logout.title').toString(),
            text: i18n
                .t('components.sessionModal.logout.description', {
                    lifetime: env.lifetime,
                })
                .toString(),
            okTitle: i18n.t('components.sessionModal.logout.button').toString(),
            onConfirm: () => window.location.reload(),
        });
    });
};

interval = setInterval(sessionCallback, 1000);

export const sessionResponseInterceptor = (response: AxiosResponse) => {
    if (response.headers['x-session-expiry-date']) {
        countDownDate = parseDate(response.headers['x-session-expiry-date']);
    }

    return response;
};

export const sessionErrorInterceptor = (error: AxiosError) => {
    if (error.response?.headers['x-session-expiry-date']) {
        countDownDate = parseDate(error.response.headers['x-session-expiry-date']);
    }

    return Promise.reject(error);
};
