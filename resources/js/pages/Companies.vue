<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import Button from '../components/ui/Button.vue';
import Card from '../components/ui/Card.vue';
import Input from '../components/ui/Input.vue';
import Label from '../components/ui/Label.vue';
import client from '../api/client';
import { readableError } from '../lib/errors';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const companies = ref([]);
const loading = ref(true);
const showForm = ref(false);
const error = ref('');

const form = reactive({
    name: '',
    email: '',
    phone: '',
    city: '',
});

const submitting = ref(false);

onMounted(async () => {
    try {
        const { data } = await client.get('/companies');
        companies.value = data.data;

        // Without any company there is nothing to pick, so open the form
        // straight away instead of leaving the user on a dead end.
        showForm.value = companies.value.length === 0;
    } finally {
        loading.value = false;
    }
});

async function select(companyId) {
    await auth.selectCompany(companyId);
    await router.replace('/');
}

async function createCompany() {
    error.value = '';
    submitting.value = true;

    try {
        const { data } = await client.post('/companies', form);

        await select(data.data.id);
    } catch (exception) {
        error.value = readableError(exception, 'Gagal membuat perusahaan.') ?? '';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="mx-auto w-full max-w-3xl px-6 py-12">
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Pilih Perusahaan</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    <template v-if="companies.length > 0">
                        Tentukan perusahaan tempat Anda bekerja pada sesi ini.
                    </template>
                    <template v-else>
                        Anda belum tergabung di perusahaan mana pun. Isi data di bawah untuk membuat perusahaan pertama Anda.
                    </template>
                </p>
            </div>

            <Button
                v-if="companies.length > 0"
                variant="outline"
                @click="showForm = !showForm"
            >
                {{ showForm ? 'Batal' : 'Perusahaan Baru' }}
            </Button>
        </div>

        <Card v-if="showForm" class="mb-6 p-6">
            <h2 class="mb-4 text-lg font-medium">Buat Perusahaan</h2>

            <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="createCompany">
                <div class="space-y-2 sm:col-span-2">
                    <Label for="company-name" required>Nama Perusahaan</Label>
                    <Input id="company-name" v-model="form.name" required placeholder="PT Contoh Nusantara" />
                </div>

                <div class="space-y-2">
                    <Label for="company-email">Email</Label>
                    <Input id="company-email" v-model="form.email" type="email" placeholder="halo@contoh.co.id" />
                </div>

                <div class="space-y-2">
                    <Label for="company-phone">Telepon</Label>
                    <Input id="company-phone" v-model="form.phone" placeholder="021 555 0100" />
                </div>

                <div class="space-y-2">
                    <Label for="company-city">Kota</Label>
                    <Input id="company-city" v-model="form.city" placeholder="Jakarta" />
                </div>

                <p
                    v-if="error"
                    class="rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive sm:col-span-2"
                    role="alert"
                >
                    {{ error }}
                </p>

                <div class="sm:col-span-2">
                    <Button type="submit" :disabled="submitting">
                        {{ submitting ? 'Menyimpan…' : 'Buat & Mulai' }}
                    </Button>
                </div>
            </form>
        </Card>

        <div v-if="loading" class="text-sm text-muted-foreground">
            Memuat daftar perusahaan…
        </div>

        <div v-else-if="companies.length === 0 && !showForm" class="rounded-lg border border-dashed p-8 text-center">
            <p class="text-sm text-muted-foreground">
                Anda belum tergabung di perusahaan mana pun. Isi form di atas untuk memulai.
            </p>
        </div>

        <div v-else class="space-y-3">
            <Card
                v-for="company in companies"
                :key="company.id"
                class="flex items-center justify-between gap-4 p-4"
            >
                <div class="min-w-0">
                    <p class="truncate font-medium">{{ company.name }}</p>
                    <p class="truncate text-sm text-muted-foreground">
                        {{ company.city || company.slug }} · {{ company.role?.name ?? 'Tanpa peran' }}
                    </p>
                </div>

                <Button size="sm" @click="select(company.id)">
                    Pilih
                </Button>
            </Card>
        </div>
    </div>
</template>
