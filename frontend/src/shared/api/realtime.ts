import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { tokenStorage } from './tokens';

let echo: Echo<'reverb'> | null = null;
let текущийКанал: string | null = null;

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

/**
 * Сообщает о появлении и пропаже связи.
 *
 * Внутренности библиотеки сокетов спрятаны здесь: хранилищу листа незачем знать,
 * как устроено подключение, а при подмене библиотеки менять придётся один файл.
 */
export function whenConnectionStateChanges(handler: (connected: boolean) => void): void {
    const connection = realtimeConnection().connector.pusher.connection;

    connection.bind('connected', () => handler(true));
    connection.bind('disconnected', () => handler(false));
    connection.bind('unavailable', () => handler(false));

    // Соединение могло установиться до подписки на события: тогда «connected»
    // уже прозвучало, и без этой строки полоса состояния так и осталась бы красной.
    handler(connection.state === 'connected');
}

/**
 * Подписка на изменения листа. Прежняя подписка снимается: при переходе
 * между месяцами иначе копились бы подписки на все открытые ранее листы.
 */
export function subscribeToSheet<TPacket>(sheetIdentifier: string, handler: (packet: TPacket) => void): void {
    const канал = `sheet.${sheetIdentifier}`;

    // Повторное открытие того же листа ничего не меняет. Снимать подписку
    // и тут же подписываться заново нельзя: отписка приходит на сервер после
    // подписки и гасит её, а обновления молча перестают приходить.
    if (текущийКанал === канал) {
        return;
    }

    const connection = realtimeConnection();

    if (текущийКанал !== null) {
        connection.leave(текущийКанал);
    }

    текущийКанал = канал;
    connection.private(канал).listen('.cells.changed', handler);
}

export function dropRealtimeConnection(): void {
    echo?.disconnect();
    echo = null;
    текущийКанал = null;
}

declare global {
    interface Window {
        Pusher: typeof Pusher;
    }
}
