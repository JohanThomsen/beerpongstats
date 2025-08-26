<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CupRack from '@/components/CupRack.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
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

// Rerack modal state
const rerackModalOpen = ref(false);
const rerackSelectedTeam = ref<'top' | 'bottom' | null>(null);
const rerackTempPositions = ref<number[]>([]);
const rerackNewPositions = ref<number[]>([]);
const rerackStep = ref<'select-team' | 'position-cups'>('select-team');

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

// Check if opponent should be hidden (placeholder users/teams)
const shouldShowOpponent = computed(() => {
    if (props.game.is_solo) {
        // For solo games, check if any opponent is a placeholder user
        const opponents = topActors.value;
        return !opponents.some(opponent =>
            opponent.name === 'placeholder1' || opponent.name === 'placeholder2'
        );
    } else {
        // For team games, check if opponent team is placeholder team
        const teams = (props.participants as any)?.teams ?? [];
        const authId = props.authUserId;
        if (authId) {
            const idxAuthTeam = [0, 1].find((i) => teams?.[i]?.users?.some?.((u: any) => u.id === authId));
            const otherIdx = idxAuthTeam === 0 ? 1 : 0;
            const opponentTeam = teams?.[otherIdx!];
            return opponentTeam?.name !== 'placeholder team';
        } else {
            // If no auth user, check if second team is placeholder
            return teams?.[1]?.name !== 'placeholder team';
        }
    }
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

// Rerack functions
function openRerackModal() {
    rerackModalOpen.value = true;
    rerackSelectedTeam.value = null;
    rerackTempPositions.value = [];
    rerackNewPositions.value = [];

    // In practice mode, skip team selection and go straight to positioning the target cups
    if (!shouldShowOpponent.value) {
        rerackSelectedTeam.value = 'top'; // Always rerack the target cups in practice mode
        rerackTempPositions.value = [...state.topPositions];
        rerackNewPositions.value = [];
        rerackStep.value = 'position-cups';
    } else {
        rerackStep.value = 'select-team';
    }
}

function selectTeamForRerack(team: 'top' | 'bottom') {
    rerackSelectedTeam.value = team;
    const currentPositions = team === 'top' ? state.topPositions : state.bottomPositions;
    rerackTempPositions.value = [...currentPositions];
    rerackNewPositions.value = [];
    rerackStep.value = 'position-cups';
}

function proceedToPositioning() {
    rerackNewPositions.value = [];
    rerackStep.value = 'position-cups';
}

function placeCupAtPosition(position: number) {
    if (rerackStep.value !== 'position-cups') return;

    // Don't allow placing on occupied positions
    if (rerackNewPositions.value.includes(position)) return;

    // Add this position to the new positions
    if (rerackNewPositions.value.length < rerackTempPositions.value.length) {
        rerackNewPositions.value.push(position);
    }
}

function removeCupFromPosition(position: number) {
    if (rerackStep.value !== 'position-cups') return;
    const index = rerackNewPositions.value.indexOf(position);
    if (index > -1) {
        rerackNewPositions.value.splice(index, 1);
    }
}

function applyRerack() {
    if (!rerackSelectedTeam.value || rerackNewPositions.value.length !== rerackTempPositions.value.length) return;

    // Create update based on which team is being reracked
    const actingUserId = props.authUserId!;
    const isBottomTeamRerack = rerackSelectedTeam.value === 'bottom';

    // Update the positions optimistically
    if (isBottomTeamRerack) {
        state.bottomPositions = [...rerackNewPositions.value];
    } else {
        state.topPositions = [...rerackNewPositions.value];
    }

    // Prepare form data for the update
    updateForm.user_id = actingUserId;
    updateForm.type = 'RERACK' as GameUpdateType;

    if (isBottomTeamRerack) {
        updateForm.self_cup_positions = [...rerackNewPositions.value];
        updateForm.opponent_cup_positions = [...state.topPositions];
        updateForm.self_cups_left = rerackNewPositions.value.length;
        updateForm.opponent_cups_left = state.topPositions.length;
    } else {
        updateForm.self_cup_positions = [...state.bottomPositions];
        updateForm.opponent_cup_positions = [...rerackNewPositions.value];
        updateForm.self_cups_left = state.bottomPositions.length;
        updateForm.opponent_cups_left = rerackNewPositions.value.length;
    }

    updateForm.affected_cup = null;

    // Submit the rerack update
    updateForm.post(route('games.updates.store', { game: props.game.id }), {
        preserveState: true,
        preserveScroll: true,
        onError: () => {
            // Revert optimistic changes on error
            hydrateFromLatest();
        },
        onSuccess: () => {
            // Clear the form for next use
            updateForm.reset();
            // Close modal
            rerackModalOpen.value = false;
        }
    });
}

function cancelRerack() {
    rerackModalOpen.value = false;
    rerackSelectedTeam.value = null;
    rerackTempPositions.value = [];
    rerackNewPositions.value = [];
    rerackStep.value = 'select-team';
}

// Cup rack layout positions - using the same layout as CupRack component but with increased spacing
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

const rerackLayout = computed(() => (cupsCount.value === 10 ? positions10 : positions6));

// End the practice game (simulate opponent hitting all remaining cups)
function endPracticeGame() {
    if (!canEdit.value || !shouldShowOpponent.value === false) return;

    // Get the first opponent actor (placeholder user/team)
    const opponentActor = topActors.value[0];
    if (!opponentActor) return;

    // Clear all player cups (simulate opponent hitting everything)
    state.bottomPositions = [];

    // Update the form data to simulate opponent hitting all cups
    updateForm.user_id = opponentActor.id;  // Use opponent's ID
    updateForm.type = 'HIT';
    updateForm.self_cup_positions = [...state.topPositions];  // Opponent's cups stay the same
    updateForm.opponent_cup_positions = [];  // Player's cups are all gone
    updateForm.self_cups_left = state.topPositions.length;
    updateForm.opponent_cups_left = 0;  // Player has 0 cups left
    updateForm.affected_cup = null;

    // Submit the game-ending update from opponent's perspective
    updateForm.post(route('games.updates.store', { game: props.game.id }), {
        preserveState: true,
        preserveScroll: true,
        onError: () => {
            // Revert on error
            hydrateFromLatest();
        },
        onSuccess: () => {
            // Clear the form for next use
            updateForm.reset();
        }
    });
}
</script>

<template>
    <AppLayout>
        <div class="px-4 py-6 space-y-8">
            <div class="text-center">
                <Heading :title="`Live game`" :subtitle="`Game #${game.id} • ${game.type === 'TEN_CUP' ? '10' : '6'} cups`" />
            </div>

            <!-- Top side: opponent; triangle inverted visually -->
            <section v-if="shouldShowOpponent" class="space-y-0">
                <div class="grid gap-2 cols-2">
                    <div v-for="p in topActors" :key="p.id" class="space-y-1 border rounded px-3 py-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium truncate">{{ p.name }}</span>
                        </div>
                        <div class="text-xs text-muted-foreground">
                            <template v-if="stats.get(p.id)">
                                {{ stats.get(p.id)!.hitRate }}% hit, {{ stats.get(p.id)!.edgeRate }}% edge, {{ stats.get(p.id)!.missRate }}% miss
                            </template>
                            <template v-else>
                                0% hit, 0% edge, 0% miss
                            </template>
                        </div>
                        <!-- Full width row for action buttons -->
                        <div class="grid grid-cols-4 gap-1 mt-2">
                            <Button size="sm" variant="outline" :disabled="!canEdit || !canHitBottom" @click="createUpdate('HIT','top', p.id)" class="border-green-500 text-green-700 hover:bg-green-50 disabled:border-gray-300 disabled:text-gray-400">Hit</Button>
                            <Button size="sm" variant="outline" :disabled="!canEdit || !canHitBottom" @click="createUpdate('EDGE','top', p.id)" class="border-yellow-500 text-yellow-700 hover:bg-yellow-50 disabled:border-gray-300 disabled:text-gray-400">Edge</Button>
                            <Button size="sm" variant="outline" :disabled="!canEdit" @click="createUpdate('MISS','top', p.id)" class="border-red-500 text-red-700 hover:bg-red-50 disabled:border-gray-300 disabled:text-gray-400">Miss</Button>
                            <Button size="sm" variant="outline" :disabled="!canEdit" @click="openRerackModal" class="border-blue-500 text-blue-700 hover:bg-blue-50 disabled:border-gray-300 disabled:text-gray-400">Rerack</Button>
                        </div>
                    </div>
                </div>
                <CupRack
                    :cups-count="cupsCount"
                    :active-positions="state.topPositions"
                    :selected-id="selectedTop"
                    :title="participantsLabelTop"
                    :inverted="true"
                    :clickable="canEdit"
                    @select="(id) => (selectedTop = id)"
                />
            </section>

            <!-- Top side cups only (for practice mode) -->
            <section v-if="!shouldShowOpponent" class="space-y-0">
                <CupRack
                    :cups-count="cupsCount"
                    :active-positions="state.topPositions"
                    :selected-id="selectedTop"
                    :title="'Opponent cups'"
                    :inverted="true"
                    :clickable="canEdit"
                    @select="(id) => (selectedTop = id)"
                />

                <!-- Practice mode controls -->
                <div class="flex justify-center mt-4">
                    <Button
                        size="sm"
                        variant="destructive"
                        :disabled="!canEdit"
                        @click="endPracticeGame"
                        class="bg-red-600 hover:bg-red-700"
                    >
                        Lost
                    </Button>
                </div>
            </section>

            <!-- Rerack Button -->
            <div class="flex justify-center">
                <Dialog v-model:open="rerackModalOpen">
                    <DialogTrigger as-child>
                        <Button
                            size="sm"
                            variant="outline"
                            :disabled="!canEdit"
                            @click="openRerackModal"
                        >
                            Rerack
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-lg">
                        <DialogHeader>
                            <DialogTitle>Rerack Cups</DialogTitle>
                        </DialogHeader>
                        <div class="space-y-4">
                            <p class="text-sm text-muted-foreground">
                                Select a team to rerack. All remaining cups will be repositioned.
                            </p>
                            <div v-if="rerackStep === 'select-team'" class="flex gap-2">
                                <Button
                                    class="flex-1"
                                    @click="selectTeamForRerack('top')"
                                >
                                    Rerack {{ participantsLabelTop }}
                                </Button>
                                <Button
                                    class="flex-1"
                                    @click="selectTeamForRerack('bottom')"
                                >
                                    Rerack {{ participantsLabelBottom }}
                                </Button>
                            </div>
                            <div v-else-if="rerackStep === 'position-cups'" class="space-y-4">
                                <p class="text-sm text-muted-foreground">
                                    Click on the rack positions where you want to place the {{ rerackTempPositions.length }} cups ({{ rerackNewPositions.length }}/{{ rerackTempPositions.length }} placed):
                                </p>

                                <!-- Visual Cup Rack for Positioning -->
                                <div class="rack">
                                    <div class="board">
                                        <button
                                            v-for="position in rerackLayout"
                                            :key="position.id"
                                            type="button"
                                            class="cup"
                                            :style="{ left: position.x + '%', top: position.y + '%' }"
                                            :class="[
                                                rerackNewPositions.includes(position.id) ? 'cup--placed' : 'cup--empty',
                                                'cup--clickable'
                                            ]"
                                            :title="`Position ${position.id}`"
                                            @click="rerackNewPositions.includes(position.id) ? removeCupFromPosition(position.id) : placeCupAtPosition(position.id)"
                                        >
                                            <span class="dot" />
                                        </button>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-2">
                                    <Button variant="outline" @click="() => rerackStep = 'select-team'">Back</Button>
                                    <Button variant="outline" @click="cancelRerack">Cancel</Button>
                                    <Button
                                        :disabled="rerackNewPositions.length !== rerackTempPositions.length"
                                        @click="applyRerack"
                                    >
                                        Apply Rerack ({{ rerackNewPositions.length }}/{{ rerackTempPositions.length }} cups)
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Bottom side: authenticated user's team when possible -->
            <section v-if="shouldShowOpponent" class="space-y-0">
                <CupRack
                    :cups-count="cupsCount"
                    :active-positions="state.bottomPositions"
                    :selected-id="selectedBottom"
                    :title="participantsLabelBottom"
                    :clickable="canEdit"
                    @select="(id) => (selectedBottom = id)"
                />
                <div class="grid gap-2 cols-2">
                    <div v-for="p in bottomActors" :key="p.id" class="space-y-1 border rounded px-3 py-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium truncate">{{ p.name }}</span>
                        </div>
                        <div class="text-xs text-muted-foreground">
                            <template v-if="stats.get(p.id)">
                                {{ stats.get(p.id)!.hitRate }}% hit, {{ stats.get(p.id)!.edgeRate }}% edge, {{ stats.get(p.id)!.missRate }}% miss
                            </template>
                            <template v-else">
                                0% hit, 0% edge, 0% miss
                            </template>
                        </div>
                        <!-- Full width row for action buttons -->
                        <div class="grid grid-cols-3 gap-1 mt-2">
                            <Button size="sm" variant="outline" :disabled="!canEdit || !canHitTop" @click="createUpdate('HIT','bottom', p.id)" class="border-green-500 text-green-700 hover:bg-green-50 disabled:border-gray-300 disabled:text-gray-400">Hit</Button>
                            <Button size="sm" variant="outline" :disabled="!canEdit || !canHitTop" @click="createUpdate('EDGE','bottom', p.id)" class="border-yellow-500 text-yellow-700 hover:bg-yellow-50 disabled:border-gray-300 disabled:text-gray-400">Edge</Button>
                            <Button size="sm" variant="outline" :disabled="!canEdit" @click="createUpdate('MISS','bottom', p.id)" class="border-red-500 text-red-700 hover:bg-red-50 disabled:border-gray-300 disabled:text-gray-400">Miss</Button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Practice mode: only show player action buttons (no cups) -->
            <section v-if="!shouldShowOpponent" class="space-y-0">
                <div class="grid gap-2 cols-2">
                    <div v-for="p in bottomActors" :key="p.id" class="space-y-1 border rounded px-3 py-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium truncate">{{ p.name }}</span>
                        </div>
                        <div class="text-xs text-muted-foreground">
                            <template v-if="stats.get(p.id)">
                                {{ stats.get(p.id)!.hitRate }}% hit, {{ stats.get(p.id)!.edgeRate }}% edge, {{ stats.get(p.id)!.missRate }}% miss
                            </template>
                            <template v-else>
                                0% hit, 0% edge, 0% miss
                            </template>
                        </div>
                        <!-- Full width row for action buttons -->
                        <div class="grid grid-cols-3 gap-1 mt-2">
                            <Button size="sm" variant="outline" :disabled="!canEdit || !canHitTop" @click="createUpdate('HIT','bottom', p.id)" class="border-green-500 text-green-700 hover:bg-green-50 disabled:border-gray-300 disabled:text-gray-400">Hit</Button>
                            <Button size="sm" variant="outline" :disabled="!canEdit || !canHitTop" @click="createUpdate('EDGE','bottom', p.id)" class="border-yellow-500 text-yellow-700 hover:bg-yellow-50 disabled:border-gray-300 disabled:text-gray-400">Edge</Button>
                            <Button size="sm" variant="outline" :disabled="!canEdit" @click="createUpdate('MISS','bottom', p.id)" class="border-red-500 text-red-700 hover:bg-red-50 disabled:border-gray-300 disabled:text-gray-400">Miss</Button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Rerack modal cup positioning styles - matches CupRack component */
.rack {
    display: grid;
    gap: 0.5rem;
}

.board {
    position: relative;
    width: 100%;
    max-width: 320px;
    aspect-ratio: 100 / 70;
    margin-inline: auto;
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
}

.cup {
    position: absolute;
    transform: translate(-50%, -50%);
    width: 16%; /* Increased from 11% to make cups bigger */
    aspect-ratio: 1;
    border-radius: 9999px;
    display: grid;
    place-items: center;
    border: 2px solid transparent;
    background: transparent;
    cursor: pointer;
    padding: 0;
    transition: all 120ms ease;
}

.cup--empty {
    background: #f8fafc;
    border-color: #e2e8f0;
}

.cup--empty:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.25);
}

.cup--placed {
    background: #3b82f6;
    border-color: #2563eb;
}

.cup--placed:hover {
    background: #2563eb;
    box-shadow: 0 0 0 3px rgba(239,68,68,0.25);
}

.cup--clickable {
    cursor: pointer;
}

.dot {
    width: 32%;
    aspect-ratio: 1;
    border-radius: 9999px;
    opacity: 0.9;
}

.cup--empty .dot {
    background: #cbd5e1;
}

.cup--placed .dot {
    background: white;
}

:where(html.dark) .cup--empty {
    background: #0f172a;
    border-color: #334155;
}

:where(html.dark) .cup--empty:hover {
    background: #1e293b;
    border-color: #475569;
}

:where(html.dark) .cup--empty .dot {
    background: #475569;
}
</style>
