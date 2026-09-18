const ХРАНИЛИЩЕ_ТОКЕНА_ДОСТУПА = 'finance.accessToken';
const ХРАНИЛИЩЕ_ТОКЕНА_ОБНОВЛЕНИЯ = 'finance.refreshToken';

export interface StoredTokens {
    accessToken: string;
    refreshToken: string;
}

/**
 * Токены живут в хранилище браузера: на телефоне вкладку закрывают постоянно,
 * и вход при каждом открытии сделал бы приложение неудобным.
 */
export const tokenStorage = {
    read(): StoredTokens | null {
        try {
            const accessToken = localStorage.getItem(ХРАНИЛИЩЕ_ТОКЕНА_ДОСТУПА);
            const refreshToken = localStorage.getItem(ХРАНИЛИЩЕ_ТОКЕНА_ОБНОВЛЕНИЯ);

            if (accessToken === null || refreshToken === null) {
                return null;
            }

            return { accessToken, refreshToken };
        } catch {
            return null;
        }
    },

    write(tokens: StoredTokens): void {
        try {
            localStorage.setItem(ХРАНИЛИЩЕ_ТОКЕНА_ДОСТУПА, tokens.accessToken);
            localStorage.setItem(ХРАНИЛИЩЕ_ТОКЕНА_ОБНОВЛЕНИЯ, tokens.refreshToken);
        } catch {
            // Приватный режим браузера может запрещать запись — приложение
            // продолжит работать, просто вход придётся повторить.
        }
    },

    clear(): void {
        try {
            localStorage.removeItem(ХРАНИЛИЩЕ_ТОКЕНА_ДОСТУПА);
            localStorage.removeItem(ХРАНИЛИЩЕ_ТОКЕНА_ОБНОВЛЕНИЯ);
        } catch {
            // Нечего чистить.
        }
    },
};
