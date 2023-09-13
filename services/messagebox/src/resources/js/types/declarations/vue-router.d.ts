import 'vue-router';

declare module 'vue-router' {
    interface RouteMeta {
        backButton?: Function;
        requiredAuth?: boolean;
        title: string;
    }
}
