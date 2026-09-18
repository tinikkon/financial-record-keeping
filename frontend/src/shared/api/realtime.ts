import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { tokenStorage } from './tokens';

let echo: Echo<'reverb'> | null = null;

/**
 * Подключение к серверу живых обновлений.
 *
 * Подпись подписки на закрытый канал проверяет сервис таблиц, поэтому запрос
 * авторизации несёт тот же токен доступа, что и обычные вызовы API.
 */
export function realtimeConnection(): Echo<'reverb'> {
    if (echo !== null) {
        return echo;
    }

    window.Pusher = Pusher;

    echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_WEBSOCKET_KEY,
        wsHost: import.meta.env.VITE_WEBSOCKET_HOST,
        wsPort: Number(import.meta.env.VITE_WEBSOCKET_PORT),
        forceTLS: false,
        enabledTransports: ['ws'],
        authEndpoint: `${import.meta.env.VITE_SHEET_API_URL}/api/broadcasting/auth`,
        auth: {
            headers: {
                Authorization: `Bearer ${tokenStorage.read()?.accessToken ?? ''}`,
            },
        },
    });

    return echo;
}

export function dropRealtimeConnection(): void {
    echo?.disconnect();
    echo = null;
}

declare global {
    interface Window {
        Pusher: typeof Pusher;
    }
}
