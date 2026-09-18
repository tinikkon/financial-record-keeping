import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [vue()],
    server: {
        host: '0.0.0.0',
        port: 5173,
        // Заголовок Host приходит от traefik, и Vite должен его принимать.
        allowedHosts: true,
    },
});
