import { expect, test } from '@playwright/test';
import { вписать, войти, новыйМесяц, ячейка } from './вспомогательное';

test('месяц переименовывается двойным щелчком по вкладке', async ({ page }) => {
    await войти(page);
    const месяц = await новыйМесяц(page);

    await page.locator('.вкладки__вкладка', { hasText: месяц }).dblclick();
    await page.getByLabel('Новое название месяца').fill(`${месяц}-новое`);
    await page.getByLabel('Новое название месяца').press('Enter');

    await expect(page.locator('.вкладки__вкладка--активная')).toHaveText(`${месяц}-новое`);

    await page.reload();
    await expect(page.locator('.вкладки__вкладка--активная')).toHaveText(`${месяц}-новое`, { timeout: 20000 });
});

test('месяц удаляется только после подтверждения', async ({ page }) => {
    await войти(page);
    const месяц = await новыйМесяц(page);

    await page.getByRole('button', { name: 'Удалить открытый месяц' }).click();
    await expect(page.locator('.вкладки__вкладка--активная')).toHaveText(месяц);

    await page.getByRole('button', { name: 'Точно удалить месяц?' }).click();

    await expect(page.locator('.вкладки__вкладка', { hasText: месяц })).toHaveCount(0);
});

test('ширина колонки тянется за край заголовка и переживает перезагрузку', async ({ page }) => {
    await войти(page);
    await новыйМесяц(page);

    await вписать(page, 'B', 3, '120');

    const былаШирина = (await ячейка(page, 'B', 3).boundingBox())?.width ?? 0;
    const граница = page.getByLabel('Ширина колонки B');
    const место = await граница.boundingBox();

    await page.mouse.move(место!.x + место!.width / 2, место!.y + место!.height / 2);
    await page.mouse.down();
    await page.mouse.move(место!.x + место!.width / 2 + 60, место!.y + место!.height / 2, { steps: 5 });
    await page.mouse.up();

    await expect
        .poll(async () => (await ячейка(page, 'B', 3).boundingBox())?.width ?? 0)
        .toBeGreaterThan(былаШирина + 40);

    await page.reload();
    await expect(ячейка(page, 'B', 3)).toHaveText('120', { timeout: 20000 });
    await expect
        .poll(async () => (await ячейка(page, 'B', 3).boundingBox())?.width ?? 0)
        .toBeGreaterThan(былаШирина + 40);
});

test('курсив и выравнивание видны в ячейке', async ({ page }) => {
    await войти(page);
    await новыйМесяц(page);

    await вписать(page, 'B', 3, '120');
    await ячейка(page, 'B', 3).click();

    await page.getByRole('button', { name: 'Курсив' }).click();
    await page.getByRole('button', { name: 'По центру' }).click();

    await expect(ячейка(page, 'B', 3)).toHaveCSS('font-style', 'italic');
    await expect(ячейка(page, 'B', 3)).toHaveCSS('text-align', 'center');
});
