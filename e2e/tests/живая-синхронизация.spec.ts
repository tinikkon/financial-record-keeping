import { expect, test } from '@playwright/test';
import { вписать, войти, новыйМесяц, ячейка } from './вспомогательное';

test('правка в одном браузере появляется во втором без перезагрузки', async ({ browser }) => {
    const мой = await browser.newContext();
    const женин = await browser.newContext();

    const мояСтраница = await мой.newPage();
    const еёСтраница = await женин.newPage();

    await войти(мояСтраница);
    const месяц = await новыйМесяц(мояСтраница);

    await войти(еёСтраница);
    await еёСтраница.locator('.вкладки__вкладка', { hasText: месяц }).click();
    await expect(еёСтраница.locator('.полоса-состояния')).toContainText('Изменения приходят сразу', { timeout: 20000 });

    await вписать(мояСтраница, 'B', 3, '120');

    // Ничего не нажимаем и не перезагружаем: значение должно приехать само.
    await expect(ячейка(еёСтраница, 'B', 3)).toHaveText('120', { timeout: 15000 });

    await мой.close();
    await женин.close();
});

test('пересчитанный итог тоже приезжает сам', async ({ browser }) => {
    const мой = await browser.newContext();
    const женин = await browser.newContext();

    const мояСтраница = await мой.newPage();
    const еёСтраница = await женин.newPage();

    await войти(мояСтраница);
    const месяц = await новыйМесяц(мояСтраница);
    await вписать(мояСтраница, 'B', 3, '120');
    await вписать(мояСтраница, 'B', 10, '=СУММ(B3:B9)');

    await войти(еёСтраница);
    await еёСтраница.locator('.вкладки__вкладка', { hasText: месяц }).click();
    await expect(ячейка(еёСтраница, 'B', 10)).toHaveText('120', { timeout: 20000 });

    await вписать(мояСтраница, 'B', 4, '80');

    await expect(ячейка(еёСтраница, 'B', 4)).toHaveText('80', { timeout: 15000 });
    await expect(ячейка(еёСтраница, 'B', 10)).toHaveText('200', { timeout: 15000 });

    await мой.close();
    await женин.close();
});

test('после обрыва связи данные подтягиваются при возврате', async ({ browser }) => {
    const мой = await browser.newContext();
    const женин = await browser.newContext();

    const мояСтраница = await мой.newPage();
    const еёСтраница = await женин.newPage();

    await войти(мояСтраница);
    const месяц = await новыйМесяц(мояСтраница);

    await войти(еёСтраница);
    await еёСтраница.locator('.вкладки__вкладка', { hasText: месяц }).click();
    await expect(еёСтраница.locator('.полоса-состояния')).toContainText('Изменения приходят сразу', { timeout: 20000 });

    // Телефон ушёл в карман: связь пропала, правка сделана без неё.
    await женин.setOffline(true);
    await вписать(мояСтраница, 'B', 3, '777');

    await женин.setOffline(false);
    await еёСтраница.reload();

    await expect(ячейка(еёСтраница, 'B', 3)).toHaveText('777', { timeout: 20000 });

    await мой.close();
    await женин.close();
});
