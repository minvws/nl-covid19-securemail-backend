import { config } from '@vue/test-utils';

// Default mocks
config.mocks.$t = (msg: string) => msg;

// Mocked modules
jest.mock('@/i18n');

jest.mock('@/interceptors/sessionInterceptor', () => ({
    sessionResponseInterceptor: () => {},
    sessionErrorInterceptor: () => {},
}));
