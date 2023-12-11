import store from '@/store';
import { State as SessionState } from '@/store/sessionStore/sessionStore';
import { Route } from 'vue-router';
import { authGuard } from './router';

jest.mock('@/store', () => {
    const sessionState: SessionState = {
        name: '',
        isLoggedIn: false,
        language: 'nl',
        sessionMessageUuid: undefined,
        loginOptions: [],
        pairingCodeResponse: null,
        digidResponse: null,
    };

    return {
        state: {
            session: sessionState,
        },
    };
});

describe('authGuard', () => {
    it.each([
        {
            requiredAuth: undefined,
            isLoggedIn: false,
            expected: undefined,
        },
        {
            requiredAuth: undefined,
            isLoggedIn: true,
            expected: undefined,
        },
        {
            requiredAuth: false,
            isLoggedIn: false,
            expected: undefined,
        },
        {
            requiredAuth: false,
            isLoggedIn: true,
            expected: { name: 'inbox', replace: true },
        },
        {
            requiredAuth: true,
            isLoggedIn: false,
            expected: { name: 'home', replace: true },
        },
        {
            requiredAuth: true,
            isLoggedIn: true,
            expected: undefined,
        },
    ])(
        'if meta requiredAuth=$requiredAuth and isLoggedIn=$isLoggedIn, next() should be called with $expected',
        ({ requiredAuth, isLoggedIn, expected }) => {
            store.state.session.isLoggedIn = isLoggedIn;

            const dummyRoute: Route = {
                path: '/',
                hash: '',
                query: {},
                params: {},
                fullPath: '/',
                matched: [],
                meta: {
                    title: 'test',
                    requiredAuth,
                },
            };
            const next = jest.fn();

            authGuard(dummyRoute, dummyRoute, next);

            if (expected === undefined) {
                expect(next).toHaveBeenCalledWith();
            } else {
                expect(next).toHaveBeenCalledWith(expected);
            }
        }
    );
});
