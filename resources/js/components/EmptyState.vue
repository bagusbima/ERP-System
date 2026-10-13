<script setup>
import Button from './ui/Button.vue';

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    icon: {
        type: [Object, Function],
        default: null,
    },
    actionLabel: {
        type: String,
        default: '',
    },
});

defineEmits(['action']);
</script>

<template>
    <div class="px-6 py-16 text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-muted text-muted-foreground">
            <component :is="icon" v-if="icon" class="h-8 w-8" />
        </div>

        <h3 class="text-base font-medium text-foreground">{{ title }}</h3>
        <p class="mx-auto mt-1 mb-6 max-w-md text-sm text-muted-foreground">
            {{ description }}
        </p>

        <div v-if="actionLabel" class="flex items-center justify-center gap-2">
            <Button @click="$emit('action')">
                {{ actionLabel }}
            </Button>
        </div>
    </div>
</template>
