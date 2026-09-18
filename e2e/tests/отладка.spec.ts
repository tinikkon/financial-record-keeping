import { test } from '@playwright/test';
import { вписать, войти, новыйМесяц } from './вспомогательное';

test('отладка живой синхронизации', async ({ browser }) => {
    test.skip(process.env.DEBUG_SYNC !== '1', 'Только для разбора');

    const мой = await browser.newContext();
    const женин = await browser.newContext();
    const мояСтраница = await мой.newPage();
    const еёСтраница = await женин.newPage();

    еёСтраница.on('console', (сообщение) => console.log('[консоль]', сообщение.type(), сообщение.text().slice(0, 200)));
    еёСтраница.on('pageerror', (ошибка) => console.log('[ошибка страницы]', ошибка.message.slice(0, 200)));
    еёСтраница.on('requestfailed', (запрос) => console.log('[запрос не удался]', запрос.url().slice(0, 120)));
    еёСтраница.on('response', (ответ) => {
        if (ответ.url().includes('broadcasting')) {
            console.log('[авторизация канала]', ответ.status(), ответ.url().slice(0, 100));
        }
    });
    еёСтраница.on('websocket', (сокет) => {
        console.log('[сокет]', сокет.url().slice(0, 100));
        сокет.on('framesent', (кадр) => console.log('[отправлено]', String(кадр.payload).slice(0, 160)));
        сокет.on('framereceived', (кадр) => console.log('[получено]', String(кадр.payload).slice(0, 160)));
    });

    await войти(мояСтраница);
    const месяц = await новыйМесяц(мояСтраница);

    await войти(еёСтраница);
    await еёСтраница.locator('.вкладки__вкладка', { hasText: месяц }).click();
    await еёСтраница.waitForTimeout(3000);

    await вписать(мояСтраница, 'B', 3, '120');
    await еёСтраница.waitForTimeout(5000);

    await мой.close();
    await женин.close();
});
