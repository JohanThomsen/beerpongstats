<template>
    <Head :title="team.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto bg-gradient-to-br from-gray-50 to-white dark:from-black dark:to-black">
            <div class="w-full max-w-2xl mx-auto">
                <div class="mb-6">
                    <Heading :title="team.name" class="text-gray-900 dark:text-white text-center" />
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Team Information</h3>
                            <div v-if="canEditTeam" class="flex space-x-2">
                                <Link
                                    :href="route('teams.edit', team.id)"
                                    as="button"
                                    class="px-3 py-1.5 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md transition-colors duration-150"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="deleteTeam"
                                    class="px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors duration-150"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Team Name</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ team.name }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ new Date(team.created_at).toLocaleDateString() }}
                                </dd>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Team Members</h4>
                        <div class="space-y-3">
                            <div
                                v-for="user in team.users"
                                :key="user.id"
                                class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600"
                            >
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-blue-500 dark:bg-blue-600 flex items-center justify-center">
                                            <span class="text-white font-medium">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ user.name }}</p>
                                    </div>
                                </div>

                                <Link
                                    :href="route('user.teams', user.id)"
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium"
                                >
                                    View User's Teams
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <Link
                    :href="route('teams.index')"
                    class="inline-flex items-center mt-2 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm"
                >
                    ← Back to Teams
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import { type BreadcrumbItem } from '@/types'
import { computed } from 'vue'

interface User {
    id: number
    name: string
    email: string
}

interface Team {
    id: number
    name: string
    created_at: string
    users: User[]
}

const props = defineProps<{
    team: Team
}>()

const page = usePage()
const authUser = page.props.auth.user

const canEditTeam = computed(() => {
    if (!authUser) return false
    return props.team.users.some(user => user.id === authUser.id)
})

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teams',
        href: '/teams',
    },
    {
        title: props.team.name,
        href: `/teams/${props.team.id}`,
    },
]

function deleteTeam() {
    if (confirm(`Are you sure you want to delete the team "${props.team.name}"?`)) {
        router.delete(route('teams.destroy', props.team.id), {
            onSuccess: () => router.visit(route('teams.index'))
        })
    }
}
</script>
