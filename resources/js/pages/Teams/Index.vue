<template>
    <Head title="Teams" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="text-center space-y-4 px-4 mt-8">
            <Heading title="Teams" />
        </div>
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="space-y-6">
                <div v-if="teams.length === 0" class="text-center py-12">
                    <p class="text-gray-500">No teams found.</p>
                </div>

                <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <TeamCard
                        v-for="team in teams"
                        :key="team.id"
                        :team="team"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import TeamCard from '@/components/TeamCard.vue'
import { type BreadcrumbItem } from '@/types'

interface User {
    id: number
    name: string
}

interface Team {
    id: number
    name: string
    users: User[]
}

defineProps<{
    teams: Team[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teams',
        href: '/teams',
    },
]
</script>
