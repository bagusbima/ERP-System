<script setup>
import { computed } from 'vue';
import { cn } from '../../lib/utils';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    invalid: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const classes = computed(() => cn(
    'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors',
    'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
    'disabled:cursor-not-allowed disabled:opacity-50',
    props.invalid && 'border-destructive focus-visible:ring-destructive',
));

/**
 * A native select always reports its value as a string, so callers that need
 * an id convert it back when they build the payload.
 *
 * @param {Event} event
 */
function onChange(event) {
    emit('update:modelValue', event.target.value);
}
</script>

<template>
    <select
        v-bind="$attrs"
        :value="modelValue"
        :class="classes"
        @change="onChange"
    >
        <slot />
    </select>
</template>
