<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import type { Sheet } from '../../entities/sheet';
import { currentMonthName } from '../../stores/sheet';

defineProps<{
    sheets: Sheet[];
    activeSheetId: string | null;
}>();

/*
 * Имена событий записаны сразу в дефисном виде. Vue переводит camelCase
 * в дефисный вид по латинским заглавным буквам, и кириллическая «М» под это
 * правило не попадает: слушатель @добавить-месяц с событием добавитьМесяц
 * просто не совпадёт, и обработчик не вызовется.
 */
const emit = defineEmits<{
    open: [sheetIdentifier: string];
    'добавить-месяц': [название: string];
    'скопировать-месяц': [название: string];
    'переименовать-месяц': [sheetIdentifier: string, название: string];
    'удалить-месяц': [sheetIdentifier: string];
}>();

type Способ = 'новый' | 'копия' | 'переименование';

const создаваемыйСпособ = ref<Способ | null>(null);
const новоеНазвание = ref('');
const переименовываемый = ref<string | null>(null);
const полеНазвания = ref<HTMLInputElement | null>(null);
// Удаление месяца спрашивает подтверждение прямо на кнопке: системное окно
// перекрывает страницу и на телефоне выглядит чужеродно.
const подтверждаемоеУдаление = ref<string | null>(null);

const подписьПоля = computed(() => {
    if (создаваемыйСпособ.value === 'переименование') {
        return 'Новое название месяца';
    }

    return создаваемыйСпособ.value === 'новый' ? 'Название нового месяца' : 'Название месяца-копии';
});

function начатьСоздание(способ: Способ): void {
    подтверждаемоеУдаление.value = null;
    создаваемыйСпособ.value = способ;
    новоеНазвание.value = currentMonthName();

    void nextTick(() => полеНазвания.value?.select());
}

function начатьПереименование(sheet: Sheet): void {
    подтверждаемоеУдаление.value = null;
    создаваемыйСпособ.value = 'переименование';
    переименовываемый.value = sheet.id;
    новоеНазвание.value = sheet.name;

    void nextTick(() => полеНазвания.value?.select());
}

function подтвердить(): void {
    const название = новоеНазвание.value.trim();
    const способ = создаваемыйСпособ.value;
    const лист = переименовываемый.value;

    отменить();

    if (название === '' || способ === null) {
        return;
    }

    if (способ === 'переименование') {
        if (лист !== null) {
            emit('переименовать-месяц', лист, название);
        }

        return;
    }

    if (способ === 'новый') {
        emit('добавить-месяц', название);

        return;
    }

    emit('скопировать-месяц', название);
}

function отменить(): void {
    создаваемыйСпособ.value = null;
    переименовываемый.value = null;
    новоеНазвание.value = '';
}

/**
 * Первый щелчок спрашивает, второй удаляет.
 */
function удалить(sheetIdentifier: string): void {
    if (подтверждаемоеУдаление.value !== sheetIdentifier) {
        подтверждаемоеУдаление.value = sheetIdentifier;

        return;
    }

    подтверждаемоеУдаление.value = null;
    emit('удалить-месяц', sheetIdentifier);
}

function открыть(sheetIdentifier: string): void {
    подтверждаемоеУдаление.value = null;
    emit('open', sheetIdentifier);
}
</script>

<template>
    <div class="вкладки">
        <button
            v-for="sheet in sheets"
            :key="sheet.id"
            type="button"
            class="вкладки__вкладка"
            :class="{ 'вкладки__вкладка--активная': sheet.id === activeSheetId }"
            @click="открыть(sheet.id)"
            @dblclick="начатьПереименование(sheet)"
        >
            {{ sheet.name }}
        </button>

        <!-- Название нового месяца вводится прямо здесь. Системное окно запроса
             перекрывает страницу и на телефоне выглядит чужеродно. -->
        <input
            v-if="создаваемыйСпособ !== null"
            ref="полеНазвания"
            v-model="новоеНазвание"
            class="вкладки__название"
            :aria-label="подписьПоля"
            @keydown.enter.prevent="подтвердить"
            @keydown.esc.prevent="отменить"
            @blur="отменить"
        />

        <template v-else>
            <button
                type="button"
                class="вкладки__действие"
                title="Новый пустой месяц"
                aria-label="Новый пустой месяц"
                @click="начатьСоздание('новый')"
            >
                +
            </button>
            <button
                type="button"
                class="вкладки__действие"
                title="Новый месяц копией текущего"
                aria-label="Новый месяц копией текущего"
                @click="начатьСоздание('копия')"
            >
                ⧉
            </button>
            <button
                v-if="activeSheetId !== null"
                type="button"
                class="вкладки__действие"
                :class="{ 'вкладки__действие--опасное': подтверждаемоеУдаление === activeSheetId }"
                :title="подтверждаемоеУдаление === activeSheetId ? 'Точно удалить месяц?' : 'Удалить открытый месяц'"
                :aria-label="подтверждаемоеУдаление === activeSheetId ? 'Точно удалить месяц?' : 'Удалить открытый месяц'"
                @click="удалить(activeSheetId)"
            >
                {{ подтверждаемоеУдаление === activeSheetId ? 'точно?' : '✕' }}
            </button>
        </template>
    </div>
</template>
