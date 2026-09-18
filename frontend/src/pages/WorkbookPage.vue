<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import SheetGrid from '../features/sheet-grid/SheetGrid.vue';
import FormulaBar from '../features/sheet-grid/FormulaBar.vue';
import SheetTabs from '../features/sheet-tabs/SheetTabs.vue';
import CellHistoryPanel from '../features/history-panel/CellHistoryPanel.vue';
import CellFormatBar from '../features/cell-format/CellFormatBar.vue';
import WorkbookSummaryPanel from '../features/summary-panel/WorkbookSummaryPanel.vue';
import { useAuthStore } from '../stores/auth';
import { useSheetStore } from '../stores/sheet';
import { cellAddress, type CellFormat } from '../entities/sheet';
import { useHistoryStore } from '../stores/history';
import { useSummaryStore } from '../stores/summary';

const authentication = useAuthStore();
const sheet = useSheetStore();
const history = useHistoryStore();
const summary = useSummaryStore();
const router = useRouter();

const выбранная = ref({ row: 3, column: 1 });
const сетка = ref<InstanceType<typeof SheetGrid> | null>(null);

const выбраннаяЯчейка = computed(() => sheet.cells.get(cellAddress(выбранная.value.row, выбранная.value.column)));

onMounted(async () => {
    await authentication.loadCurrentUser();
    await sheet.openWorkbook();
});

async function сохранитьЯчейку(edit: { row: number; column: number; input: string | null }): Promise<void> {
    await sheet.applyEdits([edit]);
}

async function добавитьМесяц(название: string): Promise<void> {
    await sheet.createSheet(название);
}

async function скопироватьМесяц(название: string): Promise<void> {
    await sheet.duplicateSheet(название);
}

async function оформить(изменения: CellFormat): Promise<void> {
    await sheet.applyFormat(выбранная.value.row, выбранная.value.column, изменения);
}

async function показатьСводку(): Promise<void> {
    if (sheet.workbook === null) {
        return;
    }

    await summary.show(sheet.workbook.id);
}

async function вернутьКВерсии(версия: number): Promise<void> {
    await sheet.restoreToVersion(версия);
    history.close();
}

async function показатьИсторию(): Promise<void> {
    if (sheet.activeSheet === null) {
        return;
    }

    await history.showForCell(sheet.activeSheet.id, cellAddress(выбранная.value.row, выбранная.value.column));
}

async function выйти(): Promise<void> {
    sheet.reset();
    await authentication.logout();
    await router.push({ name: 'login' });
}
</script>

<template>
    <div class="приложение">
        <div class="полоса-состояния">
            <span class="полоса-состояния__связь">
                <span
                    class="полоса-состояния__огонёк"
                    :class="{ 'полоса-состояния__огонёк--нет-связи': !sheet.isConnected }"
                ></span>
                {{ sheet.isConnected ? 'Изменения приходят сразу' : 'Нет связи с сервером' }}
            </span>
            <span v-if="sheet.activeSheet !== null">Версия листа: {{ sheet.activeSheet.version }}</span>
            <span v-if="sheet.errorMessage !== null" class="сообщение-об-ошибке">{{ sheet.errorMessage }}</span>
            <span class="полоса-состояния__разделитель"></span>
            <button type="button" class="полоса-состояния__действие" @click="показатьИсторию">
                История ячейки
            </button>
            <button type="button" class="полоса-состояния__действие" @click="показатьСводку">Сводка</button>
            <span v-if="authentication.user !== null">{{ authentication.user.name }}</span>
            <button type="button" class="полоса-состояния__выход" @click="выйти">Выйти</button>
        </div>

        <CellFormatBar :format="выбраннаяЯчейка?.format ?? {}" @изменить="оформить" />

        <FormulaBar
            :row="выбранная.row"
            :column="выбранная.column"
            :input="выбраннаяЯчейка?.input ?? null"
            @edit="сетка?.начатьРедактирование()"
        />

        <SheetGrid
            v-if="sheet.activeSheet !== null"
            ref="сетка"
            :row-count="sheet.activeSheet.rowCount"
            :column-count="sheet.activeSheet.columnCount"
            :column-widths="sheet.activeSheet.columnWidths"
            :cells="sheet.cells"
            :selected="выбранная"
            @select="выбранная = $event"
            @commit="сохранитьЯчейку"
        />

        <CellHistoryPanel @вернуть="вернутьКВерсии" />
        <WorkbookSummaryPanel />

        <SheetTabs
            :sheets="sheet.sheets"
            :active-sheet-id="sheet.activeSheet?.id ?? null"
            @open="sheet.openSheet"
            @добавить-месяц="добавитьМесяц"
            @скопировать-месяц="скопироватьМесяц"
        />
    </div>
</template>
