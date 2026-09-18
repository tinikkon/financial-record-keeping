import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import LoginPage from '../pages/LoginPage.vue';
import WorkbookPage from '../pages/WorkbookPage.vue';

export const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'workbook', component: WorkbookPage, meta: { requiresAuthentication: true } },
        { path: '/login', name: 'login', component: LoginPage },
    ],
});

router.beforeEach((to) => {
    const authentication = useAuthStore();

    if (to.meta.requiresAuthentication === true && !authentication.isAuthenticated) {
        return { name: 'login' };
    }

    if (to.name === 'login' && authentication.isAuthenticated) {
        return { name: 'workbook' };
    }

    return true;
});
