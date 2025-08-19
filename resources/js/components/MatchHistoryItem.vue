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
</script>

<template>
    <Card :class="cardClasses">
        <CardContent class="p-3">
            <div class="grid grid-cols-3 items-start gap-2">
                <div class="col-span-2">
                    <p v-if="participantName" class="text-base font-semibold text-gray-300">
                        {{ participantName }}
                    </p>
                    <p v-if="secondaryParticipantName" class="text-base font-semibold text-gray-300">
                        vs {{ secondaryParticipantName }}
                    </p>
                    <div class="flex flex-wrap gap-x-3 gap-y-1 text-sm text-gray-400">
                        <span>Hit: {{ hitPercentage }}</span>
                        <span>Shots: {{ shotsTaken }}</span>
                        <span>Hits: {{ sinks }}</span>
                    </div>
                </div>
                <div class="col-span-1 text-right space-y-2">
                    <p class="text-3xl font-bold">{{ score }}</p>
                    <Button asChild size="sm" variant="outline">
                        <Link :href="route('games.live', { game: gameId })">Live</Link>
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
