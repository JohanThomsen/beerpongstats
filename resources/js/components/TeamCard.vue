<template>
    <div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 p-3 border border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-start mb-2">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ team.name }}</h3>
        </div>

        <div class="space-y-1">
            <div class="flex justify-between items-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">Members:</p>
                <Link
                    :href="route('teams.show', team.id)"
                    as="button"
                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md transition-colors duration-150 border border-blue-200 dark:border-blue-700"
                >
                    View Details
                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </Link>
            </div>
            <div class="flex flex-wrap gap-1">
                <span
                    v-for="user in team.users"
                    :key="user.id"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-700"
                >
                    {{ user.name }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
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
    team: Team
}>()

defineEmits<{
    delete: [team: Team]
}>()
</script>
