<template>
    <Head title="Edit Team" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto bg-gradient-to-br from-gray-50 to-white dark:from-black dark:to-black">
            <div class="w-full max-w-2xl mx-auto">
                <div class="mb-6">
                    <Heading title="Edit Team" class="text-gray-900 dark:text-white text-center" />
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Update team information and members.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border border-gray-200 dark:border-gray-700">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Team Name
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400 pl-3 pr-3 py-2"
                            :class="{ 'border-red-500 dark:border-red-400': form.errors.name }"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <TeamMemberSelector
                        v-model="form.user_ids"
                        :users="users"
                        :current-user="currentUser"
                        :error="form.errors.user_ids"
                        :initial-second-member="initialSecondMember"
                    />

                    <div class="flex justify-end space-x-3">
                        <Link
                            :href="route('teams.index')"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing || form.user_ids.length !== 2"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 dark:bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 dark:hover:bg-blue-600 focus:bg-blue-700 dark:focus:bg-blue-600 active:bg-blue-900 dark:active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
                        >
                            Update Team
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import TeamMemberSelector from '@/components/TeamMemberSelector.vue'
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
    team: Team
    users: User[]
    currentUser: User
}>()

const form = useForm({
    name: props.team.name,
    user_ids: props.team.users.map(user => user.id)
})

const page = usePage()
const authUser = page.props.auth.user

// Find the second member (not the current user)
const initialSecondMember = computed(() => {
    return props.team.users.find(user => user.id !== authUser.id)
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
    {
        title: 'Edit',
        href: `/teams/${props.team.id}/edit`,
    },
]

function submit() {
    form.put(route('teams.update', props.team.id))
}
</script>
