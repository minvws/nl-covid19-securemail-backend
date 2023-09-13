import Vue from 'vue';
import Vuex from 'vuex';
import messageStore, { State as MessageState } from './messageStore/messageStore';
import sessionStore, { State as SessionState } from './sessionStore/sessionStore';
import otpStore, { State as OtpState } from './otpStore/otpStore';
import { StoreType } from './storeType';

Vue.use(Vuex);

export interface RootStoreState {
    [StoreType.MESSAGE]: MessageState;
    [StoreType.SESSION]: SessionState;
    [StoreType.OTP]: OtpState;
}

const store = new Vuex.Store<RootStoreState>({
    modules: {
        message: messageStore,
        session: sessionStore,
        otp: otpStore,
    },
});

export default store;
