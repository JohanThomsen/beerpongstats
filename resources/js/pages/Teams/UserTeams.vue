<template>
    <Head :title="`${user.name}'s Teams`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="text-center space-y-4 px-4 mt-8">
            <Heading :title="`${user.name}'s teams`" />
        </div>
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="space-y-6">

                <div v-if="teams.length === 0" class="py-12 text-center">
                    <p class="text-gray-500">{{ user.name }} is not a member of any teams yet.</p>
                    <Link
                        v-if="authUser && authUser.id === user.id"
                        :href="route('teams.create')"
                        class="mt-4 inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-blue-900"
                    >
                        Create First Team
                    </Link>
                </div>

                <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <TeamCard
                        v-for="team in teams"
                        :key="team.id"
                        :team="team"
                    />
                </div>

                <div v-if="teams.length > 0 && authUser && authUser.id === user.id" class="mt-8 text-center">
                    <Link
                        :href="route('teams.create')"
                        class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-blue-900"
                    >
                        Create New Team
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { type BreadcrumbItem } from '@/types';
import TeamCard from '@/components/TeamCard.vue';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Team {
    id: number;
    name: string;
    created_at: string;
    users: User[];
}

const props = defineProps<{
    user: User;
    teams: Team[];
}>();

const page = usePage();
const authUser = page.props.auth.user;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teams',
        href: '/teams',
    },
    {
        title: `${props.user.name}'s Teams`,
        href: `/user/${props.user.id}/teams`,
    },
];
</script>
