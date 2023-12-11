import App from './App.vue';
import BootstrapVue from 'bootstrap-vue';
import SvgVue from 'svg-vue';
import Vue from 'vue';
import VueMask from 'v-mask';
import './filters';
import i18n from './i18n/index';
import Modal from './plugins/modal';
import router from './router';
import store from './store';
import './vee-validate';

/**
 * Initialize the plugins.
 */
Vue.use(BootstrapVue);
Vue.use(SvgVue);
Vue.use(VueMask);
Vue.use(Modal);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */
window.app = new Vue({
    el: '#app',
    store,
    i18n,
    router,
    render: h => h(App),
});
