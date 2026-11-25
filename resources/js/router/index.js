import { createRouter, createWebHistory } from 'vue-router';
import routes from './routes.js';

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    const canAccessAdmin =
        document.getElementById('app').dataset.canAccessAdmin === '1';

    if (to.meta.requiresAdmin && !canAccessAdmin) {
        return { name: 'login' };
    }

    if (to.name === 'login' && canAccessAdmin) {
        return { name: 'admin.dashboard' };
    }
});

router.afterEach((to) => {
    document.title = to.meta.title
        ? `${to.meta.title} · Munaasab Admin`
        : 'Munaasab Admin';
});

export default router;
