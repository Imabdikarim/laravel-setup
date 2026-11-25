import Login from '../views/auth/Login.vue';
import AdminLayout from '../components/admin/AdminLayout.vue';
import Dashboard from '../views/admin/Dashboard.vue';
import Projects from '../views/admin/Projects.vue';
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
                component: Dashboard,
                meta: { title: 'Overview' },
            },
            {
                path: 'projects',
                name: 'admin.projects',
                component: Projects,
                meta: { title: 'Projects' },
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
