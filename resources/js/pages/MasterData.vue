<script setup>
import { computed, reactive, ref } from 'vue';
import {
    AlertTriangle,
    ArrowDownToLine,
    ArrowUpFromLine,
    Boxes,
    ChevronLeft,
    ChevronRight,
    Eye,
    Package,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    Tag,
    Upload,
    Wallet,
} from 'lucide-vue-next';
import Button from '../components/ui/Button.vue';
import Card from '../components/ui/Card.vue';
import Checkbox from '../components/ui/Checkbox.vue';
import Dialog from '../components/ui/Dialog.vue';
import EmptyState from '../components/EmptyState.vue';
import Input from '../components/ui/Input.vue';
import Label from '../components/ui/Label.vue';
import Select from '../components/ui/Select.vue';
import { formatCurrency, formatNumber } from '../lib/format';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();

/**
 * Data contoh modul Master Data.
 *
 * TODO(M4): ganti dengan GET/POST/PUT /api/products/* — bentuk baris di bawah
 * ini sudah mengikuti respon API yang diharapkan.
 */
const categories = ['Bahan Baku', 'Barang Jadi', 'Sparepart'];
const units = ['Pcs', 'Unit', 'Lembar', 'Galon', 'Kilogram', 'Meter'];

const productRows = [
    {
        code: 'PRD-RAW-001',
        name: 'Baut M8 Baja Karbon',
        description: 'Grade 8.8 High Strength Galvanized',
        category: 'Bahan Baku',
        unit: 'Pcs',
        cost: 1250,
        price: 2100,
        stock: 14800,
        minStock: 500,
        active: true,
    },
    {
        code: 'PRD-RAW-014',
        name: 'Plat Aluminium 2mm (120x240)',
        description: 'Alloy 5052-H32 Lembaran Industri',
        category: 'Bahan Baku',
        unit: 'Lembar',
        cost: 385000,
        price: 460000,
        stock: 184,
        minStock: 30,
        active: true,
    },
    {
        code: 'PRD-SPR-082',
        name: 'Pelumas Sintetik ISO VG 68 (5L)',
        description: 'Hydro-Treat Heavy Duty Lubricant',
        category: 'Sparepart',
        unit: 'Galon',
        cost: 245000,
        price: 310000,
        stock: 42,
        minStock: 10,
        active: true,
    },
    {
        code: 'PRD-FIN-091',
        name: 'Kemasan Karton Box Double Wall A1',
        description: 'Dimensi 60x40x40 K150/M125/K150',
        category: 'Barang Jadi',
        unit: 'Pcs',
        cost: 8700,
        price: 12500,
        stock: 3250,
        minStock: 500,
        active: true,
    },
    {
        code: 'PRD-SPR-019',
        name: 'Deep Groove Ball Bearing 6204 2RS',
        description: 'Rubber Seal, C3 Clearance Japan',
        category: 'Sparepart',
        unit: 'Unit',
        cost: 32000,
        price: 48500,
        stock: 620,
        minStock: 50,
        active: true,
    },
    {
        code: 'PRD-FIN-003',
        name: 'Motor Induksi 3-Phase 1HP 1400RPM',
        description: 'Cast Iron Housing B3 Foot Mounted [Discontinued]',
        category: 'Barang Jadi',
        unit: 'Unit',
        cost: 1450000,
        price: 1950000,
        stock: 0,
        minStock: 5,
        active: false,
    },
];

/** Alokasi stok per gudang (contoh, dihitung dari satu SKU terpilih). */
const warehouseAllocations = [
    { name: 'Gudang Utama Cakung (GD-CKG-01)', bin: 'Rak A4 - Bin 12', qty: 11400, state: 'Tersedia' },
    { name: 'Gudang Transit Marunda (GD-MRD-02)', bin: 'Area Unloading Dock B', qty: 3400, state: 'Dalam Karantina QC' },
    { name: 'Gudang Pabrik Cikarang (GD-CKR-03)', bin: 'Lantai 1 - Jalur Produksi', qty: 0, state: 'Stok Kosong' },
];

const historyEntries = [
    {
        icon: ArrowDownToLine,
        title: 'Penerimaan Barang (GRN-2024-081)',
        date: '14 Okt 2024',
        detail: '+5.000 Pcs dari PT Baja Utama Logam via PO-8891',
        direction: 'in',
    },
    {
        icon: ArrowUpFromLine,
        title: 'Pengeluaran Produksi (WO-1092)',
        date: '11 Okt 2024',
        detail: '-800 Pcs dialokasikan untuk Perakitan Generator',
        direction: 'out',
    },
    {
        icon: Tag,
        title: 'Pembaruan Harga Jual',
        date: '02 Okt 2024',
        detail: 'Penyesuaian dari Rp 1.950.000 menjadi Rp 2.100.000 oleh Dewi Sartika',
        direction: 'price',
    },
];

/** Angka agregat global — placeholder, dihitung dari seluruh katalog. */
const totalSkus = 1428;
const lowStockSkus = 14;
const assetValuation = 'Rp 4,82 M';
const topCategory = 'Bahan Baku';

const filters = reactive({
    search: '',
    category: '',
    status: '',
});

const showDrawer = ref(false);
const drawerMode = ref('create');
const drawerTab = ref('basic');
const showNameError = ref(false);
const toast = ref('');
let toastTimer = null;

const form = reactive({
    code: '',
    name: '',
    category: categories[0],
    unit: units[0],
    cost: '',
    price: '',
    minStock: 50,
    active: true,
});

const canCreate = computed(() => auth.can('master.create'));
const canUpdate = computed(() => auth.can('master.update'));

const kpis = computed(() => [
    {
        label: 'Total SKU Terdaftar',
        value: formatNumber(totalSkus),
        icon: Boxes,
        tone: 'bg-muted text-primary',
        valueTone: 'text-foreground',
    },
    {
        label: 'Stok Menipis (Warning)',
        value: `${formatNumber(lowStockSkus)} SKU`,
        icon: AlertTriangle,
        tone: 'border border-danger-border bg-danger-surface text-danger',
        valueTone: 'text-danger',
    },
    {
        label: 'Valuasi Aset Fisik',
        value: assetValuation,
        icon: Wallet,
        tone: 'bg-muted text-primary',
        valueTone: 'text-foreground',
    },
    {
        label: 'Kategori Terbanyak',
        value: topCategory,
        icon: Tag,
        tone: 'bg-muted text-primary',
        valueTone: 'text-foreground',
    },
]);

const filteredProducts = computed(() => {
    const query = filters.search.toLowerCase().trim();

    return productRows.filter((row) => {
        const haystack = `${row.code} ${row.name} ${row.description}`.toLowerCase();
        const matchesSearch = haystack.includes(query);
        const matchesCategory = filters.category === '' || row.category === filters.category;
        const matchesStatus = filters.status === ''
            || (filters.status === 'Aktif' ? row.active : ! row.active);

        return matchesSearch && matchesCategory && matchesStatus;
    });
});

const hasActiveFilters = computed(() => filters.search !== ''
    || filters.category !== ''
    || filters.status !== '');

const drawerTitle = computed(() => {
    if (drawerMode.value === 'create') {
        return 'Tambah Produk Baru';
    }

    if (drawerMode.value === 'detail') {
        return `Detail: ${form.name}`;
    }

    return `Ubah: ${form.name}`;
});

const drawerPretitle = computed(() => {
    if (drawerMode.value === 'create') {
        return 'Formulir Master';
    }

    if (drawerMode.value === 'detail') {
        return 'Pratinjau SKU';
    }

    return 'Edit Master SKU';
});

const drawerIcon = computed(() => (drawerMode.value === 'edit' ? Pencil : Package));

const nameIsInvalid = computed(() => form.name.trim().length < 3);
const formIsValid = computed(() => ! nameIsInvalid.value);

const tabs = [
    { key: 'basic', label: 'Informasi Dasar' },
    { key: 'stock', label: 'Stok per Gudang' },
    { key: 'history', label: 'Riwayat Transaksi' },
];

function resetFilters() {
    filters.search = '';
    filters.category = '';
    filters.status = '';
}

function openCreate() {
    drawerMode.value = 'create';
    drawerTab.value = 'basic';
    showNameError.value = false;
    Object.assign(form, {
        code: `PRD-NEW-${Math.floor(100 + Math.random() * 900)}`,
        name: '',
        category: categories[0],
        unit: units[0],
        cost: '',
        price: '',
        minStock: 50,
        active: true,
    });
    showDrawer.value = true;
}

/**
 * @param {(typeof productRows)[number]} row
 */
function openEdit(row) {
    drawerMode.value = 'edit';
    drawerTab.value = 'basic';
    showNameError.value = false;
    fillForm(row);
    showDrawer.value = true;
}

/**
 * Tombol mata membuka tab alokasi gudang, sesuai mockup.
 *
 * @param {(typeof productRows)[number]} row
 */
function openDetail(row) {
    drawerMode.value = 'detail';
    drawerTab.value = 'stock';
    showNameError.value = false;
    fillForm(row);
    showDrawer.value = true;
}

/**
 * @param {(typeof productRows)[number]} row
 */
function fillForm(row) {
    Object.assign(form, {
        code: row.code,
        name: row.name,
        category: row.category,
        unit: row.unit,
        cost: row.cost,
        price: row.price,
        minStock: row.minStock,
        active: row.active,
    });
}

function saveProduct() {
    showNameError.value = true;

    if (! formIsValid.value) {
        document.getElementById('product-name')?.focus();
        return;
    }

    const message = drawerMode.value === 'create'
        ? `Master produk ${form.code} berhasil disimpan.`
        : `Perubahan ${form.code} berhasil disimpan.`;

    showDrawer.value = false;
    notify(message);
}

function importSpreadsheet() {
    notify('Impor Excel menyusul setelah endpoint master data tersambung.');
}

/**
 * @param {string} message
 */
function notify(message) {
    toast.value = message;
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => {
        toast.value = '';
    }, 3000);
}
</script>

<template>
    <div class="mx-auto w-full max-w-6xl space-y-4 px-4 py-8 sm:px-6">
        <!-- Page header -->
        <Card class="flex flex-col gap-4 p-6 md:flex-row md:items-start md:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                    <span>Master Data</span>
                    <span>/</span>
                    <span class="font-semibold text-foreground">Katalog Inventaris</span>
                </div>

                <h1 class="text-2xl font-semibold tracking-tight text-foreground">
                    Katalog Produk &amp; Barang
                </h1>
                <p class="text-sm text-muted-foreground">
                    Kelola master data produk, satuan, harga beli/jual, dan ambang batas minimum stok.
                </p>
                <p class="text-xs text-muted-foreground">
                    Data contoh — angka akan tersambung ke API Master Data setelah endpoint produk tersedia.
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-2 self-start md:self-auto">
                <Button v-if="canCreate" variant="outline" @click="importSpreadsheet">
                    <Upload class="h-4 w-4" />
                    Impor Excel
                </Button>

                <Button v-if="canCreate" @click="openCreate">
                    <Plus class="h-4 w-4" />
                    Tambah Produk
                </Button>
            </div>
        </Card>

        <!-- KPI ribbon -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card
                v-for="item in kpis"
                :key="item.label"
                class="flex items-center justify-between gap-3 p-4"
            >
                <div class="min-w-0">
                    <p class="block text-xs text-muted-foreground">{{ item.label }}</p>
                    <p class="mt-1 truncate text-2xl font-semibold tabular-nums" :class="item.valueTone">
                        {{ item.value }}
                    </p>
                </div>
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                    :class="item.tone"
                >
                    <component :is="item.icon" class="h-5 w-5" />
                </span>
            </Card>
        </div>

        <!-- Filter bar -->
        <Card class="p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="flex flex-1 items-center gap-2">
                        <Search class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                        <Input
                            v-model="filters.search"
                            type="search"
                            aria-label="Cari produk"
                            placeholder="Cari kode atau nama produk…"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <Select
                            v-model="filters.category"
                            aria-label="Saring menurut kategori"
                            class="sm:w-44"
                        >
                            <option value="">Semua Kategori</option>
                            <option v-for="category in categories" :key="category" :value="category">
                                {{ category }}
                            </option>
                        </Select>

                        <Select
                            v-model="filters.status"
                            aria-label="Saring menurut status"
                            class="sm:w-36"
                        >
                            <option value="">Semua Status</option>
                            <option value="Aktif">Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </Select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <Button
                        variant="ghost"
                        size="sm"
                        :disabled="! hasActiveFilters"
                        @click="resetFilters"
                    >
                        <RotateCcw class="h-4 w-4" />
                        Reset Filter
                    </Button>
                </div>
            </div>
        </Card>

        <!-- Data table -->
        <Card class="overflow-hidden">
            <div v-if="filteredProducts.length > 0" class="overflow-x-auto">
                <table class="w-full min-w-[56rem] border-collapse text-left">
                    <thead>
                        <tr class="bg-muted text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Nama Produk</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3 text-center">Satuan</th>
                            <th class="px-4 py-3 text-right">Harga Beli</th>
                            <th class="px-4 py-3 text-right">Harga Jual</th>
                            <th class="px-4 py-3 text-right">Stok Total</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody v-if="filteredProducts.length > 0" class="divide-y divide-border/60 text-sm">
                        <tr
                            v-for="row in filteredProducts"
                            :key="row.code"
                            class="cursor-pointer transition-colors hover:bg-accent/60"
                            :class="{ 'opacity-75': ! row.active }"
                        >
                            <td class="px-4 py-3.5 font-semibold tabular-nums text-primary">
                                {{ row.code }}
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="h-2 w-2 shrink-0 rounded-full"
                                        :class="row.active ? 'bg-primary' : 'bg-border'"
                                    />
                                    <span class="font-semibold text-foreground">{{ row.name }}</span>
                                </div>
                                <span class="block pl-4 text-xs text-muted-foreground">
                                    {{ row.description }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="inline-block rounded bg-muted px-2 py-0.5 text-xs font-medium text-foreground">
                                    {{ row.category }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center text-xs tabular-nums text-muted-foreground">
                                {{ row.unit }}
                            </td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-foreground">
                                {{ formatCurrency(row.cost) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-semibold tabular-nums text-foreground">
                                {{ formatCurrency(row.price) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-semibold tabular-nums text-foreground">
                                {{ formatNumber(row.stock) }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium"
                                    :class="row.active
                                        ? 'border-border bg-muted text-foreground'
                                        : 'border-border bg-accent text-muted-foreground'"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="row.active ? 'bg-primary' : 'bg-muted-foreground/60'"
                                    />
                                    {{ row.active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <Button
                                        v-if="canUpdate"
                                        variant="ghost"
                                        size="icon"
                                        title="Ubah Data"
                                        :aria-label="`Ubah ${row.name}`"
                                        @click="openEdit(row)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Button>

                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        title="Detail Info"
                                        :aria-label="`Detail ${row.name}`"
                                        @click="openDetail(row)"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-else
                :icon="Search"
                title="Produk Tidak Ditemukan"
                description="Kombinasi kata kunci, kategori, atau status yang Anda pilih tidak memiliki kecocokan pada katalog produk."
                action-label="Pulihkan Semua Filter"
                @action="resetFilters"
            />

            <div
                class="flex flex-col gap-3 border-t border-border/60 bg-muted/40 px-4 py-4 text-xs sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="tabular-nums text-muted-foreground">
                    Menampilkan <span class="font-semibold text-foreground">1-{{ filteredProducts.length }}</span>
                    dari <span class="font-semibold text-foreground">{{ formatNumber(totalSkus) }}</span> produk
                </p>

                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        disabled
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-muted text-muted-foreground disabled:opacity-40"
                        aria-label="Halaman sebelumnya"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-primary text-xs font-medium tabular-nums text-primary-foreground"
                        aria-current="page"
                    >
                        1
                    </button>
                    <button
                        v-for="page in [2, 3, 8]"
                        :key="page"
                        type="button"
                        disabled
                        title="Menyusul setelah data master data tersambung ke API."
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-muted text-xs tabular-nums text-muted-foreground transition-colors hover:bg-accent disabled:opacity-40"
                    >
                        {{ page }}
                    </button>
                    <span class="px-1 text-muted-foreground">…</span>
                    <button
                        type="button"
                        disabled
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-muted text-muted-foreground disabled:opacity-40"
                        aria-label="Halaman berikutnya"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </Card>

        <!-- Slide-over: formulir master produk -->
        <Dialog
            v-model="showDrawer"
            side="right"
            :title="drawerTitle"
            :icon="drawerIcon"
        >
            <template #pretitle>{{ drawerPretitle }}</template>

            <template #tabs>
                <nav class="flex gap-4" aria-label="Bagian formulir produk">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="border-b-2 py-2.5 text-sm transition-colors"
                        :class="drawerTab === tab.key
                            ? 'border-primary font-semibold text-foreground'
                            : 'border-transparent text-muted-foreground hover:text-foreground'"
                        :aria-current="drawerTab === tab.key ? 'page' : undefined"
                        @click="drawerTab = tab.key"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </template>

            <!-- Tab 1: informasi dasar -->
            <div v-if="drawerTab === 'basic'" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between gap-2">
                            <Label for="product-code">Kode Produk</Label>
                            <span class="rounded bg-muted px-1.5 py-0.5 text-[10px] text-muted-foreground">
                                Auto-suggest
                            </span>
                        </div>
                        <Input id="product-code" :model-value="form.code" readonly class="tabular-nums" />
                        <p class="text-xs text-muted-foreground">Gunakan format prefix PRD-[KATEGORI]-[NOMOR]</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="product-category" required>Kategori</Label>
                        <Select id="product-category" v-model="form.category" name="category">
                            <option v-for="category in categories" :key="category" :value="category">
                                {{ category }}
                            </option>
                        </Select>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label for="product-name" required>Nama Produk Resmi</Label>
                    <Input
                        id="product-name"
                        v-model="form.name"
                        name="name"
                        placeholder="Contoh: Baut Flange Hexagonal M10x50…"
                        :invalid="showNameError && nameIsInvalid"
                        @keyup.enter="saveProduct"
                    />
                    <p
                        v-if="showNameError && nameIsInvalid"
                        class="text-xs text-destructive"
                        role="alert"
                    >
                        Nama produk wajib diisi dengan minimal 3 karakter teknis.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="space-y-1.5">
                        <Label for="product-unit">Satuan Dasar</Label>
                        <Select id="product-unit" v-model="form.unit" name="unit">
                            <option v-for="unit in units" :key="unit" :value="unit">{{ unit }}</option>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="product-cost">Harga Beli (HPP)</Label>
                        <div class="flex items-center gap-2">
                            <span class="shrink-0 text-xs font-semibold text-muted-foreground">Rp</span>
                            <Input
                                id="product-cost"
                                v-model="form.cost"
                                name="cost"
                                type="number"
                                min="0"
                                placeholder="0"
                                class="text-right tabular-nums"
                            />
                        </div>
                        <p class="text-[11px] text-muted-foreground">Harga netto sebelum PPN</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="product-price">Harga Jual Standar</Label>
                        <div class="flex items-center gap-2">
                            <span class="shrink-0 text-xs font-semibold text-muted-foreground">Rp</span>
                            <Input
                                id="product-price"
                                v-model="form.price"
                                name="price"
                                type="number"
                                min="0"
                                placeholder="0"
                                class="text-right tabular-nums"
                            />
                        </div>
                        <p class="text-[11px] text-muted-foreground">Harga rujukan faktur sales</p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label for="product-min-stock">Ambang Batas Minimum Stok</Label>
                        <Input
                            id="product-min-stock"
                            v-model="form.minStock"
                            name="minStock"
                            type="number"
                            min="0"
                            placeholder="Contoh: 100"
                            class="text-right tabular-nums"
                        />
                        <p class="text-xs text-muted-foreground">
                            Sistem memberi notifikasi peringatan jika stok di bawah angka ini.
                        </p>
                    </div>

                    <label
                        class="flex items-start gap-3 self-end rounded-lg border bg-muted/50 p-4"
                    >
                        <Checkbox v-model="form.active" class="mt-0.5" />
                        <span class="flex flex-col">
                            <span class="text-sm font-semibold text-foreground">Status Aktif Produk</span>
                            <span class="text-xs text-muted-foreground">
                                Item dapat dipilih dalam modul PO, SO, dan WO.
                            </span>
                        </span>
                    </label>
                </div>
            </div>

            <!-- Tab 2: stok per gudang -->
            <div v-else-if="drawerTab === 'stock'" class="space-y-4">
                <div class="space-y-3 rounded-lg bg-muted/60 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Alokasi Fisik Real-time
                    </p>

                    <div class="space-y-2">
                        <div
                            v-for="allocation in warehouseAllocations"
                            :key="allocation.name"
                            class="flex items-center justify-between gap-3 rounded-md border bg-card p-3"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-foreground">
                                    {{ allocation.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">{{ allocation.bin }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="tabular-nums text-sm font-semibold text-foreground">
                                    {{ formatNumber(allocation.qty) }} {{ form.unit }}
                                </p>
                                <p
                                    class="text-xs font-medium"
                                    :class="allocation.qty === 0
                                        ? 'text-destructive'
                                        : allocation.state === 'Tersedia'
                                            ? 'text-success'
                                            : 'text-muted-foreground'"
                                >
                                    {{ allocation.state }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-muted-foreground">
                    Data contoh — alokasi per gudang akan dihitung dari tabel stok setelah modul Inventory tersambung.
                </p>
            </div>

            <!-- Tab 3: riwayat transaksi -->
            <div v-else class="space-y-2">
                <div
                    v-for="entry in historyEntries"
                    :key="entry.title"
                    class="flex items-start gap-3 rounded-md bg-muted/60 p-3"
                >
                    <span
                        class="shrink-0 rounded-md border bg-card p-1.5 text-foreground"
                        :class="entry.direction === 'in'
                            ? 'text-success'
                            : entry.direction === 'out'
                                ? 'text-destructive'
                                : 'text-foreground'"
                    >
                        <component :is="entry.icon" class="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <span class="truncate text-sm font-semibold text-foreground">
                                {{ entry.title }}
                            </span>
                            <span class="shrink-0 text-xs tabular-nums text-muted-foreground">
                                {{ entry.date }}
                            </span>
                        </div>
                        <p class="text-xs text-muted-foreground">{{ entry.detail }}</p>
                    </div>
                </div>

                <p class="pt-2 text-xs text-muted-foreground">
                    Data contoh — riwayat akan diambil dari kartu stok (modul Inventory) dan jurnal pembelian.
                </p>
            </div>

            <template #footer>
                <div class="flex items-center justify-between gap-2">
                    <Button variant="outline" @click="showDrawer = false">Batal</Button>

                    <div class="flex items-center gap-2">
                        <Button
                            v-if="drawerMode !== 'detail'"
                            :disabled="drawerMode === 'create' ? ! canCreate : ! canUpdate"
                            @click="saveProduct"
                        >
                            Simpan Master Produk
                        </Button>
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- Toast -->
        <div
            v-if="toast"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-lg border border-success-border bg-success-surface px-4 py-3 text-sm font-medium text-success shadow-lg"
            role="status"
        >
            <Package class="h-4 w-4" />
            <span>{{ toast }}</span>
        </div>
    </div>
</template>
