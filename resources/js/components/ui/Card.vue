<script setup>
import { cn } from '../../lib/utils';

defineProps({
    /**
     * Renders a softer, muted surface instead of a raised card.
     */
    muted: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div
        v-bind="$attrs"
        :class="cn(
            'rounded-lg border bg-card text-card-foreground shadow-sm',
            muted && 'bg-muted/40',
        )"
    >
        <slot />
    </div>
</template>
