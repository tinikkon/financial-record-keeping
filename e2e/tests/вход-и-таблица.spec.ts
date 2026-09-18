import { expect, test } from '@playwright/test';
import { вписать, войти, новыйМесяц, ячейка } from './вспомогательное';

test('вход открывает таблицу', async ({ page }) => {
    await войти(page);

    await expect(page.locator('.вкладки__вкладка').first()).toBeVisible();
    await expect(page.locator('.строка-формул__адрес')).toBeVisible();
});

test('введённое число сохраняется и видно после перезагрузки', async ({ page }) => {
    await войти(page);
    await новыйМесяц(page);

    await вписать(page, 'B', 3, '120');
    await expect(ячейка(page, 'B', 3)).toHaveText('120');

    await page.reload();
    await expect(ячейка(page, 'B', 3)).toHaveText('120', { timeout: 20000 });
});

test('формула суммы считает колонку и пересчитывается при правке', async ({ page }) => {
    await войти(page);
    await новыйМесяц(page);

    await вписать(page, 'B', 3, '120');
    await вписать(page, 'B', 4, '80');
    await вписать(page, 'B', 10, '=СУММ(B3:B9)');

    await expect(ячейка(page, 'B', 10)).toHaveText('200');

    await вписать(page, 'B', 5, '50');
    await expect(ячейка(page, 'B', 10)).toHaveText('250');
});

test('в строке формул видна формула, а в ячейке — её значение', async ({ page }) => {
    await войти(page);
    await новыйМесяц(page);

    await вписать(page, 'B', 3, '120');
    await вписать(page, 'B', 10, '=СУММ(B3:B9)');

    await ячейка(page, 'B', 10).click();

    await expect(page.locator('.строка-формул__адрес')).toHaveText('B10');
    await expect(page.locator('.строка-формул__значение')).toHaveText('=СУММ(B3:B9)');
    await expect(ячейка(page, 'B', 10)).toHaveText('120');
});

test('ошибка показывается в ячейке и не ломает страницу', async ({ page }) => {
    await войти(page);
    await новыйМесяц(page);

    await вписать(page, 'B', 3, '0');
    await вписать(page, 'C', 3, '=100/B3');

    await expect(ячейка(page, 'C', 3)).toHaveText('#ДЕЛ/0!');
    await expect(page.locator('.сетка')).toBeVisible();
});

test('связь с сервером устанавливается', async ({ page }) => {
    await войти(page);

    await expect(page.locator('.полоса-состояния')).toContainText('Изменения приходят сразу', { timeout: 20000 });
});
