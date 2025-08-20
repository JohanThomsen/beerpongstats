<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card'
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Link } from '@inertiajs/vue3'

const props = defineProps<{
    gameId: number
    participantName?: string
    score: string
    hitPercentage?: string
    shotsTaken: string
    sinks: string
    secondaryParticipantName?: string
    result?: 'WIN' | 'LOSS'
}>()

const cardClasses = computed(() => {
    if (!props.result) return ''
    return {
        'WIN': 'bg-gradient-to-r from-green-500/20 to-transparent',
        'LOSS': 'bg-gradient-to-r from-red-500/20 to-transparent'
    }[props.result]
})

// Check if the opponent should be hidden (placeholder users/teams)
const shouldShowOpponent = computed(() => {
    if (!props.secondaryParticipantName) return false

    // Hide if opponent is placeholder user
    if (props.secondaryParticipantName === 'placeholder1' ||
        props.secondaryParticipantName === 'placeholder2') {
        return false
    }

    // Hide if opponent is placeholder team
    if (props.secondaryParticipantName === 'placeholder team') {
        return false
    }

    return true
})

// For practice games, show only player's score (cups left)
const displayScore = computed(() => {
    if (shouldShowOpponent.value) {
        // Normal game: show full score (e.g., "6-4")
        return props.score
    } else {
        // Practice game: extract and show only player's score
        const scoreParts = props.score.split('-')
        if (scoreParts.length === 2) {
            // Return only the first part (player's cups left)
            if (scoreParts[1].trim() === '0') {
                return 'Win'
            }
            return scoreParts[1] + ' Cups left'
        }
        // Fallback to full score if format is unexpected
        return props.score
    }
})
</script>

<template>
    <Card :class="cardClasses">
        <CardContent class="p-3">
            <div class="grid grid-cols-3 items-start gap-2">
                <div class="col-span-2">
                    <p v-if="participantName" class="text-base font-semibold text-gray-300">
                        {{ participantName }}
                    </p>
                    <p v-if="shouldShowOpponent" class="text-base font-semibold text-gray-300">
                        vs {{ secondaryParticipantName }}
                    </p>
                    <div class="flex flex-wrap gap-x-3 gap-y-1 text-sm text-gray-400">
                        <span>Hit: {{ hitPercentage }}</span>
                        <span>Shots: {{ shotsTaken }}</span>
                        <span>Hits: {{ sinks }}</span>
                    </div>
                </div>
                <div class="col-span-1 text-right space-y-2">
                    <p class="text-3xl font-bold">{{ displayScore }}</p>
                    <Button asChild size="sm" variant="outline">
                        <Link :href="route('games.live', { game: gameId })">Live</Link>
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
