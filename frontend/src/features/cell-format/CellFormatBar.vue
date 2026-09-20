<script setup lang="ts">
import type { CellFormat } from '../../entities/sheet';

/**
 * Набор заливок вместо произвольного выбора цвета: в таблице расходов краской
 * помечают несколько видов строк, и готовый набор быстрее и опрятнее пипетки.
 */
const ЗАЛИВКИ: ReadonlyArray<{ цвет: string | null; подпись: string }> = [
    { цвет: null, подпись: 'Без заливки' },
    { цвет: '#FFF3B0', подпись: 'Жёлтая' },
    { цвет: '#D6F5D6', подпись: 'Зелёная' },
    { цвет: '#FFD9D9', подпись: 'Красная' },
    { цвет: '#DCE7FB', подпись: 'Синяя' },
];

/**
 * Выравнивание задаётся явно, когда не устраивает обычное: текст жмётся влево,
 * число — вправо.
 */
const ВЫРАВНИВАНИЯ: ReadonlyArray<{ значение: 'left' | 'center' | 'right'; подпись: string; знак: string }> = [
    { значение: 'left', подпись: 'Прижать влево', знак: '⇤' },
    { значение: 'center', подпись: 'По центру', знак: '↔' },
    { значение: 'right', подпись: 'Прижать вправо', знак: '⇥' },
];

const properties = defineProps<{
    format: CellFormat;
}>();

const emit = defineEmits<{
    изменить: [изменения: CellFormat];
}>();

function переключитьКурсив(): void {
    emit('изменить', { italic: properties.format.italic === true ? undefined : true });
}

function выбратьВыравнивание(значение: 'left' | 'center' | 'right'): void {
    emit('изменить', { align: properties.format.align === значение ? undefined : значение });
}
</script>

<template>
    <div class="оформление">
        <button
            type="button"
            class="оформление__кнопка"
            :class="{ 'оформление__кнопка--включена': format.bold === true }"
            aria-label="Жирный шрифт"
            title="Жирный шрифт"
            @click="emit('изменить', { bold: format.bold === true ? undefined : true })"
        >
            Ж
        </button>

        <button
            type="button"
            class="оформление__кнопка оформление__кнопка--курсив"
            :class="{ 'оформление__кнопка--включена': format.italic === true }"
            aria-label="Курсив"
            title="Курсив"
            @click="переключитьКурсив"
        >
            К
        </button>

        <span class="оформление__разделитель"></span>

        <button
            v-for="выравнивание in ВЫРАВНИВАНИЯ"
            :key="выравнивание.значение"
            type="button"
            class="оформление__кнопка"
            :class="{ 'оформление__кнопка--включена': format.align === выравнивание.значение }"
            :aria-label="выравнивание.подпись"
            :title="выравнивание.подпись"
            @click="выбратьВыравнивание(выравнивание.значение)"
        >
            {{ выравнивание.знак }}
        </button>

        <span class="оформление__разделитель"></span>

        <button
            v-for="заливка in ЗАЛИВКИ"
            :key="заливка.подпись"
            type="button"
            class="оформление__заливка"
            :class="{ 'оформление__заливка--выбрана': (format.background ?? null) === заливка.цвет }"
            :style="{ background: заливка.цвет ?? 'transparent' }"
            :aria-label="заливка.подпись"
            :title="заливка.подпись"
            @click="emit('изменить', { background: заливка.цвет ?? undefined })"
        >
            {{ заливка.цвет === null ? '✕' : '' }}
        </button>

        <span class="оформление__разделитель"></span>

        <label class="оформление__знаки">
            Знаков
            <select
                :value="format.decimals ?? ''"
                aria-label="Знаков после запятой"
                @change="emit('изменить', { decimals: ($event.target as HTMLSelectElement).value === '' ? undefined : Number(($event.target as HTMLSelectElement).value) })"
            >
                <option value="">как есть</option>
                <option value="0">0</option>
                <option value="2">2</option>
            </select>
        </label>
    </div>
</template>
