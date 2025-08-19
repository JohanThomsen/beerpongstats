<template>
    <Head title="Create Game" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto bg-gradient-to-br from-gray-50 to-white dark:from-black dark:to-black">
            <div class="max-w-2xl mx-auto">
                <div class="mb-6">
                    <Heading title="Create New Game" class="text-gray-900 dark:text-white text-center" />
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Start a new beer pong game. Choose between solo or team mode, then select players and game type.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border border-gray-200 dark:border-gray-700">
                    <!-- Game Mode Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            Game Mode
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <button
                                type="button"
                                @click="selectGameMode(true)"
                                :class="[
                                    'p-4 border-2 rounded-lg text-center transition-all duration-200',
                                    form.is_solo
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300'
                                        : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 text-gray-600 dark:text-gray-400'
                                ]"
                            >
                                <div class="font-semibold">Solo Game</div>
                                <div class="text-sm mt-1">1v1 Match</div>
                            </button>
                            <button
                                type="button"
                                @click="selectGameMode(false)"
                                :class="[
                                    'p-4 border-2 rounded-lg text-center transition-all duration-200',
                                    !form.is_solo
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300'
                                        : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 text-gray-600 dark:text-gray-400'
                                ]"
                            >
                                <div class="font-semibold">Team Game</div>
                                <div class="text-sm mt-1">2v2 Match</div>
                            </button>
                        </div>
                        <InputError :message="form.errors.is_solo" class="mt-2" />
                    </div>

                    <!-- Player Selection for Solo Games -->
                    <div v-if="form.is_solo">
                        <UserSelector
                            v-model="form.user_ids"
                            :users="users"
                            :current-user="currentUser"
                            :error="form.errors.user_ids"
                        />
                    </div>

                    <!-- Team Selection for Team Games -->
                    <div v-else>
                        <TeamSelector
                            v-model="form.team_ids"
                            :user-teams="userTeams"
                            :opponent-teams="opponentTeams"
                            :error="form.errors.team_ids"
                        />
                    </div>

                    <!-- Game Type Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            Game Type
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <button
                                type="button"
                                @click="form.type = 'SIX_CUP'"
                                :class="[
                                    'p-4 border-2 rounded-lg text-center transition-all duration-200',
                                    form.type === 'SIX_CUP'
                                        ? 'border-green-500 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300'
                                        : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 text-gray-600 dark:text-gray-400'
                                ]"
                            >
                                <div class="font-semibold">6 Cup</div>
                                <div class="text-sm mt-1">Quick Game</div>
                            </button>
                            <button
                                type="button"
                                @click="form.type = 'TEN_CUP'"
                                :class="[
                                    'p-4 border-2 rounded-lg text-center transition-all duration-200',
                                    form.type === 'TEN_CUP'
                                        ? 'border-green-500 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300'
                                        : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 text-gray-600 dark:text-gray-400'
                                ]"
                            >
                                <div class="font-semibold">10 Cup</div>
                                <div class="text-sm mt-1">Standard Game</div>
                            </button>
                        </div>
                        <InputError :message="form.errors.type" class="mt-2" />
                    </div>

                    <!-- No teams message -->
                    <div v-if="!form.is_solo && userTeams.length === 0" class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-md">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            You need to be a member of at least one team to create team games.
                            <Link :href="route('teams.create')" class="underline hover:no-underline">Create a team first</Link>.
                        </p>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <Link
                            :href="route('dashboard')"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing || !canSubmit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 dark:bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 dark:hover:bg-blue-600 focus:bg-blue-700 dark:focus:bg-blue-600 active:bg-blue-900 dark:active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
                        >
                            Create Game
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import UserSelector from '@/components/UserSelector.vue'
import TeamSelector from '@/components/TeamSelector.vue'
import { type BreadcrumbItem } from '@/types'
import { computed } from 'vue'

interface User {
    id: number
    name: string
}

interface Team {
    id: number
    name: string
    users: User[]
}

const props = defineProps<{
    users: User[]
    currentUser: User
    userTeams: Team[]
    opponentTeams: Team[]
}>()

const form = useForm({
    is_solo: true,
    type: 'TEN_CUP' as 'SIX_CUP' | 'TEN_CUP',
    user_ids: [props.currentUser.id] as number[],
    team_ids: [] as number[]
})

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Create Game',
        href: '/games/create',
    },
]

const canSubmit = computed(() => {
    if (form.is_solo) {
        return form.user_ids.length === 2 && form.type
    } else {
        return form.team_ids.length === 2 && form.type
    }
})

function selectGameMode(isSolo: boolean) {
    form.is_solo = isSolo
    // Reset selections when switching modes
    if (isSolo) {
        form.user_ids = [props.currentUser.id]
        form.team_ids = []
    } else {
        form.user_ids = []
        form.team_ids = []
    }
}

function submit() {
    form.post(route('games.store'))
}
</script>
