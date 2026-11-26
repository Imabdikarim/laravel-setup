import Login from '../views/auth/Login.vue';
import AdminLayout from '../components/admin/AdminLayout.vue';
import Overview from '../views/admin/Overview.vue';
import Projects from '../views/admin/Projects.vue';
import Settings from '../views/admin/Settings.vue';
import Account from '../views/admin/Account.vue';

export default [
    { path: '/', redirect: { name: 'admin.dashboard' } },
    { path: '/login', name: 'login', component: Login, meta: { title: 'Sign in' } },
    {
        path: '/admin',
        component: AdminLayout,
        meta: { requiresAdmin: true },
        children: [
            {
                path: '',
                name: 'admin.dashboard',
                component: Overview,
                meta: { title: 'Overview' },
            },
            {
                path: 'projects',
                name: 'admin.projects',
                component: Projects,
                meta: { title: 'Projects' },
            },
            {
                path: 'settings',
                name: 'admin.settings',
                component: Settings,
                meta: { title: 'Settings' },
            },
            {
                path: 'account',
                name: 'admin.account',
                component: Account,
                meta: { title: 'My account' },
            },
        ],
    },
];