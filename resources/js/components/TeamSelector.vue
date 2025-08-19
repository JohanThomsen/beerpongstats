<template>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            Teams
        </label>

        <!-- User's team selection -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Select Your Team
            </label>
            <div class="relative">
                <input
                    v-model="userTeamSearchQuery"
                    type="text"
                    placeholder="Search your teams..."
                    class="w-full pl-3 pr-10 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-400 dark:focus:border-indigo-400"
                    @focus="showUserTeamDropdown = true"
                    @blur="handleUserTeamBlur"
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- User teams dropdown -->
                <div
                    v-show="showUserTeamDropdown && filteredUserTeams.length > 0"
                    class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-y-auto"
                >
                    <div
                        v-for="team in filteredUserTeams"
                        :key="team.id"
                        class="px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer text-gray-900 dark:text-white"
                        :class="{ 'bg-blue-100 dark:bg-blue-900/30': selectedUserTeam?.id === team.id }"
                        @mousedown="selectUserTeam(team)"
                    >
                        <div class="font-medium">{{ team.name }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ team.users.map(u => u.name).join(', ') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selected user team display -->
        <div v-if="selectedUserTeam" class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-md">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mr-3"></div>
                    <div>
                        <div class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ selectedUserTeam.name }}</div>
                        <div class="text-xs text-blue-600 dark:text-blue-300">{{ selectedUserTeam.users.map(u => u.name).join(', ') }}</div>
                    </div>
                </div>
                <button
                    type="button"
                    @click="removeUserTeam"
                    class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Opponent team selection -->
        <div class="relative">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Select Opponent Team
            </label>
            <div class="relative">
                <input
                    v-model="opponentTeamSearchQuery"
                    type="text"
                    placeholder="Search opponent teams..."
                    class="w-full pl-3 pr-10 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-400 dark:focus:border-indigo-400"
                    @focus="showOpponentTeamDropdown = true"
                    @blur="handleOpponentTeamBlur"
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            <!-- Opponent teams dropdown -->
            <div
                v-show="showOpponentTeamDropdown && filteredOpponentTeams.length > 0"
                class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-y-auto"
            >
                <div
                    v-for="team in filteredOpponentTeams"
                    :key="team.id"
                    class="px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer text-gray-900 dark:text-white"
                    :class="{ 'bg-blue-100 dark:bg-blue-900/30': selectedOpponentTeam?.id === team.id }"
                    @mousedown="selectOpponentTeam(team)"
                >
                    <div class="font-medium">{{ team.name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ team.users.map(u => u.name).join(', ') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Selected opponent team display -->
        <div v-if="selectedOpponentTeam" class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-md">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                    <div>
                        <div class="text-sm font-medium text-green-800 dark:text-green-200">{{ selectedOpponentTeam.name }}</div>
                        <div class="text-xs text-green-600 dark:text-green-300">{{ selectedOpponentTeam.users.map(u => u.name).join(', ') }}</div>
                    </div>
                </div>
                <button
                    type="button"
                    @click="removeOpponentTeam"
                    class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Selected: {{ modelValue.length }}/2 teams
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

interface Team {
    id: number
    name: string
    users: User[]
}

const props = defineProps<{
    userTeams: Team[]
    opponentTeams: Team[]
    modelValue: number[]
    error?: string
}>()

const emit = defineEmits<{
    'update:modelValue': [value: number[]]
}>()

const userTeamSearchQuery = ref('')
const opponentTeamSearchQuery = ref('')
const showUserTeamDropdown = ref(false)
const showOpponentTeamDropdown = ref(false)
const selectedUserTeam = ref<Team | null>(null)
const selectedOpponentTeam = ref<Team | null>(null)

// Filter user teams based on search query
const filteredUserTeams = computed(() => {
    return props.userTeams.filter(team =>
        team.name.toLowerCase().includes(userTeamSearchQuery.value.toLowerCase()) ||
        team.users.some(user => user.name.toLowerCase().includes(userTeamSearchQuery.value.toLowerCase()))
    )
})

// Filter opponent teams based on search query
const filteredOpponentTeams = computed(() => {
    return props.opponentTeams.filter(team =>
        team.name.toLowerCase().includes(opponentTeamSearchQuery.value.toLowerCase()) ||
        team.users.some(user => user.name.toLowerCase().includes(opponentTeamSearchQuery.value.toLowerCase()))
    )
})

// Watch for changes in selected teams to update modelValue
watch([selectedUserTeam, selectedOpponentTeam], ([userTeam, opponentTeam]) => {
    const teamIds = []
    if (userTeam) teamIds.push(userTeam.id)
    if (opponentTeam) teamIds.push(opponentTeam.id)
    emit('update:modelValue', teamIds)
})

// Watch for external changes to modelValue to sync selected teams
watch(() => props.modelValue, (newValue) => {
    if (newValue.length === 0) {
        selectedUserTeam.value = null
        selectedOpponentTeam.value = null
        userTeamSearchQuery.value = ''
        opponentTeamSearchQuery.value = ''
    } else if (newValue.length === 1) {
        // Try to find which team is selected
        const teamId = newValue[0]
        const userTeam = props.userTeams.find(team => team.id === teamId)
        const opponentTeam = props.opponentTeams.find(team => team.id === teamId)

        if (userTeam) {
            selectedUserTeam.value = userTeam
            userTeamSearchQuery.value = userTeam.name
        } else if (opponentTeam) {
            selectedOpponentTeam.value = opponentTeam
            opponentTeamSearchQuery.value = opponentTeam.name
        }
    } else if (newValue.length === 2) {
        // Find both teams
        newValue.forEach(teamId => {
            const userTeam = props.userTeams.find(team => team.id === teamId)
            const opponentTeam = props.opponentTeams.find(team => team.id === teamId)

            if (userTeam) {
                selectedUserTeam.value = userTeam
                userTeamSearchQuery.value = userTeam.name
            } else if (opponentTeam) {
                selectedOpponentTeam.value = opponentTeam
                opponentTeamSearchQuery.value = opponentTeam.name
            }
        })
    }
}, { immediate: true })

function selectUserTeam(team: Team) {
    selectedUserTeam.value = team
    userTeamSearchQuery.value = team.name
    showUserTeamDropdown.value = false
}

function selectOpponentTeam(team: Team) {
    selectedOpponentTeam.value = team
    opponentTeamSearchQuery.value = team.name
    showOpponentTeamDropdown.value = false
}

function removeUserTeam() {
    selectedUserTeam.value = null
    userTeamSearchQuery.value = ''
}

function removeOpponentTeam() {
    selectedOpponentTeam.value = null
    opponentTeamSearchQuery.value = ''
}

function handleUserTeamBlur() {
    setTimeout(() => {
        showUserTeamDropdown.value = false
    }, 200)
}

function handleOpponentTeamBlur() {
    setTimeout(() => {
        showOpponentTeamDropdown.value = false
    }, 200)
}
</script>
