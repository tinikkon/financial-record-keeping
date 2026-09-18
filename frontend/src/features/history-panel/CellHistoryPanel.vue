<script setup lang="ts">
import { describeChange, formatMoment } from '../../entities/history';
import { useHistoryStore } from '../../stores/history';

const history = useHistoryStore();
</script>

<template>
    <aside v-if="history.isOpen" class="история">
        <header class="история__шапка">
            <strong>История ячейки {{ history.address }}</strong>
            <button type="button" class="история__закрыть" aria-label="Закрыть историю" @click="history.close()">
                ✕
            </button>
        </header>

        <p v-if="history.isLoading" class="история__сообщение">Загружаю…</p>
        <p v-else-if="history.errorMessage !== null" class="история__сообщение сообщение-об-ошибке">
            {{ history.errorMessage }}
        </p>
        <p v-else-if="history.changes.length === 0" class="история__сообщение">Эту ячейку ещё не меняли.</p>

        <ol v-else class="история__список">
            <li v-for="change in history.changes" :key="change.id" class="история__правка">
                <span class="история__когда">{{ formatMoment(change.occurredAt) }}</span>
                <span class="история__что">{{ describeChange(change) }}</span>
                <span v-if="change.inputAfter?.startsWith('=')" class="история__формула">
                    {{ change.inputAfter }}
                </span>
            </li>
        </ol>
    </aside>
</template>
