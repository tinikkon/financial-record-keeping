/// <reference types="vite/client" />

declare module '*.vue' {
    import type { DefineComponent } from 'vue';

    const component: DefineComponent<Record<string, never>, Record<string, never>, unknown>;

    export default component;
}

interface ImportMetaEnv {
    readonly VITE_SHEET_API_URL: string;
    readonly VITE_HISTORY_API_URL: string;
    readonly VITE_WEBSOCKET_HOST: string;
    readonly VITE_WEBSOCKET_PORT: string;
    readonly VITE_WEBSOCKET_KEY: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
