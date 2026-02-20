<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

type CalculationMode = 'simple' | 'expression';
type Operator = '+' | '-' | '*' | '/' | '^';

interface CalculationRecord {
    id: number;
    mode: CalculationMode;
    expression: string | null;
    left_operand: string | null;
    operator: Operator | null;
    right_operand: string | null;
    result: string;
    created_at: string;
}

interface ApiSuccess<T> {
    success: boolean;
    message: string | null;
    data: T;
}

const mode = ref<CalculationMode>('simple');
const left = ref<string>('');
const operator = ref<Operator>('+');
const right = ref<string>('');
const expression = ref<string>('sqrt((((9*9)/12)+(13-4))*2)^2');
const calculations = ref<CalculationRecord[]>([]);
const currentResult = ref<string | null>(null);
const errorMessage = ref<string | null>(null);
const loading = ref<boolean>(false);

const currentExpressionPreview = computed(() => {
    if (mode.value === 'expression') {
        return expression.value.trim();
    }

    return `${left.value.trim()} ${operator.value} ${right.value.trim()}`.trim();
});

const canSubmit = computed(() => {
    if (mode.value === 'expression') {
        return expression.value.trim().length > 0;
    }

    return left.value.trim() !== '' && right.value.trim() !== '';
});

async function loadHistory(): Promise<void> {
    const response = await fetch('/api/calculations');
    const payload: ApiSuccess<CalculationRecord[] | { data: CalculationRecord[] }> = await response.json();
    calculations.value = Array.isArray(payload.data) ? payload.data : (payload.data?.data ?? []);
}

async function submitCalculation(): Promise<void> {
    if (!canSubmit.value) {
        return;
    }

    loading.value = true;
    errorMessage.value = null;

    const body =
        mode.value === 'expression'
            ? { expression: expression.value.trim() }
            : { left: left.value.trim(), operator: operator.value, right: right.value.trim() };

    try {
        const response = await fetch('/api/calculations', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
            errorMessage.value = payload.message ?? 'Unable to perform the calculation.';
            return;
        }

        const record: CalculationRecord = payload.data.data ?? payload.data;
        currentResult.value = record.result;
        calculations.value = [record, ...calculations.value];
    } catch {
        errorMessage.value = 'Unexpected error while contacting the API.';
    } finally {
        loading.value = false;
    }
}

async function deleteCalculation(id: number): Promise<void> {
    const response = await fetch(`/api/calculations/${id}`, { method: 'DELETE' });
    if (response.ok) {
        calculations.value = calculations.value.filter((item) => item.id !== id);
    }
}

async function clearHistory(): Promise<void> {
    const response = await fetch('/api/calculations', { method: 'DELETE' });
    if (response.ok) {
        calculations.value = [];
    }
}

function formatTickerItem(item: CalculationRecord): string {
    if (item.mode === 'expression') {
        return `${item.expression} = ${item.result}`;
    }

    return `${item.left_operand} ${item.operator} ${item.right_operand} = ${item.result}`;
}

onMounted(async () => {
    await loadHistory();
});
</script>

<template>
    <Head title="Calculator" />

    <div class="min-h-screen bg-slate-950 px-4 py-8 text-slate-100">
        <div class="mx-auto grid w-full max-w-6xl gap-6 md:grid-cols-2">
            <section class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <h1 class="text-2xl font-semibold">API Calculator</h1>
                <p class="mt-1 text-sm text-slate-400">Supports simple operations and chained expressions with sqrt and exponent.</p>

                <div class="mt-6 inline-flex rounded-md border border-slate-700">
                    <button
                        class="px-4 py-2 text-sm"
                        :class="mode === 'simple' ? 'bg-slate-700 text-white' : 'bg-transparent text-slate-300'"
                        @click="mode = 'simple'"
                    >
                        Simple
                    </button>
                    <button
                        class="px-4 py-2 text-sm"
                        :class="mode === 'expression' ? 'bg-slate-700 text-white' : 'bg-transparent text-slate-300'"
                        @click="mode = 'expression'"
                    >
                        Expression
                    </button>
                </div>

                <form class="mt-6 space-y-4" @submit.prevent="submitCalculation">
                    <template v-if="mode === 'simple'">
                        <div class="grid grid-cols-3 gap-3">
                            <input
                                v-model="left"
                                type="text"
                                class="rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                                placeholder="Left operand"
                            />
                            <select v-model="operator" class="rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm">
                                <option value="+">+</option>
                                <option value="-">-</option>
                                <option value="*">*</option>
                                <option value="/">/</option>
                                <option value="^">^</option>
                            </select>
                            <input
                                v-model="right"
                                type="text"
                                class="rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                                placeholder="Right operand"
                            />
                        </div>
                    </template>

                    <template v-else>
                        <textarea
                            v-model="expression"
                            rows="3"
                            class="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                            placeholder="sqrt((((9*9)/12)+(13-4))*2)^2"
                        />
                    </template>

                    <button
                        type="submit"
                        class="w-full rounded-md bg-emerald-500 px-4 py-2 text-sm font-medium text-emerald-950 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="loading || !canSubmit"
                    >
                        {{ loading ? 'Calculating...' : 'Calculate' }}
                    </button>
                </form>

                <div class="mt-5 rounded-md border border-slate-800 bg-slate-950 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Preview</p>
                    <p class="mt-1 font-mono text-sm text-slate-200">{{ currentExpressionPreview }}</p>
                </div>

                <div v-if="currentResult !== null" class="mt-4 rounded-md border border-emerald-700/40 bg-emerald-900/20 p-4">
                    <p class="text-xs uppercase tracking-wide text-emerald-400">Result</p>
                    <p class="mt-1 text-xl font-semibold text-emerald-300">{{ currentResult }}</p>
                </div>

                <div v-if="errorMessage" class="mt-4 rounded-md border border-rose-700/40 bg-rose-900/20 p-4 text-sm text-rose-300">
                    {{ errorMessage }}
                </div>
            </section>

            <section class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold">Ticker Tape</h2>
                    <button
                        class="rounded-md border border-slate-700 px-3 py-1.5 text-xs text-slate-300 hover:bg-slate-800"
                        @click="clearHistory"
                    >
                        Clear All
                    </button>
                </div>

                <div v-if="calculations.length === 0" class="mt-6 rounded-md border border-dashed border-slate-700 p-6 text-center text-sm text-slate-400">
                    No calculations yet.
                </div>

                <ul v-else class="mt-4 space-y-2">
                    <li
                        v-for="item in calculations"
                        :key="item.id"
                        class="flex items-center justify-between rounded-md border border-slate-800 bg-slate-950 px-3 py-2"
                    >
                        <div>
                            <p class="font-mono text-sm text-slate-200">{{ formatTickerItem(item) }}</p>
                            <p class="text-xs text-slate-500">{{ new Date(item.created_at).toLocaleString() }}</p>
                        </div>
                        <button class="rounded border border-slate-700 px-2 py-1 text-xs text-slate-300 hover:bg-slate-800" @click="deleteCalculation(item.id)">
                            Delete
                        </button>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
