import { expect, test, type Page } from '@playwright/test';

const ПОЧТА = 'kostya@example.com';
const ПАРОЛЬ = 'finance-local-8';

async function войти(page: Page): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Почта').fill(ПОЧТА);
    await page.getByLabel('Пароль').fill(ПАРОЛЬ);
    await page.getByRole('button', { name: 'Войти' }).click();
    await expect(page.locator('.сетка')).toBeVisible({ timeout: 15000 });
}

test('вход открывает таблицу', async ({ page }) => {
    await войти(page);

    await expect(page.locator('.вкладки__вкладка').first()).toBeVisible();
    await expect(page.locator('.строка-формул__адрес')).toBeVisible();
});

test('введённое число сохраняется и видно после перезагрузки', async ({ page }) => {
    await войти(page);

    await page.locator('.сетка__ячейка').nth(1).click();
    await page.keyboard.type('120');
    await page.keyboard.press('Enter');

    await expect(page.locator('.сетка')).toContainText('120');

    await page.reload();
    await expect(page.locator('.сетка')).toContainText('120', { timeout: 15000 });
});
