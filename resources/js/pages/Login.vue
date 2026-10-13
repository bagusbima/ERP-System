<script setup>
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Button from '../components/ui/Button.vue';
import Card from '../components/ui/Card.vue';
import Input from '../components/ui/Input.vue';
import Label from '../components/ui/Label.vue';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const form = ref({
    email: '',
    password: '',
});

const error = ref('');
const fieldErrors = ref({
    email: '',
    password: '',
});

const submitting = computed(() => auth.loading);

async function submit() {
    error.value = '';
    fieldErrors.value = { email: '', password: '' };

    try {
        await auth.login({
            email: form.value.email,
            password: form.value.password,
        });

        const redirect = route.query.redirect;
        const fallback = typeof redirect === 'string' && redirect ? redirect : '/';

        // No active company yet: send the user straight to the picker (or the
        // create form) instead of letting the router bounce them around.
        await router.replace(auth.company ? fallback : { name: 'companies' });
    } catch (exception) {
        handleFailure(exception);
    }
}

/**
 * Map the API failure onto the banner and the individual fields.
 *
 * A 422 carries `errors` keyed by field; anything else only carries `message`.
 */
function handleFailure(exception) {
    const status = exception.response?.status;
    const payload = exception.response?.data;

    if (status === 429) {
        error.value = 'Terlalu banyak percobaan masuk. Silakan coba lagi dalam ±1 menit.';

        return;
    }

    const errors = payload?.errors;

    if (status === 422 && errors) {
        fieldErrors.value = {
            email: firstMessage(errors.email),
            password: firstMessage(errors.password),
        };

        const unmapped = Object.keys(errors).filter((key) => ! (key in fieldErrors.value));

        if (unmapped.length > 0) {
            error.value = payload.message ?? 'Data yang diberikan tidak valid.';
        }

        if (! fieldErrors.value.email && ! fieldErrors.value.password && ! error.value) {
            error.value = payload.message ?? 'Data yang diberikan tidak valid.';
        }

        return;
    }

    error.value = payload?.message ?? 'Tidak dapat terhubung ke server.';
}

/**
 * Take the first message of a validation error list.
 *
 * @param {string[]|undefined} messages
 * @returns {string}
 */
function firstMessage(messages) {
    return Array.isArray(messages) ? (messages[0] ?? '') : '';
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-muted/40 p-4">
        <Card class="w-full max-w-sm p-6">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                    E
                </div>
                <h1 class="text-xl font-semibold">Masuk ke ERP</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Gunakan akun perusahaan Anda
                </p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="email" required>Email</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        name="email"
                        autocomplete="username"
                        placeholder="nama@perusahaan.co.id"
                        :invalid="Boolean(fieldErrors.email)"
                        aria-describedby="email-error"
                        required
                        @input="fieldErrors.email = ''"
                    />
                    <p
                        v-if="fieldErrors.email"
                        id="email-error"
                        class="text-sm text-destructive"
                        role="alert"
                    >
                        {{ fieldErrors.email }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="password" required>Password</Label>
                    <Input
                        id="password"
                        v-model="form.password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        :invalid="Boolean(fieldErrors.password)"
                        aria-describedby="password-error"
                        required
                        @input="fieldErrors.password = ''"
                    />
                    <p
                        v-if="fieldErrors.password"
                        id="password-error"
                        class="text-sm text-destructive"
                        role="alert"
                    >
                        {{ fieldErrors.password }}
                    </p>
                </div>

                <p
                    v-if="error"
                    class="rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive"
                    role="alert"
                >
                    {{ error }}
                </p>

                <Button type="submit" class="w-full" :disabled="submitting">
                    {{ submitting ? 'Memproses…' : 'Masuk' }}
                </Button>
            </form>
        </Card>
    </div>
</template>
