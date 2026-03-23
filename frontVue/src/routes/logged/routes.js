import LoggedView from './LoggedView.vue';

/**
 * Thread-related routes
 * @type RouteRecordRaw[]
 */
const routes = [
    {
        path: '/logged',
        name: 'Logged',
        component: LoggedView,
        meta: { requiresAuth: true }
    },
];

export default routes;
