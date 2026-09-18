import { tokenStorage } from './tokens';

export class ApiError extends Error {
    public constructor(
        message: string,
        public readonly status: number,
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

interface RequestOptions {
    method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
    body?: unknown;
    withoutAuthentication?: boolean;
}

/**
 * Клиент API с самостоятельным обновлением истёкшего токена доступа.
 *
 * Обновление идёт одним запросом на всех: если сразу несколько вызовов получат
 * отказ, второй и последующие дождутся уже запущенного обновления, а не начнут
 * своё. Иначе первое же обновление отозвало бы токен, которым пользуются остальные.
 */
export class ApiClient {
    private refreshing: Promise<boolean> | null = null;

    private onAuthenticationLost: (() => void) | null = null;

    public constructor(private readonly baseUrl: string) {}

    public whenAuthenticationLost(handler: () => void): void {
        this.onAuthenticationLost = handler;
    }

    public async request<TResponse>(path: string, options: RequestOptions = {}): Promise<TResponse> {
        const response = await this.send(path, options);

        if (response.status !== 401 || options.withoutAuthentication === true) {
            return this.toResult<TResponse>(response);
        }

        const refreshed = await this.refreshTokens();

        if (!refreshed) {
            this.onAuthenticationLost?.();

            throw new ApiError('Требуется вход', 401);
        }

        return this.toResult<TResponse>(await this.send(path, options));
    }

    private async send(path: string, options: RequestOptions): Promise<Response> {
        const headers: Record<string, string> = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        };

        const tokens = tokenStorage.read();

        if (options.withoutAuthentication !== true && tokens !== null) {
            headers.Authorization = `Bearer ${tokens.accessToken}`;
        }

        return fetch(`${this.baseUrl}/api/${path}`, {
            method: options.method ?? 'GET',
            headers,
            body: options.body === undefined ? undefined : JSON.stringify(options.body),
        });
    }

    private async toResult<TResponse>(response: Response): Promise<TResponse> {
        if (response.status === 204) {
            return undefined as TResponse;
        }

        const payload: unknown = await response.json().catch(() => null);

        if (response.ok) {
            return payload as TResponse;
        }

        const message =
            payload !== null && typeof payload === 'object' && 'message' in payload
                ? String((payload as { message: unknown }).message)
                : 'Не удалось выполнить запрос';

        throw new ApiError(message, response.status);
    }

    private async refreshTokens(): Promise<boolean> {
        this.refreshing ??= this.performRefresh().finally(() => {
            this.refreshing = null;
        });

        return this.refreshing;
    }

    private async performRefresh(): Promise<boolean> {
        const tokens = tokenStorage.read();

        if (tokens === null) {
            return false;
        }

        const response = await this.send('auth/refresh', {
            method: 'POST',
            body: { refreshToken: tokens.refreshToken },
            withoutAuthentication: true,
        });

        if (!response.ok) {
            tokenStorage.clear();

            return false;
        }

        const refreshed = (await response.json()) as { accessToken: string; refreshToken: string };
        tokenStorage.write(refreshed);

        return true;
    }
}

export const sheetApi = new ApiClient(import.meta.env.VITE_SHEET_API_URL);
export const historyApi = new ApiClient(import.meta.env.VITE_HISTORY_API_URL);
