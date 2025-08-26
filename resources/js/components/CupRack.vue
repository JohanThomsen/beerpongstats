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
// Numbering from tip (1) down to base (10) - increased spacing for larger cups
const positions10 = [
    { id: 1, x: 50, y: 27 },
    { id: 2, x: 40, y: 47 },
    { id: 3, x: 60, y: 47 },
    { id: 4, x: 30, y: 67 },
    { id: 5, x: 50, y: 67 },
    { id: 6, x: 70, y: 67 },
    { id: 7, x: 20, y: 87 },
    { id: 8, x: 40, y: 87 },
    { id: 9, x: 60, y: 87 },
    { id: 10, x: 80, y: 87 },
] as const;

const positions6 = [
    { id: 1, x: 50, y: 37 },
    { id: 2, x: 38, y: 62 },
    { id: 3, x: 62, y: 62 },
    { id: 4, x: 26, y: 87 },
    { id: 5, x: 50, y: 87 },
    { id: 6, x: 74, y: 87 },
] as const;

const layout = computed(() => (props.cupsCount === 10 ? positions10 : positions6));

const isActive = (id: number) => props.activePositions.includes(id);

function onClick(id: number) {
    if (!props.clickable) return;
    if (!isActive(id)) return; // can't select removed cup
    emit('select', id);
}

// Handle touch events for mobile devices
function onTouchStart(event: TouchEvent, id: number) {
    // Prevent default to avoid hover effects on mobile
    event.preventDefault();
    onClick(id);
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
                    isActive(p.id) ? 'cup--active' : 'cup--hit',
                    clickable && isActive(p.id) ? 'cup--clickable' : '',
                    selectedId === p.id ? 'cup--selected' : '',
                ]"
                :aria-selected="selectedId === p.id"
                :title="isActive(p.id) ? `Cup ${p.id}` : `Cup ${p.id} (Hit)`"
                @click="onClick(p.id)"
                @touchstart="onTouchStart($event, p.id)"
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

.cup {
    position: absolute;
    transform: translate(-50%, -50%);
    width: 16%;
    aspect-ratio: 1;
    border-radius: 9999px;
    display: grid;
    place-items: center;
    border: 2px solid transparent;
    background: transparent;
    cursor: default;
    padding: 0;
    /* Improve touch targets for mobile */
    min-width: 44px;
    min-height: 44px;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
}

.cup--active { background: #f1f5f9; border-color: #cbd5e1; }
:where(html.dark) .cup--active { background: #0b1220; border-color: #1f2a44; }
.cup--hit { background: #fef2f2; border-color: #fecaca; opacity: 0.7; }
:where(html.dark) .cup--hit { background: #1f1416; border-color: #3f1f1f; opacity: 0.7; }
.cup--gone { background: transparent; border-color: transparent; opacity: 0.25; }

.cup--clickable {
    cursor: pointer;
    box-shadow: 0 0 0 0 transparent;
    transition: box-shadow 120ms ease, transform 120ms ease;
}

/* Only apply hover effects on devices that support hover (not touch devices) */
@media (hover: hover) and (pointer: fine) {
    .cup--clickable:hover {
        box-shadow: 0 0 0 4px rgba(59,130,246,0.35);
    }
}

/* Touch feedback for mobile devices */
@media (hover: none) and (pointer: coarse) {
    .cup--clickable:active {
        transform: translate(-50%, -50%) scale(0.95);
        box-shadow: 0 0 0 3px rgba(59,130,246,0.5);
    }
}

.cup--selected {
    box-shadow: 0 0 0 5px rgba(34,197,94,0.55);
    transform: translate(-50%, -50%) scale(1.06);
}

.dot {
    width: 32%;
    aspect-ratio: 1;
    border-radius: 9999px;
    background: #3b82f6;
    opacity: 0.9;
    pointer-events: none; /* Prevent dot from intercepting touch events */
}

.cup--gone .dot { background: transparent; }
.cup--hit .dot { background: #ef4444; opacity: 0.8; }
</style>
