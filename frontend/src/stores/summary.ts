import { defineStore } from 'pinia';
import { ref } from 'vue';
import { historyApi } from '../shared/api/client';
import type { SheetSummary } from '../entities/summary';

export const useSummaryStore = defineStore('summary', () => {
    const sheets = ref<SheetSummary[]>([]);
    const isOpen = ref(false);
    const isLoading = ref(false);
    const errorMessage = ref<string | null>(null);

    async function show(workbookIdentifier: string): Promise<void> {
        isOpen.value = true;
        isLoading.value = true;
        errorMessage.value = null;

        try {
            const response = await historyApi.request<{ sheets: SheetSummary[] }>(
                `workbooks/${workbookIdentifier}/summary`,
            );

            sheets.value = response.sheets;
        } catch (error) {
            sheets.value = [];
            errorMessage.value = error instanceof Error ? error.message : 'Сводка недоступна';
        } finally {
            isLoading.value = false;
        }
    }

    function close(): void {
        isOpen.value = false;
        sheets.value = [];
    }

    return { sheets, isOpen, isLoading, errorMessage, show, close };
});
