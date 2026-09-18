<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { cellAddress, columnToLetters, displayedText, type Cell } from '../../entities/sheet';

const ВЫСОТА_СТРОКИ = 30;
const ШИРИНА_КОЛОНКИ_ПО_УМОЛЧАНИЮ = 110;
const ЗАПАС_СТРОК = 6;

const properties = defineProps<{
    rowCount: number;
    columnCount: number;
    columnWidths: Record<string, number>;
    cells: Map<string, Cell>;
    selected: { row: number; column: number };
}>();

const emit = defineEmits<{
    select: [position: { row: number; column: number }];
    commit: [edit: { row: number; column: number; input: string | null }];
}>();

const область = ref<HTMLElement | null>(null);
// Ссылка внутри v-for превращается в массив, поэтому поле запоминается вручную:
// одновременно редактируется ровно одна ячейка, и хранить нужно один элемент.
const редактор = { поле: null as HTMLInputElement | null };
const смещениеПрокрутки = ref(0);
const высотаОбласти = ref(600);
const редактируемоеЗначение = ref<string | null>(null);

const колонки = computed(() => Array.from({ length: properties.columnCount }, (_, index) => index + 1));

/**
 * Отрисовываются только видимые строки: лист на двести строк и двадцать шесть
 * колонок — это пять тысяч ячеек, и держать их все в разметке незачем.
 */
const видимыеСтроки = computed(() => {
    const первая = Math.max(1, Math.floor(смещениеПрокрутки.value / ВЫСОТА_СТРОКИ) + 1 - ЗАПАС_СТРОК);
    const сколько = Math.ceil(высотаОбласти.value / ВЫСОТА_СТРОКИ) + ЗАПАС_СТРОК * 2;
    const последняя = Math.min(properties.rowCount, первая + сколько);

    return Array.from({ length: последняя - первая + 1 }, (_, index) => первая + index);
});

const отступОкна = computed(() => ((видимыеСтроки.value[0] ?? 1) - 1) * ВЫСОТА_СТРОКИ);
const полнаяВысота = computed(() => properties.rowCount * ВЫСОТА_СТРОКИ);
const редактируется = computed(() => редактируемоеЗначение.value !== null);

function ширинаКолонки(column: number): number {
    return properties.columnWidths[columnToLetters(column)] ?? ШИРИНА_КОЛОНКИ_ПО_УМОЛЧАНИЮ;
}

function ячейка(row: number, column: number): Cell | undefined {
    return properties.cells.get(cellAddress(row, column));
}

function выбрана(row: number, column: number): boolean {
    return properties.selected.row === row && properties.selected.column === column;
}

function приПрокрутке(event: Event): void {
    const цель = event.target as HTMLElement;
    смещениеПрокрутки.value = цель.scrollTop;
    высотаОбласти.value = цель.clientHeight;
}

function выбрать(row: number, column: number): void {
    завершитьРедактирование();
    emit('select', { row, column });
}

function начатьРедактирование(начальноеЗначение?: string): void {
    const текущая = ячейка(properties.selected.row, properties.selected.column);
    редактируемоеЗначение.value = начальноеЗначение ?? текущая?.input ?? '';

    void nextTick(() => редактор.поле?.focus());
}

function завершитьРедактирование(): void {
    редактируемоеЗначение.value = null;
}

function сохранить(): void {
    if (редактируемоеЗначение.value === null) {
        return;
    }

    const введённое = редактируемоеЗначение.value;
    завершитьРедактирование();

    emit('commit', {
        row: properties.selected.row,
        column: properties.selected.column,
        input: введённое.trim() === '' ? null : введённое,
    });
}

function сдвинуть(строкой: number, колонкой: number): void {
    const row = Math.min(Math.max(1, properties.selected.row + строкой), properties.rowCount);
    const column = Math.min(Math.max(1, properties.selected.column + колонкой), properties.columnCount);

    emit('select', { row, column });
}

function приНажатии(event: KeyboardEvent): void {
    if (редактируется.value) {
        return;
    }

    const действия: Record<string, () => void> = {
        ArrowUp: () => сдвинуть(-1, 0),
        ArrowDown: () => сдвинуть(1, 0),
        ArrowLeft: () => сдвинуть(0, -1),
        ArrowRight: () => сдвинуть(0, 1),
        Enter: () => начатьРедактирование(),
        F2: () => начатьРедактирование(),
        Tab: () => сдвинуть(0, 1),
        Delete: () => emit('commit', { ...properties.selected, input: null }),
        Backspace: () => emit('commit', { ...properties.selected, input: null }),
    };

    const действие = действия[event.key];

    if (действие !== undefined) {
        event.preventDefault();
        действие();

        return;
    }

    // Начало набора текста сразу открывает ячейку на правку, как в Excel.
    if (event.key.length === 1 && !event.ctrlKey && !event.metaKey) {
        event.preventDefault();
        начатьРедактирование(event.key);
    }
}

function приНажатииВПоле(event: KeyboardEvent): void {
    if (event.key === 'Enter') {
        event.preventDefault();
        сохранить();
        сдвинуть(1, 0);
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        завершитьРедактирование();
    }

    if (event.key === 'Tab') {
        event.preventDefault();
        сохранить();
        сдвинуть(0, 1);
    }
}

function стильЯчейки(cell: Cell | undefined): Record<string, string> {
    if (cell === undefined) {
        return {};
    }

    const стиль: Record<string, string> = {};

    if (cell.format.background !== undefined) {
        стиль.background = cell.format.background;
    }

    if (cell.format.bold === true) {
        стиль.fontWeight = '600';
    }

    if (cell.format.italic === true) {
        стиль.fontStyle = 'italic';
    }

    стиль.textAlign = cell.format.align ?? (cell.kind === 'text' ? 'left' : 'right');

    return стиль;
}

watch(
    () => properties.selected,
    () => завершитьРедактирование(),
);

onMounted(() => {
    // Сколько строк помещается на экране, известно только после отрисовки.
    высотаОбласти.value = область.value?.clientHeight ?? высотаОбласти.value;
});

defineExpose({ начатьРедактирование });
</script>

<template>
    <div class="сетка" tabindex="0" @keydown="приНажатии">
        <div class="сетка__шапка">
            <div class="сетка__угол"></div>
            <div
                v-for="column in колонки"
                :key="column"
                class="сетка__заголовок"
                :style="{ width: `${ширинаКолонки(column)}px` }"
            >
                {{ columnToLetters(column) }}
            </div>
        </div>

        <div ref="область" class="сетка__область" @scroll="приПрокрутке">
            <div class="сетка__полотно" :style="{ height: `${полнаяВысота}px` }">
                <div class="сетка__окно" :style="{ transform: `translateY(${отступОкна}px)` }">
                    <div v-for="row in видимыеСтроки" :key="row" class="сетка__строка">
                        <div class="сетка__номер">{{ row }}</div>
                        <div
                            v-for="column in колонки"
                            :key="column"
                            class="сетка__ячейка"
                            :class="{
                                'сетка__ячейка--выбрана': выбрана(row, column),
                                'сетка__ячейка--ошибка': ячейка(row, column)?.error != null,
                            }"
                            :style="{ width: `${ширинаКолонки(column)}px`, ...стильЯчейки(ячейка(row, column)) }"
                            @click="выбрать(row, column)"
                            @dblclick="начатьРедактирование()"
                        >
                            <input
                                v-if="выбрана(row, column) && редактируется"
                                :ref="(элемент) => (редактор.поле = элемент as HTMLInputElement | null)"
                                v-model="редактируемоеЗначение"
                                class="сетка__ввод"
                                :inputmode="редактируемоеЗначение?.startsWith('=') ? 'text' : 'decimal'"
                                @keydown="приНажатииВПоле"
                                @blur="сохранить"
                            />
                            <template v-else>{{ displayedText(ячейка(row, column)) }}</template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
