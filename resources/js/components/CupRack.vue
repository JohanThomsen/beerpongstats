<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    cupsCount: 6 | 10;
    activePositions: number[]; // positions that still have cups (1..cupsCount)
    title?: string;
    clickable?: boolean;
    selectedId?: number | null;
    inverted?: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{ (e: 'select', position: number): void }>();

// Predefined position map (percent coordinates) for triangle layouts
// Numbering from tip (1) down to base (10)
const positions10 = [
    { id: 1, x: 50, y: 8 },
    { id: 2, x: 43, y: 24 },
    { id: 3, x: 57, y: 24 },
    { id: 4, x: 36, y: 40 },
    { id: 5, x: 50, y: 40 },
    { id: 6, x: 64, y: 40 },
    { id: 7, x: 29, y: 58 },
    { id: 8, x: 43, y: 58 },
    { id: 9, x: 57, y: 58 },
    { id: 10, x: 71, y: 58 },
] as const;

const positions6 = [
    { id: 1, x: 50, y: 16 },
    { id: 2, x: 43, y: 36 },
    { id: 3, x: 57, y: 36 },
    { id: 4, x: 36, y: 58 },
    { id: 5, x: 50, y: 58 },
    { id: 6, x: 64, y: 58 },
] as const;

const layout = computed(() => (props.cupsCount === 10 ? positions10 : positions6));

const isActive = (id: number) => props.activePositions.includes(id);

function onClick(id: number) {
    if (!props.clickable) return;
    if (!isActive(id)) return; // can't select removed cup
    emit('select', id);
}
</script>

<template>
    <div class="rack">
        <div v-if="title && !inverted" class="title">{{ title }}</div>
        <div class="board" :class="{ 'board--inverted': !!inverted }">
            <button
                v-for="p in layout"
                :key="p.id"
                class="cup"
                type="button"
                :style="{ left: p.x + '%', top: p.y + '%' }"
                :class="[
                    isActive(p.id) ? 'cup--active' : 'cup--gone',
                    clickable && isActive(p.id) ? 'cup--clickable' : '',
                    selectedId === p.id ? 'cup--selected' : '',
                ]"
                :aria-selected="selectedId === p.id"
                :title="`Cup ${p.id}`"
                @click="onClick(p.id)"
            >
                <span class="dot" />
            </button>
        </div>
        <div v-if="title && inverted" class="title">{{ title }}</div>
    </div>
</template>

<style scoped>
.rack { display: grid; gap: 0.5rem; }
.title { text-align: center; font-weight: 600; opacity: 0.8; }
.board { position: relative; width: 100%; max-width: 420px; aspect-ratio: 100 / 70; margin-inline: auto; }
.board--inverted { transform: rotate(180deg); transform-origin: center; }
.triangle { position: absolute; inset: 0; color: #64748b; }
.cup { position: absolute; transform: translate(-50%, -50%); width: 11%; aspect-ratio: 1; border-radius: 9999px; display: grid; place-items: center; border: 2px solid transparent; background: transparent; cursor: default; padding: 0; }
.cup--active { background: #f1f5f9; border-color: #cbd5e1; }
:where(html.dark) .cup--active { background: #0b1220; border-color: #1f2a44; }
.cup--gone { background: transparent; border-color: transparent; opacity: 0.25; }
.cup--clickable { cursor: pointer; box-shadow: 0 0 0 0 transparent; transition: box-shadow 120ms ease, transform 120ms ease; }
.cup--clickable:hover { box-shadow: 0 0 0 4px rgba(59,130,246,0.35); }
.cup--selected { box-shadow: 0 0 0 5px rgba(34,197,94,0.55); transform: translate(-50%, -50%) scale(1.06); }
.dot { width: 32%; aspect-ratio: 1; border-radius: 9999px; background: #3b82f6; opacity: 0.9; }
.cup--gone .dot { background: transparent; }
</style>
