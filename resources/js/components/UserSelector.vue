<template>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            Players
        </label>

        <!-- Current user (always selected) -->
        <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-md">
            <div class="flex items-center">
                <div class="w-2 h-2 bg-blue-500 rounded-full mr-3"></div>
                <span class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ currentUser.name }} (You)</span>
            </div>
        </div>

        <!-- Searchable dropdown for opponent -->
        <div class="relative">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Select Opponent
            </label>
            <div class="relative">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search for an opponent..."
                    class="w-full pl-3 pr-10 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-400 dark:focus:border-indigo-400"
                    @focus="showDropdown = true"
                    @blur="handleBlur"
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            <!-- Dropdown -->
            <div
                v-show="showDropdown && filteredUsers.length > 0"
                class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-y-auto"
            >
                <div
                    v-for="user in filteredUsers"
                    :key="user.id"
                    class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer text-gray-900 dark:text-white"
                    :class="{ 'bg-blue-100 dark:bg-blue-900/30': selectedOpponent?.id === user.id }"
                    @mousedown="selectUser(user)"
                >
                    {{ user.name }}
                </div>
            </div>
        </div>

        <!-- Selected opponent display -->
        <div v-if="selectedOpponent" class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-md">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                    <span class="text-sm font-medium text-green-800 dark:text-green-200">{{ selectedOpponent.name }}</span>
                </div>
                <button
                    type="button"
                    @click="removeOpponent"
                    class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Selected: {{ modelValue.length }}/2 players
        </p>
        <InputError :message="error" class="mt-2" />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import InputError from '@/components/InputError.vue'

interface User {
    id: number
    name: string
}

const props = defineProps<{
    users: User[]
    currentUser: User
    modelValue: number[]
    error?: string
}>()

const emit = defineEmits<{
    'update:modelValue': [value: number[]]
}>()

const searchQuery = ref('')
const showDropdown = ref(false)
const selectedOpponent = ref<User | null>(null)

// Filter users excluding current user and based on search query
const filteredUsers = computed(() => {
    return props.users
        .filter(user => user.id !== props.currentUser.id)
        .filter(user =>
            user.name.toLowerCase().includes(searchQuery.value.toLowerCase())
        )
})

// Watch for changes in selectedOpponent to update modelValue
watch(selectedOpponent, (newOpponent) => {
    if (newOpponent) {
        emit('update:modelValue', [props.currentUser.id, newOpponent.id])
    } else {
        emit('update:modelValue', [props.currentUser.id])
    }
})

// Watch for external changes to modelValue to sync selectedOpponent
watch(() => props.modelValue, (newValue) => {
    if (newValue.length === 1) {
        // Only current user selected
        selectedOpponent.value = null
        searchQuery.value = ''
    } else if (newValue.length === 2) {
        // Find the opponent (not current user)
        const opponentId = newValue.find(id => id !== props.currentUser.id)
        if (opponentId) {
            const opponent = props.users.find(user => user.id === opponentId)
            if (opponent) {
                selectedOpponent.value = opponent
                searchQuery.value = opponent.name
            }
        }
    }
}, { immediate: true })

function selectUser(user: User) {
    selectedOpponent.value = user
    searchQuery.value = user.name
    showDropdown.value = false
}

function removeOpponent() {
    selectedOpponent.value = null
    searchQuery.value = ''
}

function handleBlur() {
    // Delay hiding dropdown to allow for clicks
    setTimeout(() => {
        showDropdown.value = false
    }, 200)
}
</script>
