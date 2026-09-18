import { test } from '@playwright/test';
import { вписать, войти, новыйМесяц } from './вспомогательное';

/**
 * Не проверка, а снимок для описания проекта: заполняет месяц так же, как
 * выглядела исходная таблица в Excel, и сохраняет картинку.
 */
test('снимок заполненного месяца', async ({ page }) => {
    test.skip(process.env.СНИМКИ !== '1', 'Снимки делаются отдельным прогоном');

    await page.setViewportSize({ width: 1280, height: 720 });
    await войти(page);
    await новыйМесяц(page);

    await вписать(page, 'A', 2, 'назначение');
    await вписать(page, 'B', 2, 'расход, бел');
    await вписать(page, 'C', 2, 'расход, рос');
    await вписать(page, 'D', 2, 'приход, рос');
    await вписать(page, 'F', 2, '0,03485535');

    await вписать(page, 'A', 3, 'Продукты');
    await вписать(page, 'B', 3, '248,60');
    await вписать(page, 'A', 4, 'Бензин');
    await вписать(page, 'C', 4, '3500');
    await вписать(page, 'A', 5, 'Аренда');
    await вписать(page, 'B', 5, '520');
    await вписать(page, 'A', 6, 'Зарплата');
    await вписать(page, 'D', 6, '95000');

    await вписать(page, 'E', 12, 'итого росс. руб.');
    await вписать(page, 'D', 12, '=СУММ(D3:D11)-СУММ(C3:C11)');
    await вписать(page, 'E', 13, 'итого бел. руб.');
    await вписать(page, 'B', 13, '=СУММ(B3:B11)');
    await вписать(page, 'E', 14, 'остаток бел. руб.');
    await вписать(page, 'D', 14, '=D12*$F$2-B13');

    await page.locator('.сетка__ячейка').first().click();
    await page.waitForTimeout(500);

    await page.screenshot({ path: '../docs/screenshots/таблица.png' });
});
