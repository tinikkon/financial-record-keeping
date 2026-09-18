import { defineConfig, devices } from '@playwright/test';

const адресПриложения = process.env.APP_URL ?? 'http://finance.localhost:8088';

// Браузер берётся системный: зеркало браузеров Playwright бывает недоступно,
// а Chromium из репозитория Debian уже лежит в образе.
const путьКБраузеру = process.env.PLAYWRIGHT_CHROMIUM_PATH;

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
        {
            // Готовый профиль «Desktop Chrome» задаёт канал браузера и перебивает
            // явный путь, поэтому размеры окна указаны вручную.
            name: 'компьютер',
            use: {
                browserName: 'chromium',
                viewport: { width: 1280, height: 720 },
                launchOptions: { executablePath: путьКБраузеру },
            },
        },
        {
            name: 'телефон',
            use: {
                ...devices['Pixel 7'],
                channel: undefined,
                launchOptions: { executablePath: путьКБраузеру },
            },
        },
    ],
});
