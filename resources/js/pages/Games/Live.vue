<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CupRack from '@/components/CupRack.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { echo } from '@laravel/echo-vue';
import { useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import type { GameType, GameUpdateType } from '@/types';

interface Props {
    game: { id: number; type: GameType; is_solo: boolean; is_ended: boolean };
    participants: Record<string, unknown>;
    latestGameUpdate: null | {
        id: number;
        user_id: number | null;
        type: GameUpdateType;
        self_cup_positions: number[] | null;
        opponent_cup_positions: number[] | null;
        self_cups_left: number;
        opponent_cups_left: number;
        affected_cup: number | null;
        created_at: string;
    };
    isParticipant: boolean;
    authUserId: number | null;
    throwStats: Record<string | number, { total: number; hits: number; edges: number; misses: number; hitRate: number; edgeRate: number; missRate: number }>;
}

const props = defineProps<Props>();

const cupsCount = computed<6 | 10>(() => (props.game.type === 'TEN_CUP' ? 10 : 6));

function fullPositions(count: number): number[] {
    return Array.from({ length: count }, (_, i) => i + 1);
}

const state = reactive({
    bottomPositions: [] as number[],
    topPositions: [] as number[],
});

// Build local reactive stats map keyed by numeric user id
const stats = reactive(new Map<number, { total: number; hits: number; edges: number; misses: number; hitRate: number; edgeRate: number; missRate: number }>());
for (const [k, v] of Object.entries(props.throwStats ?? {})) {
    const id = Number(k);
    stats.set(id, { ...v });
}

function bumpStats(userId: number | null | undefined, type: GameUpdateType | string) {
    if (userId == null) return;
    if (type !== 'HIT' && type !== 'EDGE' && type !== 'MISS') return;
    const s = stats.get(userId) ?? { total: 0, hits: 0, edges: 0, misses: 0, hitRate: 0, edgeRate: 0, missRate: 0 };
    s.total += 1;
    if (type === 'HIT') s.hits += 1;
    if (type === 'EDGE') s.edges += 1;
    if (type === 'MISS') s.misses += 1;
    const t = s.total || 0;
    s.hitRate = t ? Math.round((s.hits / t) * 1000) / 10 : 0;
    s.edgeRate = t ? Math.round((s.edges / t) * 1000) / 10 : 0;
    s.missRate = t ? Math.round((s.misses / t) * 1000) / 10 : 0;
    stats.set(userId, s);
}

const selectedTop = ref<number | null>(null);
const selectedBottom = ref<number | null>(null);

// Use Inertia form for CSRF-safe requests
const updateForm = useForm({
    user_id: null as number | null,
    type: '' as GameUpdateType,
    self_cup_positions: [] as number[],
    opponent_cup_positions: [] as number[],
    self_cups_left: 0,
    opponent_cups_left: 0,
    affected_cup: null as number | null,
});

// --- Orientation (define BEFORE orientAndSet uses isBottomUser) ---
const bottomActors = computed<Array<{ id: number; name: string }>>(() => {
    const authId = props.authUserId;
    if (props.game.is_solo) {
        const solo = ((props.participants as any)?.solo ?? []) as Array<{ id: number; name: string }>;
        if (!solo?.length) return [];
        if (authId && solo.find((u) => u.id === authId)) {
            const me = solo.find((u) => u.id === authId)!;
            return [me];
        }
        return solo[0] ? [solo[0]] : [];
    }
    const teams = (props.participants as any)?.teams ?? [];
    if (!teams?.length) return [];
    if (authId) {
        const idxAuthTeam = [0, 1].find((i) => teams?.[i]?.users?.some?.((u: any) => u.id === authId));
        if (idxAuthTeam !== undefined) return (teams?.[idxAuthTeam!]?.users ?? []) as Array<{ id: number; name: string }>;
    }
    return (teams?.[0]?.users ?? []) as Array<{ id: number; name: string }>;
});

const topActors = computed<Array<{ id: number; name: string }>>(() => {
    const authId = props.authUserId;
    if (props.game.is_solo) {
        const solo = ((props.participants as any)?.solo ?? []) as Array<{ id: number; name: string }>;
        if (authId && solo.find((u) => u.id === authId)) {
            const other = solo.find((u) => u.id !== authId);
            return other ? [other] : [];
        }
        return solo[1] ? [solo[1]] : [];
    }
    const teams = (props.participants as any)?.teams ?? [];
    if (!teams?.length) return [];
    if (authId) {
        const idxAuthTeam = [0, 1].find((i) => teams?.[i]?.users?.some?.((u: any) => u.id === authId));
        const otherIdx = idxAuthTeam === 0 ? 1 : 0;
        return (teams?.[otherIdx!]?.users ?? []) as Array<{ id: number; name: string }>;
    }
    return (teams?.[1]?.users ?? []) as Array<{ id: number; name: string }>;
});

const bottomUserIds = computed<number[]>(() => bottomActors.value.map((u) => u.id));
function isBottomUser(userId: number | null | undefined): boolean {
    if (userId == null) return true;
    return bottomUserIds.value.includes(userId);
}
// --- End orientation ---

function ensureSelectionsValid() {
    if (selectedTop.value != null && !state.topPositions.includes(selectedTop.value)) {
        selectedTop.value = null;
    }
    if (selectedBottom.value != null && !state.bottomPositions.includes(selectedBottom.value)) {
        selectedBottom.value = null;
    }
}

function orientAndSet(self: number[] | null, opp: number[] | null, actorUserId: number | null) {
    if (!Array.isArray(self) || !Array.isArray(opp)) return false;
    if (isBottomUser(actorUserId)) {
        state.bottomPositions = [...self];
        state.topPositions = [...opp];
    } else {
        state.bottomPositions = [...opp];
        state.topPositions = [...self];
    }
    ensureSelectionsValid();
    return true;
}

function hydrateFromLatest() {
    const latest = props.latestGameUpdate;
    if (!latest || !orientAndSet(latest.self_cup_positions, latest.opponent_cup_positions, latest.user_id)) {
        state.bottomPositions = fullPositions(cupsCount.value);
        state.topPositions = fullPositions(cupsCount.value);
    }
    ensureSelectionsValid();
}

hydrateFromLatest();

watch(cupsCount, () => hydrateFromLatest());

// Labels oriented with auth at bottom when possible
const participantsLabelBottom = computed(() => {
    if (props.game.is_solo) {
        const solo = ((props.participants as any)?.solo ?? []) as Array<{ id: number; name: string }>;
        const authId = props.authUserId;
        if (authId && solo.find((u) => u.id === authId)) return solo.find((u) => u.id === authId)?.name ?? 'Team one';
        return solo[0]?.name ?? 'Team one';
    }
    const teams = (props.participants as any)?.teams ?? [];
    const authId = props.authUserId;
    let bottomTeamName = teams?.[0]?.name ?? 'Team one';
    if (authId) {
        const idxAuthTeam = [0, 1].find((i) => teams?.[i]?.users?.some?.((u: any) => u.id === authId));
        bottomTeamName = teams?.[idxAuthTeam ?? 0]?.name ?? bottomTeamName;
    }
    return bottomTeamName;
});

const participantsLabelTop = computed(() => {
    if (props.game.is_solo) {
        const solo = ((props.participants as any)?.solo ?? []) as Array<{ id: number; name: string }>;
        const authId = props.authUserId;
        if (authId && solo.find((u) => u.id === authId)) return solo.find((u) => u.id !== authId)?.name ?? 'Team two';
        return solo[1]?.name ?? 'Team two';
    }
    const teams = (props.participants as any)?.teams ?? [];
    const authId = props.authUserId;
    let topTeamName = teams?.[1]?.name ?? 'Team two';
    if (authId) {
        const idxAuthTeam = [0, 1].find((i) => teams?.[i]?.users?.some?.((u: any) => u.id === authId));
        const otherIdx = idxAuthTeam === 0 ? 1 : 0;
        topTeamName = teams?.[otherIdx ?? 1]?.name ?? topTeamName;
    }
    return topTeamName;
});

function getCsrfToken(): string | null {
    const el = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    return el?.content ?? null;
}

async function createUpdate(type: GameUpdateType, actor: 'bottom' | 'top', actingUserId: number) {
    if (!canEdit.value) return;

    const actorSelf = actor === 'bottom' ? [...state.bottomPositions] : [...state.topPositions];
    const actorOpp = actor === 'bottom' ? [...state.topPositions] : [...state.bottomPositions];

    const revert = { bottom: [...state.bottomPositions], top: [...state.topPositions] };

    let affectedCup: number | null = null;

    if (type === 'HIT') {
        // HIT requires a selected opposing cup and removes it
        if (actor === 'bottom' && selectedTop.value != null) {
            affectedCup = selectedTop.value;
            state.topPositions = state.topPositions.filter((p) => p !== selectedTop.value);
            selectedTop.value = null;
        } else if (actor === 'top' && selectedBottom.value != null) {
            affectedCup = selectedBottom.value;
            state.bottomPositions = state.bottomPositions.filter((p) => p !== selectedBottom.value);
            selectedBottom.value = null;
        } else {
            // No cup selected for HIT; do nothing
            return;
        }
    } else if (type === 'EDGE') {
        // EDGE requires a selected opposing cup but does NOT remove it; send affected_cup for stats
        if (actor === 'bottom' && selectedTop.value != null) {
            affectedCup = selectedTop.value;
        } else if (actor === 'top' && selectedBottom.value != null) {
            affectedCup = selectedBottom.value;
        } else {
            // No cup selected for EDGE; do nothing
            return;
        }
    }

    // Update the form data
    updateForm.user_id = actingUserId;
    updateForm.type = type;
    updateForm.self_cup_positions = [...actorSelf];
    updateForm.opponent_cup_positions = [...actorOpp];
    updateForm.self_cups_left = actorSelf.length;
    updateForm.opponent_cups_left = actorOpp.length;
    updateForm.affected_cup = affectedCup;

    // For HIT, update opponent cup positions to reflect the removed cup
    if (type === 'HIT' && affectedCup) {
        updateForm.opponent_cup_positions = updateForm.opponent_cup_positions.filter(p => p !== affectedCup);
        updateForm.opponent_cups_left = updateForm.opponent_cup_positions.length;
    }

    // NO optimistic stats bump - let WebSocket handle this

    // Use Inertia's post method which handles CSRF automatically
    updateForm.post(route('games.updates.store', { game: props.game.id }), {
        preserveState: true,
        preserveScroll: true,
        onError: () => {
            // Revert optimistic changes on error
            state.bottomPositions = revert.bottom;
            state.topPositions = revert.top;
            ensureSelectionsValid();
        },
        onSuccess: () => {
            // Clear the form for next use
            updateForm.reset();
        }
    });
}

function listenWs() {
    const channel = `game-updates.${props.game.id}`;
    echo()
        .channel(channel)
        .listen('.game-update', (e: any) => {
            if (Array.isArray(e.self_cup_positions) && Array.isArray(e.opponent_cup_positions)) {
                orientAndSet(e.self_cup_positions, e.opponent_cup_positions, e.user_id ?? null);
            }
            // Update throw stats live
            bumpStats(e.user_id, e.type);
            ensureSelectionsValid();
        });
}

onMounted(() => listenWs());
onUnmounted(() => {
    try {
        echo().leave(`game-updates.${props.game.id}`);
    } catch {}
});

const canEdit = computed(() => !!props.isParticipant && !!props.authUserId && !props.game.is_ended);
const canHitTop = computed(() => selectedTop.value != null);
const canHitBottom = computed(() => selectedBottom.value != null);
</script>

<template>
    <AppLayout>
        <div class="px-4 py-6 space-y-8">
            <div class="text-center">
                <Heading :title="`Live game`" :subtitle="`Game #${game.id} • ${game.type === 'TEN_CUP' ? '10' : '6'} cups`" />
            </div>

            <!-- Top side: opponent; triangle inverted visually -->
            <section class="space-y-3">
                <CupRack
                    :cups-count="cupsCount"
                    :active-positions="state.topPositions"
                    :selected-id="selectedTop"
                    :title="participantsLabelTop"
                    :inverted="true"
                    :clickable="canEdit"
                    @select="(id) => (selectedTop = id)"
                />
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div v-for="p in bottomActors" :key="p.id" class="space-y-1 border rounded px-3 py-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium truncate">{{ p.name }}</span>
                            <div class="flex items-center gap-1">
                                <Button size="sm" variant="outline" :disabled="!canEdit || !canHitTop" @click="createUpdate('HIT','bottom', p.id)">Hit</Button>
                                <Button size="sm" variant="outline" :disabled="!canEdit || !canHitTop" @click="createUpdate('EDGE','bottom', p.id)">Edge</Button>
                                <Button size="sm" variant="outline" :disabled="!canEdit" @click="createUpdate('MISS','bottom', p.id)">Miss</Button>
                            </div>
                        </div>
                        <div class="text-xs text-muted-foreground">
                            <template v-if="stats.get(p.id)">
                                {{ stats.get(p.id)!.hitRate }}% hit, {{ stats.get(p.id)!.edgeRate }}% edge, {{ stats.get(p.id)!.missRate }}% miss
                            </template>
                            <template v-else>
                                0% hit, 0% edge, 0% miss
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            <div class="text-center">
                <div class="text-sm text-muted-foreground">Team one</div>
            </div>

            <!-- Bottom side: authenticated user's team when possible -->
            <section class="space-y-3">
                <CupRack
                    :cups-count="cupsCount"
                    :active-positions="state.bottomPositions"
                    :selected-id="selectedBottom"
                    :title="participantsLabelBottom"
                    :clickable="canEdit"
                    @select="(id) => (selectedBottom = id)"
                />
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div v-for="p in topActors" :key="p.id" class="space-y-1 border rounded px-3 py-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium truncate">{{ p.name }}</span>
                            <div class="flex items-center gap-1">
                                <Button size="sm" variant="outline" :disabled="!canEdit || !canHitBottom" @click="createUpdate('HIT','top', p.id)">Hit</Button>
                                <Button size="sm" variant="outline" :disabled="!canEdit || !canHitBottom" @click="createUpdate('EDGE','top', p.id)">Edge</Button>
                                <Button size="sm" variant="outline" :disabled="!canEdit" @click="createUpdate('MISS','top', p.id)">Miss</Button>
                            </div>
                        </div>
                        <div class="text-xs text-muted-foreground">
                            <template v-if="stats.get(p.id)">
                                {{ stats.get(p.id)!.hitRate }}% hit, {{ stats.get(p.id)!.edgeRate }}% edge, {{ stats.get(p.id)!.missRate }}% miss
                            </template>
                            <template v-else>
                                0% hit, 0% edge, 0% miss
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            <div v-if="!props.isParticipant" class="text-center text-sm text-muted-foreground">
                Viewing live. Log in and join the game to submit updates.
            </div>
            <div v-else-if="props.game.is_ended" class="text-center text-sm text-muted-foreground">
                Game has ended. Editing disabled.
            </div>
        </div>
    </AppLayout>
</template>
