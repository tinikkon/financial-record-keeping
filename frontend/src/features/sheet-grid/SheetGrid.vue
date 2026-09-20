<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { cellAddress, columnToLetters, displayedText, type Cell } from '../../entities/sheet';

const ШИРИНА_КОЛОНКИ_ПО_УМОЛЧАНИЮ = 110;
const НАИМЕНЬШАЯ_ШИРИНА_КОЛОНКИ = 40;
const НАИБОЛЬШАЯ_ШИРИНА_КОЛОНКИ = 600;
const ЗАПАС_СТРОК = 6;
const ВЫСОТА_СТРОКИ_ПО_УМОЛЧАНИЮ = 30;
const ШИРИНА_НОМЕРА_ПО_УМОЛЧАНИЮ = 52;

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
    'ширина-колонки': [колонка: string, ширина: number];
    'выделение': [диапазон: { startRow: number; startColumn: number; endRow: number; endColumn: number }];
    'очистить-выделение': [];
}>();

const корень = ref<HTMLElement | null>(null);
const область = ref<HTMLElement | null>(null);
// Поле ввода одно на всю сетку и живёт в разметке всегда, просто становится
// невидимым. Пока оно создавалось под каждую правку, фокус приходилось ждать,
// и набранное до его появления пропадало.
const поле = ref<HTMLInputElement | null>(null);
const смещениеПрокрутки = ref(0);
const высотаОбласти = ref(600);
const высотаСтроки = ref(ВЫСОТА_СТРОКИ_ПО_УМОЛЧАНИЮ);
const ширинаНомера = ref(ШИРИНА_НОМЕРА_ПО_УМОЛЧАНИЮ);
const редактируется = ref(false);
const редактируемоеЗначение = ref('');
// Содержимое ячейки на момент начала правки: если человек ничего не изменил,
// ячейку трогать не за что.
const исходноеЗначение = ref('');
// Ширина, которую колонка показывает прямо во время перетаскивания: на сервер
// она уходит один раз, когда границу отпустили.
const растягиваемаяКолонка = ref<{ номер: number; ширина: number } | null>(null);
const началоРастягивания = { отX: 0, отШирины: 0 };
// Начало выделения. Диапазон — всё, что между ним и выбранной ячейкой, поэтому
// хранить достаточно одну точку.
const якорьВыделения = ref({ row: properties.selected.row, column: properties.selected.column });

const колонки = computed(() => Array.from({ length: properties.columnCount }, (_, index) => index + 1));

/**
 * Отрисовываются только видимые строки: лист на двести строк и двадцать шесть
 * колонок — это пять тысяч ячеек, и держать их все в разметке незачем.
 */
const видимыеСтроки = computed(() => {
    const первая = Math.max(1, Math.floor(смещениеПрокрутки.value / высотаСтроки.value) + 1 - ЗАПАС_СТРОК);
    const сколько = Math.ceil(высотаОбласти.value / высотаСтроки.value) + ЗАПАС_СТРОК * 2;
    const последняя = Math.min(properties.rowCount, первая + сколько);

    return Array.from({ length: последняя - первая + 1 }, (_, index) => первая + index);
});

const отступОкна = computed(() => ((видимыеСтроки.value[0] ?? 1) - 1) * высотаСтроки.value);
const полнаяВысота = computed(() => properties.rowCount * высотаСтроки.value);
const диапазонВыделения = computed(() => ({
    startRow: Math.min(якорьВыделения.value.row, properties.selected.row),
    endRow: Math.max(якорьВыделения.value.row, properties.selected.row),
    startColumn: Math.min(якорьВыделения.value.column, properties.selected.column),
    endColumn: Math.max(якорьВыделения.value.column, properties.selected.column),
}));

const режимВыделенияДиапазона = computed(
    () => диапазонВыделения.value.startRow !== диапазонВыделения.value.endRow
        || диапазонВыделения.value.startColumn !== диапазонВыделения.value.endColumn,
);

const режимВвода = computed(() => (редактируемоеЗначение.value.startsWith('=') ? 'text' : 'decimal'));

/**
 * Поле ввода стоит поверх выбранной ячейки, а вне правки сжимается в точку:
 * убрать его из разметки нельзя — тогда оно снова не успевало бы к первому
 * нажатию клавиши.
 */
const положениеПоля = computed(() => {
    const сверху = (properties.selected.row - 1) * высотаСтроки.value;
    const слева = отступКолонки(properties.selected.column);
    const положение: Record<string, string> = { top: `${сверху}px`, left: `${слева}px` };

    if (!редактируется.value) {
        return { ...положение, width: '0', height: '0', padding: '0', border: 'none', opacity: '0' };
    }

    return {
        ...положение,
        width: `${ширинаКолонки(properties.selected.column)}px`,
        height: `${высотаСтроки.value}px`,
    };
});

function ширинаКолонки(column: number): number {
    const растягиваемая = растягиваемаяКолонка.value;

    if (растягиваемая !== null && растягиваемая.номер === column) {
        return растягиваемая.ширина;
    }

    return properties.columnWidths[columnToLetters(column)] ?? ШИРИНА_КОЛОНКИ_ПО_УМОЛЧАНИЮ;
}

/**
 * Ширина колонки тянется мышью за правый край заголовка.
 *
 * Слежение вешается на весь документ: указатель во время перетаскивания уходит
 * далеко за пределы узкой полоски, за которую взялись.
 */
function начатьРастягивание(column: number, событие: MouseEvent): void {
    растягиваемаяКолонка.value = { номер: column, ширина: ширинаКолонки(column) };
    началоРастягивания.отX = событие.clientX;
    началоРастягивания.отШирины = ширинаКолонки(column);

    document.addEventListener('mousemove', приРастягивании);
    document.addEventListener('mouseup', закончитьРастягивание);
}

function приРастягивании(событие: MouseEvent): void {
    const растягиваемая = растягиваемаяКолонка.value;

    if (растягиваемая === null) {
        return;
    }

    const ширина = началоРастягивания.отШирины + (событие.clientX - началоРастягивания.отX);

    растягиваемаяКолонка.value = {
        номер: растягиваемая.номер,
        ширина: Math.min(Math.max(НАИМЕНЬШАЯ_ШИРИНА_КОЛОНКИ, Math.round(ширина)), НАИБОЛЬШАЯ_ШИРИНА_КОЛОНКИ),
    };
}

function закончитьРастягивание(): void {
    document.removeEventListener('mousemove', приРастягивании);
    document.removeEventListener('mouseup', закончитьРастягивание);

    const растягиваемая = растягиваемаяКолонка.value;
    растягиваемаяКолонка.value = null;

    if (растягиваемая === null) {
        return;
    }

    emit('ширина-колонки', columnToLetters(растягиваемая.номер), растягиваемая.ширина);
}

function отступКолонки(column: number): number {
    let отступ = ширинаНомера.value;

    for (let номер = 1; номер < column; номер += 1) {
        отступ += ширинаКолонки(номер);
    }

    return отступ;
}

function ячейка(row: number, column: number): Cell | undefined {
    return properties.cells.get(cellAddress(row, column));
}

function выбрана(row: number, column: number): boolean {
    return properties.selected.row === row && properties.selected.column === column;
}

function вВыделении(row: number, column: number): boolean {
    const диапазон = диапазонВыделения.value;

    return режимВыделенияДиапазона.value
        && row >= диапазон.startRow
        && row <= диапазон.endRow
        && column >= диапазон.startColumn
        && column <= диапазон.endColumn;
}

function приПрокрутке(event: Event): void {
    const цель = event.target as HTMLElement;
    смещениеПрокрутки.value = цель.scrollTop;
    высотаОбласти.value = цель.clientHeight;
}

function выбрать(row: number, column: number, событие: MouseEvent): void {
    сохранить();

    // Щелчок с shift растягивает выделение от прежнего начала, обычный —
    // начинает новое.
    if (!событие.shiftKey) {
        якорьВыделения.value = { row, column };
    }

    // Щелчок по ячейке возвращает фокус сетке: иначе после работы с вкладками
    // или панелями нажатия клавиш уходят в никуда и ввод не начинается.
    корень.value?.focus();

    emit('select', { row, column });
}

function начатьРедактирование(начальноеЗначение?: string): void {
    const текущая = ячейка(properties.selected.row, properties.selected.column);
    исходноеЗначение.value = текущая?.input ?? '';
    редактируемоеЗначение.value = начальноеЗначение ?? исходноеЗначение.value;
    редактируется.value = true;

    const элемент = поле.value;

    if (элемент === null) {
        return;
    }

    // Значение и фокус выставляются сразу, не дожидаясь перерисовки: иначе
    // следующие нажатия попадут в поле, где ещё пусто, и затрут начало строки.
    элемент.value = редактируемоеЗначение.value;
    элемент.focus();
    элемент.setSelectionRange(элемент.value.length, элемент.value.length);
}

function отменитьРедактирование(): void {
    редактируется.value = false;
    редактируемоеЗначение.value = '';
    исходноеЗначение.value = '';
}

/**
 * Записывает правку, если она вообще была.
 *
 * Открытая и не тронутая ячейка не должна ни во что превращаться: раньше
 * случайная потеря фокуса сохраняла пустое поле и стирала содержимое.
 */
function сохранить(): void {
    if (!редактируется.value) {
        return;
    }

    const введённое = редактируемоеЗначение.value;
    const изменилось = введённое !== исходноеЗначение.value;

    отменитьРедактирование();

    if (!изменилось) {
        return;
    }

    emit('commit', {
        row: properties.selected.row,
        column: properties.selected.column,
        input: введённое.trim() === '' ? null : введённое,
    });
}

function сдвинуть(строкой: number, колонкой: number, расширяя = false): void {
    const row = Math.min(Math.max(1, properties.selected.row + строкой), properties.rowCount);
    const column = Math.min(Math.max(1, properties.selected.column + колонкой), properties.columnCount);

    if (!расширяя) {
        якорьВыделения.value = { row, column };
    }

    emit('select', { row, column });
}

function приНажатии(event: KeyboardEvent): void {
    if (редактируется.value) {
        return;
    }

    const очистить = (): void => {
        if (режимВыделенияДиапазона.value) {
            emit('очистить-выделение');

            return;
        }

        emit('commit', { ...properties.selected, input: null });
    };

    const действия: Record<string, () => void> = {
        ArrowUp: () => сдвинуть(-1, 0, event.shiftKey),
        ArrowDown: () => сдвинуть(1, 0, event.shiftKey),
        ArrowLeft: () => сдвинуть(0, -1, event.shiftKey),
        ArrowRight: () => сдвинуть(0, 1, event.shiftKey),
        Enter: () => начатьРедактирование(),
        F2: () => начатьРедактирование(),
        Tab: () => сдвинуть(0, 1),
        Delete: очистить,
        Backspace: очистить,
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
    // Клавиши поля не должны обрабатываться ещё и сеткой: всплывший Enter
    // открывал правку заново, уже на соседней ячейке.
    event.stopPropagation();

    if (event.key === 'Enter') {
        event.preventDefault();
        сохранить();
        корень.value?.focus();
        сдвинуть(1, 0);

        return;
    }

    if (event.key === 'Tab') {
        event.preventDefault();
        сохранить();
        корень.value?.focus();
        сдвинуть(0, 1);

        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        отменитьРедактирование();
        корень.value?.focus();
    }
}

function приПотереФокуса(): void {
    сохранить();
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
    () => отменитьРедактирование(),
);

watch(диапазонВыделения, (диапазон) => emit('выделение', диапазон), { immediate: true });

onUnmounted(() => {
    document.removeEventListener('mousemove', приРастягивании);
    document.removeEventListener('mouseup', закончитьРастягивание);
});

onMounted(() => {
    // Размеры заданы переменными оформления и на телефоне другие, поэтому
    // берутся оттуда: сетка и поле ввода должны считать их одинаково.
    const оформление = getComputedStyle(document.documentElement);
    const высота = Number.parseInt(оформление.getPropertyValue('--высота-строки'), 10);
    const ширина = Number.parseInt(оформление.getPropertyValue('--ширина-номера-строки'), 10);

    высотаСтроки.value = Number.isNaN(высота) ? ВЫСОТА_СТРОКИ_ПО_УМОЛЧАНИЮ : высота;
    ширинаНомера.value = Number.isNaN(ширина) ? ШИРИНА_НОМЕРА_ПО_УМОЛЧАНИЮ : ширина;

    // Сколько строк помещается на экране, известно только после отрисовки.
    высотаОбласти.value = область.value?.clientHeight ?? высотаОбласти.value;
});

defineExpose({ начатьРедактирование });
</script>

<template>
    <div ref="корень" class="сетка" tabindex="0" @keydown="приНажатии">
        <div class="сетка__шапка">
            <div class="сетка__угол"></div>
            <div
                v-for="column in колонки"
                :key="column"
                class="сетка__заголовок"
                :style="{ width: `${ширинаКолонки(column)}px` }"
            >
                {{ columnToLetters(column) }}
                <span
                    class="сетка__граница-колонки"
                    :aria-label="`Ширина колонки ${columnToLetters(column)}`"
                    @mousedown.prevent="начатьРастягивание(column, $event)"
                ></span>
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
                                'сетка__ячейка--в-выделении': вВыделении(row, column),
                                'сетка__ячейка--ошибка': ячейка(row, column)?.error != null,
                            }"
                            :style="{ width: `${ширинаКолонки(column)}px`, ...стильЯчейки(ячейка(row, column)) }"
                            @click="выбрать(row, column, $event)"
                            @dblclick="начатьРедактирование()"
                        >
                            {{ displayedText(ячейка(row, column)) }}
                        </div>
                    </div>
                </div>

                <input
                    ref="поле"
                    v-model="редактируемоеЗначение"
                    class="сетка__ввод"
                    :style="положениеПоля"
                    :inputmode="режимВвода"
                    @keydown="приНажатииВПоле"
                    @blur="приПотереФокуса"
                />
            </div>
        </div>
    </div>
</template>
