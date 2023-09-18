import Vue from 'vue';
import VueRouter from 'vue-router';
import i18n from './i18n/index';
import GenericPage from './app/pages/GenericPage/GenericPage.vue';
import HomePage from './app/pages/HomePage/HomePage.vue';
import TokenPage from './app/pages/TokenPage/TokenPage.vue';
import LoginPage from './app/pages/LoginPage/LoginPage.vue';
import TwoFactorPage from './app/pages/TwoFactorPage/TwoFactorPage.vue';
import ErrorPage from './app/pages/ErrorPage/ErrorPage.vue';
import InboxPage from './app/pages/InboxPage/InboxPage.vue';
import AccessibilityStatementPage from './app/pages/AccessibilityStatementPage/AccessibilityStatementPage.vue';
import PrivacyStatementPage from './app/pages/PrivacyStatementPage/PrivacyStatementPage.vue';
import ReportVulnerabilityPage from './app/pages/ReportVulnerabilityPage/ReportVulnerabilityPage.vue';

import Message from './app/components/Message/Message.vue';
import EmptyInboxMessage from './app/components/EmptyInboxMessage/EmptyInboxMessage.vue';
import { authGuard } from './utils/router';

const getPageTitle = (page: string) => i18n.t(`pages.${page}.pageTitle`).toString();

const router: VueRouter = new VueRouter({
    mode: 'history',
    routes: [
        {
            path: '/',
            component: GenericPage,
            children: [
                {
                    path: '',
                    name: 'home',
                    component: HomePage,
                    meta: {
                        requiredAuth: false,
                        title: getPageTitle('homePage'),
                    },
                },
                {
                    path: '/auth/token',
                    name: 'auth.token',
                    component: TokenPage,
                    meta: {
                        backButton: () => router.go(-1),
                        requiredAuth: false,
                        title: getPageTitle('tokenPage'),
                    },
                },
                {
                    path: '/auth/login',
                    name: 'auth.login',
                    component: LoginPage,
                    meta: {
                        backButton: () => router.go(-1),
                        requiredAuth: false,
                        title: getPageTitle('loginPage'),
                    },
                },
                {
                    path: '/auth/2fa',
                    name: 'auth.2fa',
                    component: TwoFactorPage,
                    props: (route) => ({
                        otpType: route.params.otpType,
                    }),
                    meta: {
                        backButton: () => router.go(-1),
                        requiredAuth: false,
                        title: getPageTitle('twoFactorPage'),
                    },
                },
                {
                    path: '/error/:code',
                    name: 'error',
                    component: ErrorPage,
                    props: (route) => ({
                        code: route.params.code,
                    }),
                    meta: {
                        title: getPageTitle('errorPage'),
                    },
                },

                // Footer pages
                {
                    path: '/toegankelijkheid',
                    name: 'accessibility',
                    component: AccessibilityStatementPage,
                    meta: {
                        backButton: () => router.go(-1),
                        title: getPageTitle('accessibilityStatementPage'),
                    },
                },
                {
                    path: '/privacy',
                    name: 'privacy',
                    component: PrivacyStatementPage,
                    meta: {
                        backButton: () => router.go(-1),
                        title: getPageTitle('privacyStatementPage'),
                    },
                },
                {
                    path: '/kwetsbaarheid-melden',
                    name: 'reportVulnerability',
                    component: ReportVulnerabilityPage,
                    meta: {
                        backButton: () => router.go(-1),
                        title: getPageTitle('reportVulnerabilityPage'),
                    },
                },
            ],
        },

        {
            path: '/inbox',
            component: InboxPage,
            children: [
                {
                    path: '',
                    name: 'inbox',
                    component: EmptyInboxMessage,
                    meta: {
                        requiredAuth: true,
                        title: getPageTitle('inboxPage'),
                    },
                },
                {
                    path: ':messageId',
                    name: 'inbox.message',
                    component: Message,
                    meta: {
                        requiredAuth: true,
                        title: getPageTitle('inboxPage'),
                    },
                },
            ],
        },
    ],
});

router.beforeEach((to, from, next) => {
    authGuard(to, from, next);

    // Gets page title and replaces placeholders with params
    const pageTitle = to.meta?.title?.replace(
        /{{(\w+)}}/g,
        (match: string, param: string) => to.params[param] || match
    );
    document.title = i18n.t('app.title', { title: pageTitle }).toString();

    next();
});

Vue.use(VueRouter);

export default router;
