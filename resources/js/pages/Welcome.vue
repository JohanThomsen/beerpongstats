<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Beerpong Stats" />

    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col items-center justify-center">
        <!-- Navigation -->
        <nav class="absolute top-0 right-0 p-6">
            <div class="flex items-center gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('user.match-history', { user: $page.props.auth.user.id })"
                    class="inline-block rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
                >
                    Match History
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="inline-block rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800 transition-colors"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="route('register')"
                        class="inline-block rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
                    >
                        Register
                    </Link>
                </template>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="text-center">
            <h1 class="text-6xl font-bold text-gray-900 dark:text-white mb-4">
                Beerpong Stats
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-300 mb-8">
                Track your beer pong games and statistics
            </p>

            <div class="flex gap-4 justify-center">
                <Link
                    v-if="!$page.props.auth.user"
                    :href="route('register')"
                    class="inline-block rounded-md bg-blue-600 px-6 py-3 text-lg font-medium text-white hover:bg-blue-700 transition-colors"
                >
                    Get Started
                </Link>
                <Link
                    v-else
                    :href="route('user.match-history', { user: $page.props.auth.user.id })"
                    class="inline-block rounded-md bg-blue-600 px-6 py-3 text-lg font-medium text-white hover:bg-blue-700 transition-colors"
                >
                    Go to Match History
                </Link>
            </div>
        </div>
    </div>
</template>
