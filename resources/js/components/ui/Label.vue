<script setup>
import { cn } from '../../lib/utils';

defineProps({
    required: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <label
        v-bind="$attrs"
        :class="cn('text-sm font-medium leading-none', 'peer-disabled:cursor-not-allowed peer-disabled:opacity-70')"
    >
        <slot />
        <span v-if="required" class="ml-0.5 text-destructive">*</span>
    </label>
</template>
