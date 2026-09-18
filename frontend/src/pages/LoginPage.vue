<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const authentication = useAuthStore();
const router = useRouter();

const email = ref('');
const password = ref('');

async function войти(): Promise<void> {
    const успешно = await authentication.login(email.value, password.value);

    if (успешно) {
        await router.push({ name: 'workbook' });
    }
}
</script>

<template>
    <div class="вход">
        <form class="вход__форма" @submit.prevent="войти">
            <h1>Таблица финансов</h1>

            <label class="поле">
                Почта
                <input v-model="email" type="email" autocomplete="username" required />
            </label>

            <label class="поле">
                Пароль
                <input v-model="password" type="password" autocomplete="current-password" required />
            </label>

            <p v-if="authentication.errorMessage !== null" class="сообщение-об-ошибке">
                {{ authentication.errorMessage }}
            </p>

            <button class="кнопка" type="submit" :disabled="authentication.isBusy">Войти</button>
        </form>
    </div>
</template>
