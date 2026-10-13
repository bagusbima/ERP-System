<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import Button from '../components/ui/Button.vue';
import Card from '../components/ui/Card.vue';
import Checkbox from '../components/ui/Checkbox.vue';
import Input from '../components/ui/Input.vue';
import Label from '../components/ui/Label.vue';
import client from '../api/client';
import { fieldErrors as extractFieldErrors, readableError } from '../lib/errors';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();

const roles = ref([]);
const modules = ref([]);
const loading = ref(true);
const showForm = ref(false);
const editing = ref(null);
const error = ref('');
const errors = ref({});
const submitting = ref(false);

const form = reactive({
    name: '',
    permissions: [],
});

const canCreate = computed(() => auth.can('users.create'));
const canUpdate = computed(() => auth.can('users.update'));
const canDelete = computed(() => auth.can('users.delete'));

const title = computed(() => (editing.value ? 'Ubah Peran' : 'Tambah Peran'));

onMounted(load);

async function load() {
    loading.value = true;
    error.value = '';

    try {
        const [roleResponse, permissionResponse] = await Promise.all([
            client.get('/roles'),
            client.get('/permissions'),
        ]);

        roles.value = roleResponse.data.data;
        modules.value = permissionResponse.data.data;
    } catch (exception) {
        error.value = readableError(exception, 'Gagal memuat daftar peran.') ?? '';
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    editing.value = null;
    form.name = '';
    form.permissions = [];
    errors.value = {};
    error.value = '';
    showForm.value = true;
}

function openEdit(role) {
    editing.value = role;
    form.name = role.name;
    form.permissions = [...role.permissions];
    errors.value = {};
    error.value = '';
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editing.value = null;
    errors.value = {};
    error.value = '';
}

async function submit() {
    error.value = '';
    errors.value = {};
    submitting.value = true;

    try {
        const payload = { name: form.name, permissions: form.permissions };

        if (editing.value) {
            await client.patch(`/roles/${editing.value.id}`, payload);
        } else {
            await client.post('/roles', payload);
        }

        await load();
        closeForm();
    } catch (exception) {
        // Only `name` is an editable field here; anything else (a taken slug,
        // a rejected permission key) belongs in the banner because the user
        // has no input to point at.
        const extracted = extractFieldErrors(exception);

        errors.value = extracted.name ? { name: extracted.name } : {};
        error.value = readableError(exception, 'Gagal menyimpan peran.') ?? '';
    } finally {
        submitting.value = false;
    }
}

async function remove(role) {
    if (! window.confirm(`Hapus peran "${role.name}"?`)) {
        return;
    }

    error.value = '';
    errors.value = {};

    try {
        await client.delete(`/roles/${role.id}`);
        await load();
    } catch (exception) {
        error.value = readableError(exception, 'Gagal menghapus peran.') ?? '';
    }
}

/**
 * @param {string} key
 * @returns {boolean}
 */
function has(key) {
    return form.permissions.includes(key);
}

/**
 * @param {string} key
 * @param {boolean} checked
 */
function toggle(key, checked) {
    form.permissions = checked
        ? [...form.permissions, key]
        : form.permissions.filter((item) => item !== key);
}

/**
 * @param {{actions: Array<{key: string}>}} module
 * @returns {string[]}
 */
function moduleKeys(module) {
    return module.actions.map((action) => action.key);
}

/**
 * @param {{actions: Array<{key: string}>}} module
 * @param {boolean} checked
 */
function toggleModule(module, checked) {
    const keys = moduleKeys(module);

    form.permissions = checked
        ? [...new Set([...form.permissions, ...keys])]
        : form.permissions.filter((key) => ! keys.includes(key));
}

/**
 * @param {{actions: Array<{key: string}>}} module
 * @returns {boolean}
 */
function isModuleChecked(module) {
    const keys = moduleKeys(module);

    return keys.length > 0 && keys.every((key) => form.permissions.includes(key));
}

/**
 * A short, unambiguous list of the keys a role grants.
 *
 * @param {Record<string, any>} role
 * @returns {string}
 */
function permissionSummary(role) {
    const keys = role.permissions ?? [];

    if (keys.length === 0) {
        return 'Tidak ada izin';
    }

    const head = keys.slice(0, 3).join(', ');

    return keys.length > 3 ? `${head} +${keys.length - 3} lainnya` : head;
}
</script>

<template>
    <div class="mx-auto w-full max-w-5xl px-6 py-8">
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Peran &amp; Izin</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Kelola paket izin yang dapat dipilih saat menambahkan pengguna di {{ auth.company?.name }}.
                </p>
            </div>

            <Button v-if="canCreate && !showForm" @click="openCreate">
                Tambah Peran
            </Button>
        </div>

        <p
            v-if="error"
            class="mb-4 rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive"
            role="alert"
        >
            {{ error }}
        </p>

        <Card v-if="showForm" class="mb-6 p-6">
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 class="text-lg font-medium">{{ title }}</h2>
                <Button variant="ghost" size="sm" @click="closeForm">Batal</Button>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="max-w-sm space-y-2">
                    <Label for="role-name" required>Nama Peran</Label>
                    <Input
                        id="role-name"
                        v-model="form.name"
                        name="name"
                        autocomplete="off"
                        placeholder="Kasir Gudang"
                        :invalid="Boolean(errors.name)"
                        required
                        @input="errors.name = ''"
                    />
                    <p v-if="errors.name" class="text-sm text-destructive" role="alert">
                        {{ errors.name }}
                    </p>
                    <p v-else class="text-xs text-muted-foreground">
                        Slug dibuat otomatis dari nama dan harus unik di dalam perusahaan.
                    </p>
                </div>

                <div>
                    <p class="mb-2 text-sm font-medium">Izin</p>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="module in modules"
                            :key="module.key"
                            class="rounded-md border p-3"
                        >
                            <label class="mb-2 flex cursor-pointer items-center gap-2 text-sm font-medium">
                                <Checkbox
                                    :model-value="isModuleChecked(module)"
                                    @update:model-value="toggleModule(module, $event)"
                                />
                                {{ module.label }}
                            </label>

                            <ul class="space-y-1.5">
                                <li
                                    v-for="action in module.actions"
                                    :key="action.key"
                                    class="flex items-center gap-2"
                                >
                                    <Checkbox
                                        :id="`permission-${action.key}`"
                                        :model-value="has(action.key)"
                                        @update:model-value="toggle(action.key, $event)"
                                    />
                                    <label
                                        :for="`permission-${action.key}`"
                                        class="cursor-pointer text-sm text-muted-foreground"
                                    >
                                        {{ action.label }}
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="submitting || form.permissions.length === 0">
                        {{ submitting ? 'Menyimpan…' : 'Simpan' }}
                    </Button>
                    <Button type="button" variant="outline" @click="closeForm">Batal</Button>
                    <span v-if="form.permissions.length === 0" class="text-xs text-muted-foreground">
                        Pilih setidaknya satu izin.
                    </span>
                </div>
            </form>
        </Card>

        <div v-if="loading" class="text-sm text-muted-foreground">
            Memuat daftar peran…
        </div>

        <div v-else-if="roles.length === 0" class="rounded-lg border border-dashed p-8 text-center">
            <p class="text-sm text-muted-foreground">
                Belum ada peran. Peran bawaan sistem akan muncul di sini setelah perusahaan dibuat.
            </p>
        </div>

        <Card v-else class="overflow-x-auto">
            <table class="w-full min-w-[40rem] text-sm">
                <thead>
                    <tr class="border-b text-left text-muted-foreground">
                        <th class="px-4 py-3 font-medium">Peran</th>
                        <th class="px-4 py-3 font-medium">Izin</th>
                        <th class="px-4 py-3 font-medium">Pengguna</th>
                        <th class="px-4 py-3 font-medium">Tipe</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="role in roles" :key="role.id" class="border-b last:border-0">
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ role.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ role.slug }}</p>
                        </td>
                        <td class="max-w-64 px-4 py-3 text-xs text-muted-foreground">
                            {{ permissionSummary(role) }}
                        </td>
                        <td class="px-4 py-3 tabular-nums">{{ role.users_count }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-secondary px-2 py-0.5 text-[11px] font-medium text-secondary-foreground">
                                {{ role.is_system ? 'Bawaan' : 'Kustom' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <Button
                                    v-if="canUpdate && !role.is_system"
                                    size="sm"
                                    variant="ghost"
                                    @click="openEdit(role)"
                                >
                                    Ubah
                                </Button>
                                <Button
                                    v-if="canDelete && !role.is_system && role.users_count === 0"
                                    size="sm"
                                    variant="ghost"
                                    class="text-destructive hover:text-destructive"
                                    @click="remove(role)"
                                >
                                    Hapus
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </Card>
    </div>
</template>
