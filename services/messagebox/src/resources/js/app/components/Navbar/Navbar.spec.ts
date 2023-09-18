import { authApi } from '@/api';
import BaseModal from '@/app/components/BaseModal/BaseModal.vue';
import Modal from '@/plugins/modal';
import { SessionStoreAction } from '@/store/sessionStore';
import sessionStore, { State as SessionState } from '@/store/sessionStore/sessionStore';
import { StoreType } from '@/store/storeType';
import { AppProps, defineComponent } from '@/typeHelpers';
import { fakerjs, setupTest } from '@/utils/testUtils';
import { createLocalVue, shallowMount, Wrapper } from '@vue/test-utils';
import BootstrapVue, { BModal } from 'bootstrap-vue';
import SvgVue from 'svg-vue';
import { VueConstructor } from 'vue';
import Vuex from 'vuex';
import Navbar from './Navbar.vue';

jest.spyOn(authApi, 'logout').mockImplementation(() => Promise.resolve());

Object.defineProperty(window, 'location', {
    value: {
        href: fakerjs.internet.url(),
    },
});

const fakerUrl = fakerjs.internet.url();
const mockRouter = {
    resolve: jest.fn(() => {
        return {
            href: fakerUrl,
        };
    }),
};

const createComponent = setupTest(
    (localVue: VueConstructor, props: object = {}, sessionState: Partial<SessionState> = {}) => {
        const sessionStoreModule = {
            ...sessionStore,
            state: {
                ...sessionStore.state,
                ...sessionState,
            },
        };

        return shallowMount<Vue & AppProps>(Navbar, {
            localVue,
            propsData: props,
            mocks: {
                $t: (msg: string) => msg,
                $router: mockRouter,
            },
            store: new Vuex.Store({
                modules: {
                    [StoreType.SESSION]: sessionStoreModule,
                },
            }),
        });
    },
    [BootstrapVue, Modal, SvgVue, Vuex]
);

let modalWrapper: Wrapper<Vue>;
const getModalWrapper = () => {
    const localVue = createLocalVue();
    localVue.use(BootstrapVue);
    localVue.use(Modal);
    localVue.use(Vuex);

    const TestComponent = defineComponent({ name: 'Test', template: '<base-modal />' });

    return (modalWrapper = shallowMount(TestComponent, {
        localVue,
        stubs: {
            BaseModal,
            BModal,
        },
    }));
};

describe('Navbar.vue', () => {
    afterEach(() => {
        modalWrapper?.destroy();
    });

    it('should load', () => {
        const wrapper = createComponent();

        expect(wrapper.findComponent({ name: 'BNavbar' }).exists()).toBe(true);
    });

    it('should add class "d-flex" to brand-container if prop isHome=true', () => {
        const wrapper = createComponent({ isHome: true });

        expect(wrapper.find('[data-testid="brand-container"]').classes()).toEqual(['d-flex']);
    });

    it('should add classes "d-none d-lg-flex" to brand-container if prop isHome=false', () => {
        const wrapper = createComponent();

        expect(wrapper.find('[data-testid="brand-container"]').classes()).toEqual(['d-none', 'd-lg-flex']);
    });

    it('should show BackButton if prop hasBackButton=true', () => {
        const wrapper = createComponent({ hasBackButton: true });

        expect(wrapper.findComponent({ name: 'BackButton' }).exists()).toBe(true);
    });

    it('should NOT show BackButton if prop hasBackButton=false', () => {
        const wrapper = createComponent();

        expect(wrapper.findComponent({ name: 'BackButton' }).exists()).toBe(false);
    });

    it('should emit "back" when BackButton is clicked', () => {
        const wrapper = createComponent({ hasBackButton: true });

        wrapper.findComponent({ name: 'BackButton' }).vm.$emit('click');

        expect(wrapper.emitted().back).toBeTruthy();
    });

    it('should show logout button if logged in', () => {
        const wrapper = createComponent(undefined, { isLoggedIn: true });

        const logoutButton = wrapper.find('[data-testid="navbar-logout-container"]').findComponent({ name: 'BButton' });
        expect(logoutButton.exists()).toBe(true);
        expect(logoutButton.text()).toBe('components.navbar.logout');
    });

    it('should NOT show logout button if not logged in', () => {
        const wrapper = createComponent();

        const logoutButton = wrapper.find('[data-testid="navbar-logout-container"]').findComponent({ name: 'BButton' });
        expect(logoutButton.exists()).toBe(false);
    });

    it('should add classes "d-none d-lg-flex" to logout button if prop hasBackButton=true', () => {
        const wrapper = createComponent({ hasBackButton: true }, { isLoggedIn: true });

        const logoutButton = wrapper.find('[data-testid="navbar-logout-container"]').findComponent({ name: 'BButton' });
        expect(logoutButton.classes()).toEqual(expect.arrayContaining(['d-none', 'd-lg-flex']));
    });

    it('should not add classes to button-logout if prop hasBackButton=false', () => {
        const wrapper = createComponent(undefined, { isLoggedIn: true });

        const logoutButton = wrapper.find('[data-testid="navbar-logout-container"]').findComponent({ name: 'BButton' });
        expect(logoutButton.classes()).not.toEqual(expect.arrayContaining(['d-none', 'd-lg-flex']));
    });

    it('should open a modal when logout button is clicked', () => {
        const wrapper = createComponent(undefined, { isLoggedIn: true });
        const spyShow = jest.spyOn(wrapper.vm.$modal, 'show');

        const logoutButton = wrapper.find('[data-testid="navbar-logout-container"]').findComponent({ name: 'BButton' });
        logoutButton.trigger('click');

        expect(spyShow).toBeCalled();
    });

    it(`should dispatch ${StoreType.SESSION}/${SessionStoreAction.LOGOUT} if modal is confirmed`, async () => {
        const wrapper = createComponent(undefined, { isLoggedIn: true });
        const modalWrapper = getModalWrapper();
        const spyOnLogout = jest.spyOn(authApi, 'logout').mockImplementationOnce(() => Promise.resolve());

        const logoutButton = wrapper.find('[data-testid="navbar-logout-container"]').findComponent({ name: 'BButton' });
        logoutButton.trigger('click');

        modalWrapper.findComponent({ name: 'BModal' }).vm.$emit('ok');
        await modalWrapper.vm.$nextTick();

        expect(spyOnLogout).toHaveBeenCalledTimes(1);
        await wrapper.vm.$nextTick();
        expect(mockRouter.resolve).toBeCalledWith({ name: 'home' });
        expect(window.location.href).toBe(fakerUrl);
    });
});
