<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import MatchHistoryItem from '@/components/MatchHistoryItem.vue';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';
import { User } from '@/types';
import type { GameDataObject } from '@/types/generated';
import { router } from '@inertiajs/vue3';

interface PaginationData {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from: number | null;
    to: number | null;
    hasMorePages: boolean;
    prevPageUrl: string | null;
    nextPageUrl: string | null;
}

const props = defineProps<{
    user: User,
    games: GameDataObject[],
    pagination: PaginationData
}>();

const navigateToPage = (page: number) => {
    router.get(route('user.match-history', { user: props.user.id }), {
        page,
        perPage: props.pagination.perPage
    }, {
        preserveState: true
    });
};

const goToPreviousPage = () => {
    if (props.pagination.currentPage > 1) {
        navigateToPage(props.pagination.currentPage - 1);
    }
};

const goToNextPage = () => {
    if (props.pagination.hasMorePages) {
        navigateToPage(props.pagination.currentPage + 1);
    }
};
</script>

<template>
    <Head title="Match History" />

    <AppLayout>
        <div class="space-y-4 px-4 mt-8">
            <div class="text-center">
                <Heading :title="`${user.name}'s match History`" />
                <Link
                    :href="route('games.create')"
                    class="inline-flex items-center px-6 py-3 mt-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition-colors duration-200"
                >
                    New Game
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <div v-for="game in games" :key="game.id">
                    <div v-if="game.isSolo && game.primaryUser">
                        <MatchHistoryItem
                            :game-id="game.id"
                            :participant-name="user.name"
                            :secondary-participant-name="game.secondaryUser?.name"
                            :score="`${game.primaryUser.cupsLeft} - ${game.secondaryUser?.cupsLeft ?? '?'}`"
                            :hit-percentage="`${game.hitRate}%`"
                            :shots-taken="game.totalThrows"
                            :sinks="game.hits"
                            :result="game.primaryUser.result"
                        />
                    </div>
                    <div v-else-if="!game.isSolo && game.primaryTeam">
                        <MatchHistoryItem
                            :game-id="game.id"
                            :participant-name="game.primaryTeam.name"
                            :secondary-participant-name="game.secondaryTeam?.name"
                            :score="`${game.primaryTeam.cupsLeft} - ${game.secondaryTeam?.cupsLeft ?? '?'}`"
                            :hit-percentage="`${game.hitRate}%`"
                            :shots-taken="game.totalThrows"
                            :sinks="game.hits"
                            :result="game.primaryTeam.result"
                        />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-400">
                    Page {{ pagination.currentPage }} of {{ pagination.lastPage }}
                    <span v-if="pagination.from && pagination.to">
                        ({{ pagination.from }}-{{ pagination.to }} of {{ pagination.total }})
                    </span>
                </span>
                <div class="flex space-x-2">
                    <Button
                        variant="outline"
                        @click="goToPreviousPage"
                        :disabled="pagination.currentPage === 1"
                    >
                        Previous
                    </Button>
                    <Button
                        variant="outline"
                        @click="goToNextPage"
                        :disabled="!pagination.hasMorePages"
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
