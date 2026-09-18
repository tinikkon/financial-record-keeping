<script setup lang="ts">
import { cellAddress } from '../../entities/sheet';

defineProps<{
    row: number;
    column: number;
    input: string | null;
}>();

const emit = defineEmits<{ edit: [] }>();
</script>

<template>
    <div class="строка-формул">
        <span class="строка-формул__адрес">{{ cellAddress(row, column) }}</span>
        <button class="строка-формул__значение" type="button" @click="emit('edit')">
            {{ input ?? '' }}
        </button>
    </div>
</template>
