<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { X } from 'lucide-vue-next';
import { cn } from '../../lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    /**
     * Decorative header icon, e.g. `<ArrowLeftRight class="h-4 w-4" />`.
     */
    icon: {
        type: [Object, Function],
        default: null,
    },
    wide: {
        type: Boolean,
        default: false,
    },
    /**
     * `center` renders a modal box, `right` renders a full-height slide-over
     * panel (used for long master-data forms with their own tab strip).
     */
    side: {
        type: String,
        default: 'center',
        validator: (value) => ['center', 'right'].includes(value),
    },
    closeLabel: {
        type: String,
        default: 'Tutup dialog',
    },
});

const emit = defineEmits(['update:modelValue']);

const panel = ref(null);

const isSlideOver = computed(() => props.side === 'right');

watch(() => props.modelValue, async (open) => {
    document.body.style.overflow = open ? 'hidden' : '';

    if (! open) {
        return;
    }

    await nextTick();
    focusFirstField();
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', onKeydown);
});

/**
 * Move focus into the dialog so keyboard users are not left behind it.
 */
function focusFirstField() {
    const field = panel.value?.querySelector(
        'input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])',
    );

    field?.focus();
}

function close() {
    emit('update:modelValue', false);
}

/**
 * @param {KeyboardEvent} event
 */
function onKeydown(event) {
    if (props.modelValue && event.key === 'Escape') {
        close();
    }
}

window.addEventListener('keydown', onKeydown);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="modelValue"
            class="fixed inset-0 z-50 flex p-4"
            :class="isSlideOver ? 'justify-end p-0' : 'items-center justify-center'"
        >
            <div
                class="absolute inset-0 bg-foreground/40 backdrop-blur-[2px]"
                aria-hidden="true"
                @click="close"
            />

            <div
                ref="panel"
                role="dialog"
                aria-modal="true"
                :aria-label="title"
                class="relative flex min-h-0 w-full flex-col overflow-hidden bg-card shadow-xl"
                :class="isSlideOver
                    ? 'h-full max-w-2xl border-l'
                    : cn('max-h-[90vh] rounded-lg border', wide ? 'max-w-2xl' : 'max-w-lg')"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-3 border-b border-border/60 bg-muted/60 px-5 py-4"
                >
                    <div class="flex items-center gap-3">
                        <span
                            v-if="icon"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground"
                        >
                            <component :is="icon" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0">
                            <p
                                v-if="isSlideOver && $slots.pretitle"
                                class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground"
                            >
                                <slot name="pretitle" />
                            </p>
                            <h2 class="text-sm font-semibold text-foreground">{{ title }}</h2>
                            <p v-if="description" class="text-xs text-muted-foreground">
                                {{ description }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="rounded-md p-1 text-muted-foreground transition-colors hover:text-foreground"
                        :aria-label="closeLabel"
                        @click="close"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div v-if="$slots.tabs" class="shrink-0 border-b border-border/60 px-5">
                    <slot name="tabs" />
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-5">
                    <slot />
                </div>

                <div v-if="$slots.footer" class="shrink-0 border-t border-border/60 bg-muted/40 px-5 py-4">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </Teleport>
</template>
