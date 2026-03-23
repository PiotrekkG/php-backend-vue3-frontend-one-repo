import UnloggedView from './UnloggedView.vue'
import GuestLoginView from './GuestLoginView.vue';
import VAuth from './VAuth.vue';

/**
 * Thread-related routes
 * @type RouteRecordRaw[]
 */
const routes = [
  {
    path: '/',
    children: [
      {
        path: '/',
        name: 'UnloggedView',
        component: UnloggedView,
        meta: { authRedirectTo: 'Logged' }
      },
      {
        path: 'guest',
        name: 'Guest',
        component: GuestLoginView,
        meta: { authRedirectTo: 'Logged' }
      },
      {
        path: 'register',
        name: 'Register',
        component: VAuth,
        // component: () => import('./VAuth.vue'),
        props: { formType: 'register' },
        meta: { authRedirectTo: 'Logged' }
      },
      {
        path: 'change-password',
        name: 'ChangePassword',
        component: VAuth,
        // component: () => import('./VAuth.vue'),
        props: { formType: 'change-password' },
        meta: { authRedirectTo: 'Logged' }
      },
      {
        path: 'login',
        name: 'Login',
        component: VAuth,
        // component: () => import('./VAuth.vue'),
        props: { formType: 'login' },
        meta: { authRedirectTo: 'Logged' }
      },
    ]
  }
];

export default routes;
