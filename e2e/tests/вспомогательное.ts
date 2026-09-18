import { expect, type Page } from '@playwright/test';

export const ПОЧТА = 'kostya@example.com';
export const ПАРОЛЬ = 'finance-local-8';

export async function войти(page: Page): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Почта').fill(ПОЧТА);
    await page.getByLabel('Пароль').fill(ПАРОЛЬ);
    await page.getByRole('button', { name: 'Войти' }).click();
    await expect(page.locator('.сетка')).toBeVisible({ timeout: 20000 });
}

/**
 * Находит ячейку по адресу так же, как её видит пользователь: по заголовку
 * колонки и номеру строки, а не по порядковому номеру в разметке.
 */
export function ячейка(page: Page, колонка: string, строка: number) {
    const номерКолонки = колонка.charCodeAt(0) - 'A'.charCodeAt(0) + 1;

    return page.locator('.сетка__строка').filter({ has: page.locator('.сетка__номер', { hasText: new RegExp(`^${строка}$`) }) })
        .locator('.сетка__ячейка')
        .nth(номерКолонки - 1);
}

export async function вписать(page: Page, колонка: string, строка: number, значение: string): Promise<void> {
    await ячейка(page, колонка, строка).click();
    await page.keyboard.type(значение);
    await page.keyboard.press('Enter');
}

/**
 * Создаёт отдельный месяц под каждый прогон: тесты не должны видеть данные,
 * оставленные соседями.
 */
export async function новыйМесяц(page: Page): Promise<string> {
    const название = `тест-${Date.now()}-${Math.floor(Math.random() * 1000)}`;

    await page.getByRole('button', { name: 'Новый пустой месяц' }).click();
    await page.getByLabel('Название нового месяца').fill(название);
    await page.getByLabel('Название нового месяца').press('Enter');
    await expect(page.locator('.вкладки__вкладка--активная')).toHaveText(название, { timeout: 20000 });

    return название;
}
