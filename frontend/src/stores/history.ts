import { defineStore } from 'pinia';
import { ref } from 'vue';
import { historyApi } from '../shared/api/client';
import type { CellChange } from '../entities/history';

export const useHistoryStore = defineStore('history', () => {
    const changes = ref<CellChange[]>([]);
    const isOpen = ref(false);
    const isLoading = ref(false);
    const errorMessage = ref<string | null>(null);
    const address = ref<string | null>(null);

    async function showForCell(sheetIdentifier: string, cellAddress: string): Promise<void> {
        isOpen.value = true;
        isLoading.value = true;
        errorMessage.value = null;
        address.value = cellAddress;

        try {
            const response = await historyApi.request<{ changes: CellChange[] }>(
                `sheets/${sheetIdentifier}/cells/${cellAddress}/history`,
            );

            changes.value = response.changes;
        } catch (error) {
            // История — вспомогательная возможность: её недоступность не должна
            // выглядеть как поломка всего приложения.
            changes.value = [];
            errorMessage.value = error instanceof Error ? error.message : 'История недоступна';
        } finally {
            isLoading.value = false;
        }
    }

    function close(): void {
        isOpen.value = false;
        changes.value = [];
        address.value = null;
    }

    return { changes, isOpen, isLoading, errorMessage, address, showForCell, close };
});
