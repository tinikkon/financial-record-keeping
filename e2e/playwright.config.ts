import { defineConfig, devices } from '@playwright/test';

const адресПриложения = process.env.APP_URL ?? 'http://finance.localhost:8088';

export default defineConfig({
    testDir: './tests',
    // Тесты трогают одну и ту же книгу, поэтому идут по очереди.
    workers: 1,
    fullyParallel: false,
    reporter: [['list']],
    use: {
        baseURL: адресПриложения,
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    projects: [
        { name: 'компьютер', use: { ...devices['Desktop Chrome'] } },
        { name: 'телефон', use: { ...devices['Pixel 7'] } },
    ],
});
