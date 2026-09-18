<script setup lang="ts">
import { useSummaryStore } from '../../stores/summary';

const summary = useSummaryStore();
</script>

<template>
    <aside v-if="summary.isOpen" class="история">
        <header class="история__шапка">
            <strong>Сводка по книге</strong>
            <button type="button" class="история__закрыть" aria-label="Закрыть сводку" @click="summary.close()">
                ✕
            </button>
        </header>

        <p v-if="summary.isLoading" class="история__сообщение">Считаю…</p>
        <p v-else-if="summary.errorMessage !== null" class="история__сообщение сообщение-об-ошибке">
            {{ summary.errorMessage }}
        </p>
        <p v-else-if="summary.sheets.length === 0" class="история__сообщение">
            Пока нечего показывать: в книге нет заполненных чисел.
        </p>

        <div v-else class="история__список">
            <section v-for="лист in summary.sheets" :key="лист.sheetId" class="сводка__месяц">
                <h3 class="сводка__название">{{ лист.sheetName }}</h3>
                <p class="сводка__правки">правок: {{ лист.changes }}</p>

                <table class="сводка__итоги">
                    <tbody>
                        <tr v-for="колонка in лист.columns" :key="колонка.column">
                            <th scope="row">{{ колонка.column }}</th>
                            <td>{{ колонка.total }}</td>
                            <td class="сводка__ячеек">{{ колонка.filledCells }} яч.</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </aside>
</template>
