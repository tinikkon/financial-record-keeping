import { defineStore } from 'pinia';
import { ref } from 'vue';
import { sheetApi } from '../shared/api/client';
import { tokenStorage } from '../shared/api/tokens';

interface User {
    id: string;
    email: string;
    name: string;
}

interface SessionResponse {
    user: User;
    accessToken: string;
    refreshToken: string;
}

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null);
    const isAuthenticated = ref(tokenStorage.read() !== null);
    const errorMessage = ref<string | null>(null);
    const isBusy = ref(false);

    async function login(email: string, password: string): Promise<boolean> {
        isBusy.value = true;
        errorMessage.value = null;

        try {
            const session = await sheetApi.request<SessionResponse>('auth/login', {
                method: 'POST',
                body: { email, password },
                withoutAuthentication: true,
            });

            tokenStorage.write(session);
            user.value = session.user;
            isAuthenticated.value = true;

            return true;
        } catch (error) {
            errorMessage.value = error instanceof Error ? error.message : 'Не удалось войти';

            return false;
        } finally {
            isBusy.value = false;
        }
    }

    async function loadCurrentUser(): Promise<void> {
        if (!isAuthenticated.value) {
            return;
        }

        try {
            user.value = await sheetApi.request<User>('me');
        } catch {
            forget();
        }
    }

    async function logout(): Promise<void> {
        const tokens = tokenStorage.read();

        if (tokens !== null) {
            await sheetApi
                .request('auth/logout', {
                    method: 'POST',
                    body: { refreshToken: tokens.refreshToken },
                    withoutAuthentication: true,
                })
                .catch(() => undefined);
        }

        forget();
    }

    function forget(): void {
        tokenStorage.clear();
        user.value = null;
        isAuthenticated.value = false;
    }

    return { user, isAuthenticated, errorMessage, isBusy, login, loadCurrentUser, logout, forget };
});
