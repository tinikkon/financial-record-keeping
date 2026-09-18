<script setup lang="ts">
import type { Sheet } from '../../entities/sheet';

defineProps<{
    sheets: Sheet[];
    activeSheetId: string | null;
}>();

const emit = defineEmits<{
    open: [sheetIdentifier: string];
    добавитьМесяц: [];
    скопироватьМесяц: [];
}>();
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
        <button type="button" class="вкладки__действие" title="Новый пустой месяц" @click="emit('добавитьМесяц')">
            +
        </button>
        <button
            type="button"
            class="вкладки__действие"
            title="Новый месяц копией текущего"
            @click="emit('скопироватьМесяц')"
        >
            ⧉
        </button>
    </div>
</template>
