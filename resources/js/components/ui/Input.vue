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
});

const emit = defineEmits(['update:modelValue']);

const classes = computed(() => cn(
    'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors',
    'placeholder:text-muted-foreground',
    'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
    'disabled:cursor-not-allowed disabled:opacity-50',
    props.invalid && 'border-destructive focus-visible:ring-destructive',
));

/**
 * The native input must forward its value back to the parent: `v-model`
 * compiles to `modelValue` + `update:modelValue`, neither of which a plain
 * `<input>` emits on its own.
 *
 * @param {Event} event
 */
function onInput(event) {
    emit('update:modelValue', event.target.value);
}
</script>

<template>
    <input
        v-bind="$attrs"
        :value="modelValue"
        :class="classes"
        @input="onInput"
    />
</template>
