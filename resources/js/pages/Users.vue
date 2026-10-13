<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import Button from '../components/ui/Button.vue';
import Card from '../components/ui/Card.vue';
import Input from '../components/ui/Input.vue';
import Label from '../components/ui/Label.vue';
import Select from '../components/ui/Select.vue';
import client from '../api/client';
import { fieldErrors as extractFieldErrors, readableError } from '../lib/errors';
import { formatDate } from '../lib/format';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();

const members = ref([]);
const roles = ref([]);
const loading = ref(true);
const showForm = ref(false);
const editing = ref(null);
const error = ref('');
const errors = ref({});
const submitting = ref(false);

const form = reactive({
    name: '',
    email: '',
    password: '',
    role_id: '',
});

const canCreate = computed(() => auth.can('users.create'));
const canUpdate = computed(() => auth.can('users.update'));
const canDelete = computed(() => auth.can('users.delete'));

/**
 * The owner bundle is reserved for the single `is_owner` member, so it never
 * appears as an assignment option.
 */
const assignableRoles = computed(() => roles.value.filter((role) => role.slug !== 'owner'));

const roleOptions = computed(() => {
    if (editing.value?.is_owner && editing.value.role) {
        return [editing.value.role];
    }

    return assignableRoles.value;
});

const title = computed(() => (editing.value ? 'Ubah Pengguna' : 'Tambah Pengguna'));

onMounted(load);

async function load() {
    loading.value = true;
    error.value = '';

    try {
        const [memberResponse, roleResponse] = await Promise.all([
            client.get('/users'),
            client.get('/roles'),
        ]);

        members.value = memberResponse.data.data;
        roles.value = roleResponse.data.data;
    } catch (exception) {
        error.value = readableError(exception, 'Gagal memuat daftar pengguna.');
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    editing.value = null;
    form.name = '';
    form.email = '';
    form.password = '';
    form.role_id = String(defaultRoleId());
    errors.value = {};
    error.value = '';
    showForm.value = true;
}

function openEdit(member) {
    editing.value = member;
    form.name = member.name;
    form.email = member.email;
    form.password = '';
    form.role_id = String(member.role?.id ?? '');
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

/**
 * New members start with the least privileged system role.
 *
 * @returns {number|string}
 */
function defaultRoleId() {
    return assignableRoles.value.find((role) => role.slug === 'staff')?.id
        ?? assignableRoles.value[0]?.id
        ?? '';
}

async function submit() {
    error.value = '';
    errors.value = {};
    submitting.value = true;

    const payload = {
        name: form.name,
        email: form.email,
        role_id: Number(form.role_id),
    };

    if (form.password !== '') {
        payload.password = form.password;
    }

    try {
        if (editing.value) {
            await client.patch(`/users/${editing.value.id}`, payload);
        } else {
            await client.post('/users', payload);
        }

        await load();
        closeForm();
    } catch (exception) {
        errors.value = extractFieldErrors(exception);
        error.value = Object.keys(errors.value).length > 0
            ? ''
            : (readableError(exception, 'Gagal menyimpan pengguna.') ?? '');
    } finally {
        submitting.value = false;
    }
}

async function remove(member) {
    if (! window.confirm(`Keluarkan ${member.name} dari perusahaan ini?`)) {
        return;
    }

    error.value = '';
    errors.value = {};

    try {
        await client.delete(`/users/${member.id}`);
        await load();
    } catch (exception) {
        error.value = readableError(exception, 'Gagal mengeluarkan pengguna.') ?? '';
    }
}

/**
 * The account itself is never deleted, only its membership, so the UI blocks
 * the two rows that would refuse the request anyway.
 *
 * @param {Record<string, any>} member
 * @returns {boolean}
 */
function isRemovable(member) {
    return canDelete.value && ! member.is_owner && member.id !== auth.user?.id;
}
</script>

<template>
    <div class="mx-auto w-full max-w-5xl px-6 py-8">
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Pengguna &amp; Akses</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Kelola siapa saja yang menjadi anggota {{ auth.company?.name }} dan peran yang mereka pegang.
                </p>
            </div>

            <Button v-if="canCreate && !showForm" @click="openCreate">
                Tambah Pengguna
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

            <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="member-name" required>Nama</Label>
                    <Input
                        id="member-name"
                        v-model="form.name"
                        name="name"
                        autocomplete="off"
                        placeholder="Siti Gudang"
                        :invalid="Boolean(errors.name)"
                        required
                        @input="errors.name = ''"
                    />
                    <p v-if="errors.name" class="text-sm text-destructive" role="alert">
                        {{ errors.name }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="member-email" required>Email</Label>
                    <Input
                        id="member-email"
                        v-model="form.email"
                        type="email"
                        name="email"
                        autocomplete="off"
                        placeholder="siti@perusahaan.co.id"
                        :invalid="Boolean(errors.email)"
                        required
                        @input="errors.email = ''"
                    />
                    <p v-if="errors.email" class="text-sm text-destructive" role="alert">
                        {{ errors.email }}
                    </p>
                    <p v-else class="text-xs text-muted-foreground">
                        Jika email sudah terdaftar, akun tersebut yang ditambahkan tanpa membuat akun baru.
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="member-password" :required="! editing">Kata Sandi</Label>
                    <Input
                        id="member-password"
                        v-model="form.password"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        placeholder="Minimal 8 karakter"
                        :invalid="Boolean(errors.password)"
                        @input="errors.password = ''"
                    />
                    <p v-if="errors.password" class="text-sm text-destructive" role="alert">
                        {{ errors.password }}
                    </p>
                    <p v-else class="text-xs text-muted-foreground">
                        {{ editing
                            ? 'Kosongkan untuk mempertahankan kata sandi yang sekarang.'
                            : 'Wajib diisi untuk akun baru.' }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="member-role" required>Peran</Label>
                    <Select
                        id="member-role"
                        v-model="form.role_id"
                        name="role_id"
                        :invalid="Boolean(errors.role_id)"
                        :disabled="Boolean(editing?.is_owner)"
                        @update:model-value="errors.role_id = ''"
                    >
                        <option v-for="role in roleOptions" :key="role.id" :value="role.id">
                            {{ role.name }}
                        </option>
                    </Select>
                    <p v-if="errors.role_id" class="text-sm text-destructive" role="alert">
                        {{ errors.role_id }}
                    </p>
                    <p v-else class="text-xs text-muted-foreground">
                        {{ editing?.is_owner
                            ? 'Peran pemilik tidak dapat diubah.'
                            : 'Peran bawaan sistem tidak dapat diedit; buat peran baru untuk menyesuaikan izin.' }}
                    </p>
                </div>

                <div class="flex items-center gap-3 sm:col-span-2">
                    <Button type="submit" :disabled="submitting">
                        {{ submitting ? 'Menyimpan…' : 'Simpan' }}
                    </Button>
                    <Button type="button" variant="outline" @click="closeForm">Batal</Button>
                </div>
            </form>
        </Card>

        <div v-if="loading" class="text-sm text-muted-foreground">
            Memuat daftar pengguna…
        </div>

        <div v-else-if="members.length === 0" class="rounded-lg border border-dashed p-8 text-center">
            <p class="text-sm text-muted-foreground">
                Belum ada pengguna lain di perusahaan ini. Tambahkan anggota tim pertama Anda.
            </p>
        </div>

        <Card v-else class="overflow-x-auto">
            <table class="w-full min-w-[36rem] text-sm">
                <thead>
                    <tr class="border-b text-left text-muted-foreground">
                        <th class="px-4 py-3 font-medium">Nama</th>
                        <th class="px-4 py-3 font-medium">Peran</th>
                        <th class="px-4 py-3 font-medium">Bergabung</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="member in members" :key="member.id" class="border-b last:border-0">
                        <td class="px-4 py-3">
                            <p class="flex items-center gap-2 font-medium">
                                {{ member.name }}
                                <span
                                    v-if="member.is_owner"
                                    class="rounded-full bg-secondary px-2 py-0.5 text-[11px] font-medium text-secondary-foreground"
                                >
                                    Pemilik
                                </span>
                                <span
                                    v-else-if="member.is_superadmin"
                                    class="rounded-full bg-secondary px-2 py-0.5 text-[11px] font-medium text-secondary-foreground"
                                >
                                    Superadmin
                                </span>
                            </p>
                            <p class="text-xs text-muted-foreground">{{ member.email }}</p>
                        </td>
                        <td class="px-4 py-3">{{ member.role?.name ?? '—' }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ formatDate(member.joined_at) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <Button
                                    v-if="canUpdate"
                                    size="sm"
                                    variant="ghost"
                                    @click="openEdit(member)"
                                >
                                    Ubah
                                </Button>
                                <Button
                                    v-if="isRemovable(member)"
                                    size="sm"
                                    variant="ghost"
                                    class="text-destructive hover:text-destructive"
                                    @click="remove(member)"
                                >
                                    Keluarkan
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </Card>
    </div>
</template>
