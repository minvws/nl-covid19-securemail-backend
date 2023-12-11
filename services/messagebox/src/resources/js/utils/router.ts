import { NavigationGuard } from 'vue-router';
import store from '@/store';

/**
 * This guard checks if the user is allowed to be on the given page
 */
export const authGuard: NavigationGuard = (to, _, next) => {
    // User should NOT be logged in, otherwise send to inbox
    if (to.meta?.requiredAuth === false && store.state.session.isLoggedIn) {
        next({ name: 'inbox', replace: true });
        return;
    }

    // User should be logged in, otherwise send to home
    if (to.meta?.requiredAuth === true && !store.state.session.isLoggedIn) {
        next({ name: 'home', replace: true });
        return;
    }

    next();
};
