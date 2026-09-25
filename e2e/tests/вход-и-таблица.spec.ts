import { readFile } from 'node:fs/promises';
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

test('текст доходит до ячейки целиком', async ({ page }) => {
    await войти(page);
    await новыйМесяц(page);

    await вписать(page, 'A', 2, 'назначение');
    await вписать(page, 'B', 2, 'расход, бел');

    await expect(ячейка(page, 'A', 2)).toHaveText('назначение');
    await expect(ячейка(page, 'B', 2)).toHaveText('расход, бел');
});

test('быстрый набор без открытой правки не теряет начало строки', async ({ page }) => {
    await войти(page);
    await новыйМесяц(page);

    // Набор идёт прямо по выбранной ячейке, как в Excel: первый же символ
    // открывает правку, и обогнать её появление нельзя.
    await ячейка(page, 'A', 2).click();
    await page.keyboard.type('rashod, bel');
    await page.keyboard.press('Enter');

    await expect(ячейка(page, 'A', 2)).toHaveText('rashod, bel');
});

test('открытая и не тронутая ячейка сохраняет содержимое', async ({ page }) => {
    await войти(page);
    await новыйМесяц(page);

    await вписать(page, 'B', 3, '120');

    // Правка открывается и бросается щелчком по соседке: пустое поле не должно
    // записаться поверх числа.
    await ячейка(page, 'B', 3).dblclick();
    await ячейка(page, 'C', 5).click();

    await expect(ячейка(page, 'B', 3)).toHaveText('120');
});

test('лист выгружается в CSV', async ({ page }) => {
    await войти(page);
    await новыйМесяц(page);

    await вписать(page, 'A', 2, 'Продукты');
    await вписать(page, 'B', 2, '248.60');

    const [файл] = await Promise.all([
        page.waitForEvent('download'),
        page.getByRole('button', { name: 'Выгрузить CSV' }).click(),
    ]);

    // Имя файла здесь не проверяется: Chromium из образа отдаёт для содержимого
    // из памяти имя «download», что бы ни стояло в ссылке. Заголовок с именем
    // проверяется тестом сервиса таблиц.
    const путь = await файл.path();
    const содержимое = await readFile(путь, 'utf8');

    expect(содержимое).toContain('Продукты;248,6');
});

test('вставка строки сдвигает содержимое и формулу', async ({ page }) => {
    await войти(page);
    await новыйМесяц(page);

    await вписать(page, 'B', 3, '120');
    await вписать(page, 'B', 4, '80');
    await вписать(page, 'B', 10, '=СУММ(B3:B9)');

    await ячейка(page, 'A', 4).click();
    await page.getByRole('button', { name: 'Вставить строку' }).click();

    await expect(ячейка(page, 'B', 5)).toHaveText('80');
    await expect(ячейка(page, 'B', 4)).toHaveText('');
    await expect(ячейка(page, 'B', 11)).toHaveText('200');
});

test('удаление строки поднимает нижние и пересчитывает итог', async ({ page }) => {
    await войти(page);
    await новыйМесяц(page);

    await вписать(page, 'B', 3, '120');
    await вписать(page, 'B', 4, '80');
    await вписать(page, 'B', 10, '=СУММ(B3:B9)');

    await ячейка(page, 'A', 4).click();
    await page.getByRole('button', { name: 'Удалить строку' }).click();

    await expect(ячейка(page, 'B', 4)).toHaveText('');
    await expect(ячейка(page, 'B', 9)).toHaveText('120');
});

test('вставка колонки сдвигает содержимое вправо вместе с формулой', async ({ page }) => {
    await войти(page);
    await новыйМесяц(page);

    await вписать(page, 'B', 2, '120');
    await вписать(page, 'C', 2, '80');
    await вписать(page, 'E', 2, '=СУММ(B2:C2)');

    await ячейка(page, 'C', 5).click();
    await page.getByRole('button', { name: 'Вставить колонку' }).click();

    await expect(ячейка(page, 'D', 2)).toHaveText('80');
    await expect(ячейка(page, 'C', 2)).toHaveText('');
    await expect(ячейка(page, 'F', 2)).toHaveText('200');
});

test('удаление колонки сдвигает правые и пересчитывает итог', async ({ page }) => {
    await войти(page);
    await новыйМесяц(page);

    await вписать(page, 'B', 2, '120');
    await вписать(page, 'C', 2, '80');
    await вписать(page, 'E', 2, '=СУММ(B2:D2)');

    await ячейка(page, 'C', 5).click();
    await page.getByRole('button', { name: 'Удалить колонку' }).click();

    await expect(ячейка(page, 'C', 2)).toHaveText('');
    await expect(ячейка(page, 'D', 2)).toHaveText('120');
});
