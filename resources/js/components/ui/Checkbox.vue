<script setup>
import { computed } from 'vue';
import { cn } from '../../lib/utils';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    invalid: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const classes = computed(() => cn(
    'h-4 w-4 shrink-0 rounded border border-input accent-primary',
    'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
    'disabled:cursor-not-allowed disabled:opacity-50',
    props.invalid && 'border-destructive',
));

/**
 * @param {Event} event
 */
function onChange(event) {
    emit('update:modelValue', event.target.checked);
}
</script>

<template>
    <input
        v-bind="$attrs"
        type="checkbox"
        :checked="modelValue"
        :class="classes"
        @change="onChange"
    />
</template>
