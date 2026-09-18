<script setup lang="ts">
import { nextTick, ref } from 'vue';
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
}>();

type Способ = 'новый' | 'копия';

const создаваемыйСпособ = ref<Способ | null>(null);
const новоеНазвание = ref('');
const полеНазвания = ref<HTMLInputElement | null>(null);

function начатьСоздание(способ: Способ): void {
    создаваемыйСпособ.value = способ;
    новоеНазвание.value = currentMonthName();

    void nextTick(() => полеНазвания.value?.select());
}

function подтвердить(): void {
    const название = новоеНазвание.value.trim();
    const способ = создаваемыйСпособ.value;

    отменить();

    if (название === '' || способ === null) {
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
    новоеНазвание.value = '';
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
            @click="emit('open', sheet.id)"
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
            :aria-label="создаваемыйСпособ === 'новый' ? 'Название нового месяца' : 'Название месяца-копии'"
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
        </template>
    </div>
</template>
