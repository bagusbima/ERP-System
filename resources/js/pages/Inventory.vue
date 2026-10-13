<script setup>
import { computed, reactive, ref } from 'vue';
import {
    AlertTriangle,
    ArrowDownLeft,
    ArrowLeftRight,
    ArrowUpRight,
    ChevronLeft,
    ChevronRight,
    ClipboardCheck,
    CircleCheck,
    Download,
    History,
    LayoutGrid,
    ListChecks,
    Package,
    ReceiptText,
    RotateCcw,
    Search,
    Truck,
    Warehouse,
} from 'lucide-vue-next';
import Button from '../components/ui/Button.vue';
import Card from '../components/ui/Card.vue';
import Checkbox from '../components/ui/Checkbox.vue';
import Dialog from '../components/ui/Dialog.vue';
import EmptyState from '../components/EmptyState.vue';
import Input from '../components/ui/Input.vue';
import Label from '../components/ui/Label.vue';
import Select from '../components/ui/Select.vue';
import { formatNumber } from '../lib/format';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();

/**
 * Data contoh modul Inventory.
 *
 * TODO(M3): ganti dengan GET /api/inventory/* begitu tabel gudang, stok,
 * mutasi, transfer dan penyesuaian tersedia. Bentuk baris di bawah ini sudah
 * mengikuti respon API yang diharapkan.
 */
const warehouseOptions = ['Gudang Utama Cakung', 'Gudang Transit Surabaya', 'Gudang Bahan Baku'];

const stockRows = [
    {
        code: 'SKU-PP-9901',
        name: 'Polypropylene Granule Grade A',
        category: 'Bahan Baku Polimer',
        unit: 'Sak (25kg)',
        warehouse: 'Gudang Utama Cakung',
        stock: 42,
        minimum: 100,
    },
    {
        code: 'SKU-HDPE-204',
        name: 'High Density PE Sheet 2mm',
        category: 'Lembaran Plastik Rigid',
        unit: 'Lembar',
        warehouse: 'Gudang Utama Cakung',
        stock: 320,
        minimum: 150,
    },
    {
        code: 'SKU-CL-088',
        name: 'Masterbatch Colorant Blue 08',
        category: 'Pigmen Pewarna Industri',
        unit: 'Drum (10kg)',
        warehouse: 'Gudang Bahan Baku',
        stock: 15,
        minimum: 50,
    },
    {
        code: 'SKU-PK-102',
        name: 'Karton Box Corrugated 40x40',
        category: 'Kemasan Sekunder',
        unit: 'Bundle (25 pcs)',
        warehouse: 'Gudang Transit Surabaya',
        stock: 850,
        minimum: 300,
    },
    {
        code: 'SKU-FL-772',
        name: 'Stretch Film Wrapping Roll 500mm',
        category: 'Logistik Packing',
        unit: 'Roll',
        warehouse: 'Gudang Transit Surabaya',
        stock: 28,
        minimum: 60,
    },
    {
        code: 'SKU-ADH-301',
        name: 'Solvent Glue Adhesive Grade 4',
        category: 'Kimia Penunjang',
        unit: 'Jerigen (20L)',
        warehouse: 'Gudang Bahan Baku',
        stock: 190,
        minimum: 80,
    },
];

const movements = [
    {
        at: '08 Mar 2025 14:10',
        document: 'SJ-OUT-2025-091',
        product: 'Polypropylene Granule Grade A',
        warehouse: 'Gudang Utama Cakung',
        masuk: 0,
        keluar: 20,
        balance: 42,
    },
    {
        at: '08 Mar 2025 11:45',
        document: 'PO-RCV-2025-882',
        product: 'High Density PE Sheet 2mm',
        warehouse: 'Gudang Utama Cakung',
        masuk: 100,
        keluar: 0,
        balance: 320,
    },
    {
        at: '08 Mar 2025 09:20',
        document: 'TRF-JKT-SBY-04',
        product: 'Stretch Film Wrapping Roll 500mm',
        warehouse: 'Gudang Transit Surabaya',
        masuk: 0,
        keluar: 12,
        balance: 28,
    },
    {
        at: '07 Mar 2025 17:02',
        document: 'ADJ-OPN-2025-01',
        product: 'Masterbatch Colorant Blue 08',
        warehouse: 'Gudang Bahan Baku',
        masuk: 0,
        keluar: 5,
        balance: 15,
    },
    {
        at: '07 Mar 2025 15:30',
        document: 'PO-RCV-2025-879',
        product: 'Karton Box Corrugated 40x40',
        warehouse: 'Gudang Transit Surabaya',
        masuk: 250,
        keluar: 0,
        balance: 850,
    },
];

const transferRows = [
    {
        number: 'TRF-JKT-SBY-04',
        from: 'Gudang Utama Cakung',
        to: 'Gudang Transit Surabaya',
        code: 'SKU-FL-772',
        qty: 12,
        date: '08 Mar 2025',
        status: 'Dikirim',
    },
    {
        number: 'TRF-BBK-CKG-02',
        from: 'Gudang Bahan Baku',
        to: 'Gudang Utama Cakung',
        code: 'SKU-ADH-301',
        qty: 40,
        date: '08 Mar 2025',
        status: 'Draft',
    },
    {
        number: 'TRF-SBY-BBK-01',
        from: 'Gudang Transit Surabaya',
        to: 'Gudang Bahan Baku',
        code: 'SKU-PK-102',
        qty: 120,
        date: '07 Mar 2025',
        status: 'Diterima',
    },
];

const adjustmentRows = [
    {
        number: 'ADJ-OPN-2025-01',
        at: '07 Mar 2025',
        code: 'SKU-CL-088',
        system: 20,
        counted: 15,
        reason: 'Kerusakan kemasan saat pemindahan rak B-04',
        status: 'Menunggu',
    },
    {
        number: 'ADJ-OPN-2025-02',
        at: '06 Mar 2025',
        code: 'SKU-HDPE-204',
        system: 318,
        counted: 320,
        reason: 'Selisih pembulatan unit pemakaian',
        status: 'Disetujui',
    },
];

/** Angka agregat global — placeholder, dihitung dari seluruh katalog. */
const totalSkus = 1428;
const criticalSkus = 14;
const inboundToday = 4820;
const outboundToday = 3150;
const lastRefreshedAt = '08 Mar 2025 14:32 WIB';

const tabs = [
    { key: 'stock', label: 'Posisi Stok Real-Time', icon: LayoutGrid, badge: '1.4K' },
    { key: 'movement', label: 'Mutasi & Kartu Stok', icon: ReceiptText, badge: '' },
    { key: 'transfer', label: 'Transfer Antar Gudang', icon: Truck, badge: '3 Aktif' },
    { key: 'opname', label: 'Penyesuaian (Opname)', icon: ListChecks, badge: '' },
];

const activeTab = ref('stock');

const filters = reactive({
    warehouse: 'ALL',
    search: '',
    criticalOnly: false,
});

const showTransferDialog = ref(false);
const showOpnameDialog = ref(false);
const toast = ref('');
let toastTimer = null;

const transferForm = reactive({
    from: warehouseOptions[0],
    to: warehouseOptions[1],
    code: stockRows[0].code,
    qty: 25,
    note: '',
});

const opnameForm = reactive({
    code: stockRows[0].code,
    counted: '',
    reason: '',
});

const canCreateTransfer = computed(() => auth.can('inventory.create'));
const canApproveAdjustment = computed(() => auth.can('inventory.approve'));
const canExport = computed(() => auth.can('report.export'));
const canCreatePo = computed(() => auth.can('purchasing.create'));

const kpis = computed(() => [
    {
        label: 'Total SKUs Terdaftar',
        value: formatNumber(totalSkus),
        unit: 'item',
        tone: 'bg-muted text-foreground',
        valueTone: 'text-foreground',
        icon: Package,
    },
    {
        label: 'SKU Stok Kritis',
        value: formatNumber(criticalSkus),
        unit: 'perlu PO segera',
        tone: 'border border-danger-border bg-danger-surface text-danger',
        valueTone: 'text-danger',
        icon: AlertTriangle,
    },
    {
        label: 'Mutasi Masuk Hari Ini',
        value: `+${formatNumber(inboundToday)}`,
        unit: 'unit',
        tone: 'bg-accent text-primary',
        valueTone: 'text-foreground',
        icon: ArrowDownLeft,
    },
    {
        label: 'Mutasi Keluar Hari Ini',
        value: `-${formatNumber(outboundToday)}`,
        unit: 'unit',
        tone: 'bg-muted text-foreground',
        valueTone: 'text-foreground',
        icon: ArrowUpRight,
    },
]);

const filteredStocks = computed(() => {
    const query = filters.search.toLowerCase().trim();

    return stockRows.filter((row) => {
        const matchesWarehouse = filters.warehouse === 'ALL' || row.warehouse === filters.warehouse;
        const matchesQuery = row.code.toLowerCase().includes(query) || row.name.toLowerCase().includes(query);
        const matchesCritical = ! filters.criticalOnly || isCritical(row);

        return matchesWarehouse && matchesQuery && matchesCritical;
    });
});

const hasActiveFilters = computed(() => filters.warehouse !== 'ALL'
    || filters.search !== ''
    || filters.criticalOnly);

const skuOptions = computed(() => stockRows.map((row) => ({
    value: row.code,
    label: `${row.code} - ${row.name} (Tersedia: ${formatNumber(row.stock)})`,
})));

const selectedSystemStock = computed(() => {
    const row = stockRows.find((item) => item.code === opnameForm.code);

    return row ? `${formatNumber(row.stock)} ${row.unit}` : '—';
});

const transferIsValid = computed(() => transferForm.from !== transferForm.to
    && Number(transferForm.qty) > 0);

const opnameIsValid = computed(() => opnameForm.counted !== '' && opnameForm.reason.trim() !== '');

/**
 * @param {{stock: number, minimum: number}} row
 * @returns {boolean}
 */
function isCritical(row) {
    return row.stock <= row.minimum;
}

/**
 * @param {{stock: number, minimum: number}} row
 * @returns {number}
 */
function difference(row) {
    return row.stock - row.minimum;
}

function resetFilters() {
    filters.warehouse = 'ALL';
    filters.search = '';
    filters.criticalOnly = false;
}

function openTransfer() {
    transferForm.from = warehouseOptions[0];
    transferForm.to = warehouseOptions[1];
    transferForm.code = stockRows[0].code;
    transferForm.qty = 25;
    transferForm.note = '';
    showTransferDialog.value = true;
}

function openOpname() {
    opnameForm.code = stockRows[0].code;
    opnameForm.counted = '';
    opnameForm.reason = '';
    showOpnameDialog.value = true;
}

function submitTransfer() {
    showTransferDialog.value = false;
    notify('Permintaan transfer gudang berhasil didaftarkan.');
}

function submitOpname() {
    showOpnameDialog.value = false;
    notify('Penyesuaian stok diajukan untuk otorisasi manajer.');
}

function showMovements(row) {
    activeTab.value = 'movement';
    notify(`Menampilkan kartu stok untuk ${row.name}.`);
}

function createPurchaseOrder(row) {
    notify(`Draft PO untuk ${row.code} disiapkan di modul Pembelian.`);
}

function exportReport() {
    notify('Laporan persediaan sedang disiapkan untuk diunduh.');
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
    <div class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6">
        <div class="space-y-6">
            <!-- Page header -->
            <div class="flex flex-col gap-4 border-b border-border/60 pb-6 md:flex-row md:items-center md:justify-between">
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                            Modul Logistik
                        </span>
                        <span class="h-1.5 w-1.5 rounded-full bg-primary" />
                        <span class="text-xs text-muted-foreground">Sinkronisasi Real-Time: Aktif</span>
                    </div>

                    <h1 class="text-3xl font-semibold tracking-tight text-foreground">
                        Monitoring Stok &amp; Kartu Gudang
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Pantau posisi stok fisik real-time, status barang kritis, dan mutasi antar gudang.
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Data contoh — angka akan tersambung ke API Inventory setelah migrasi gudang dan stok selesai.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Button v-if="canCreateTransfer" variant="outline" @click="openTransfer">
                        <ArrowLeftRight class="h-4 w-4" />
                        Transfer Gudang
                    </Button>

                    <Button v-if="canCreateTransfer" variant="outline" @click="openOpname">
                        <ClipboardCheck class="h-4 w-4" />
                        Penyesuaian Stok
                    </Button>

                    <Button v-if="canExport" @click="exportReport">
                        <Download class="h-4 w-4" />
                        Ekspor Laporan
                    </Button>
                </div>
            </div>

            <!-- KPI strip -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card
                    v-for="item in kpis"
                    :key="item.label"
                    class="flex items-center justify-between gap-3 p-4"
                >
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                            {{ item.label }}
                        </p>
                        <p class="mt-1 text-2xl font-semibold tabular-nums" :class="item.valueTone">
                            {{ item.value }}
                            <span class="text-xs font-normal text-muted-foreground">{{ item.unit }}</span>
                        </p>
                    </div>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" :class="item.tone">
                        <component :is="item.icon" class="h-5 w-5" />
                    </span>
                </Card>
            </div>

            <!-- Tabs -->
            <div class="rounded-lg border bg-card px-2 pt-2 shadow-sm">
                <div class="flex gap-1 overflow-x-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="inline-flex shrink-0 items-center gap-2 rounded-md px-4 py-2.5 text-sm font-medium transition-colors"
                        :class="activeTab === tab.key
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                        :aria-current="activeTab === tab.key ? 'page' : undefined"
                        @click="activeTab = tab.key"
                    >
                        <component :is="tab.icon" class="h-4 w-4" />
                        <span>{{ tab.label }}</span>
                        <span
                            v-if="tab.badge"
                            class="rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                            :class="activeTab === tab.key
                                ? 'bg-card text-primary'
                                : 'bg-accent text-foreground'"
                        >
                            {{ tab.badge }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Tab: Posisi stok -->
            <div v-if="activeTab === 'stock'" class="space-y-4">
                <!-- Filter bar -->
                <Card class="flex flex-col gap-3 p-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="flex w-full items-center gap-2 sm:w-60">
                            <Warehouse class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                            <Select v-model="filters.warehouse" aria-label="Saring menurut gudang">
                                <option value="ALL">Semua Gudang</option>
                                <option v-for="warehouse in warehouseOptions" :key="warehouse" :value="warehouse">
                                    {{ warehouse }}
                                </option>
                            </Select>
                        </div>

                        <div class="flex flex-1 items-center gap-2">
                            <Search class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                            <Input
                                v-model="filters.search"
                                type="search"
                                aria-label="Cari barang"
                                placeholder="Cari kode (SKU) atau nama barang…"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 pt-2 md:justify-end md:pt-0">
                        <label
                            class="inline-flex cursor-pointer select-none items-center gap-2 rounded-md bg-muted px-3 py-2 transition-colors hover:bg-accent"
                        >
                            <Checkbox v-model="filters.criticalOnly" />
                            <span class="flex items-center gap-1.5 text-xs font-medium text-foreground">
                                <span class="h-2 w-2 rounded-full bg-destructive" />
                                Hanya Stok Kritis
                            </span>
                        </label>

                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="! hasActiveFilters"
                            @click="resetFilters"
                        >
                            <RotateCcw class="h-4 w-4" />
                            Reset Filter
                        </Button>
                    </div>
                </Card>

                <!-- Data table -->
                <Card class="overflow-hidden">
                    <div class="flex flex-col gap-2 border-b border-border/60 bg-muted/50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-semibold text-foreground">Katalog Posisi Stok</h2>
                            <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium tabular-nums text-foreground">
                                {{ filteredStocks.length }} SKU ditampilkan
                            </span>
                        </div>
                        <p class="hidden text-xs text-muted-foreground sm:block">
                            Terakhir di-refresh:
                            <span class="tabular-nums text-foreground">{{ lastRefreshedAt }}</span>
                        </p>
                    </div>

                    <div v-if="filteredStocks.length > 0" class="overflow-x-auto">
                        <table class="w-full min-w-[56rem] border-collapse text-left">
                            <thead>
                                <tr class="bg-muted text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                                    <th class="px-4 py-3">Kode SKU</th>
                                    <th class="px-4 py-3">Nama Produk</th>
                                    <th class="px-4 py-3">Gudang</th>
                                    <th class="px-4 py-3 text-right">Stok Fisik</th>
                                    <th class="px-4 py-3 text-right">Stok Min.</th>
                                    <th class="px-4 py-3 text-right">Selisih</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/60 text-sm">
                                <tr
                                    v-for="row in filteredStocks"
                                    :key="row.code"
                                    class="cursor-pointer transition-colors hover:bg-accent/60"
                                >
                                    <td class="px-4 py-3.5 font-semibold tabular-nums text-foreground">
                                        <span class="flex items-center gap-2">
                                            <span
                                                class="h-6 w-1.5 shrink-0 rounded-full"
                                                :class="isCritical(row) ? 'bg-destructive' : 'bg-accent'"
                                            />
                                            {{ row.code }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <p class="font-semibold text-foreground">{{ row.name }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ row.category }} • Satuan: {{ row.unit }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3.5 text-muted-foreground">{{ row.warehouse }}</td>
                                    <td
                                        class="px-4 py-3.5 text-right font-semibold tabular-nums"
                                        :class="isCritical(row) ? 'text-destructive' : 'text-foreground'"
                                    >
                                        {{ formatNumber(row.stock) }}
                                    </td>
                                    <td class="px-4 py-3.5 text-right tabular-nums text-muted-foreground">
                                        {{ formatNumber(row.minimum) }}
                                    </td>
                                    <td
                                        class="px-4 py-3.5 text-right font-semibold tabular-nums"
                                        :class="isCritical(row) ? 'text-destructive' : 'text-muted-foreground'"
                                    >
                                        {{ difference(row) > 0 ? '+' : '' }}{{ formatNumber(difference(row)) }}
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium"
                                            :class="isCritical(row)
                                                ? 'border-danger-border bg-danger-surface text-danger'
                                                : 'border-border bg-muted text-muted-foreground'"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full"
                                                :class="isCritical(row) ? 'animate-pulse bg-destructive' : 'bg-secondary-foreground'"
                                            />
                                            {{ isCritical(row) ? 'Kritis' : 'Normal' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <Button variant="ghost" size="sm" @click="showMovements(row)">
                                                Lihat Mutasi
                                            </Button>
                                            <Button
                                                v-if="canCreatePo"
                                                size="sm"
                                                :variant="isCritical(row) ? 'default' : 'outline'"
                                                @click="createPurchaseOrder(row)"
                                            >
                                                Buat PO
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
                        title="Tidak Ada Data Barang Ditemukan"
                        description="Kombinasi filter gudang, status kritis, atau kata kunci pencarian Anda tidak memiliki kecocokan data stok yang relevan."
                        action-label="Pulihkan Semua Filter"
                        @action="resetFilters"
                    />

                    <div
                        class="flex flex-col gap-3 border-t border-border/60 bg-muted/40 px-6 py-4 text-xs sm:flex-row sm:items-center sm:justify-between"
                    >
                        <p class="text-muted-foreground">
                            Menampilkan
                            <span class="font-semibold tabular-nums text-foreground">1-{{ filteredStocks.length }}</span>
                            dari
                            <span class="font-semibold tabular-nums text-foreground">{{ formatNumber(totalSkus) }}</span>
                            catatan persediaan
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
                                v-for="page in [2, 3, 72]"
                                :key="page"
                                type="button"
                                disabled
                                title="Menyusul setelah data inventory tersambung ke API."
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
            </div>

            <!-- Tab: Mutasi & kartu stok -->
            <Card v-else-if="activeTab === 'movement'" class="p-6">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <History class="h-5 w-5 text-foreground" aria-hidden="true" />
                            <h2 class="text-base font-medium text-foreground">
                                Ringkasan Mutasi Terakhir (Kartu Stok Terpadu)
                            </h2>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Log transaksi pergudangan aktual berdasarkan surat jalan, nota penyesuaian, dan purchase order.
                        </p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 self-start rounded-full bg-muted px-3 py-1 text-xs font-medium text-foreground">
                        <span class="h-2 w-2 rounded-full bg-primary" />
                        Live Feed Audit
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[48rem] border-collapse text-left">
                        <thead>
                            <tr class="bg-muted text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                                <th class="px-3 py-2.5">Tanggal &amp; Waktu</th>
                                <th class="px-3 py-2.5">No. Dokumen</th>
                                <th class="px-3 py-2.5">Produk</th>
                                <th class="px-3 py-2.5">Gudang</th>
                                <th class="px-3 py-2.5 text-right">Masuk</th>
                                <th class="px-3 py-2.5 text-right">Keluar</th>
                                <th class="px-3 py-2.5 text-right">Saldo Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 text-xs">
                            <tr
                                v-for="item in movements"
                                :key="`${item.document}-${item.at}`"
                                class="transition-colors hover:bg-muted/50"
                            >
                                <td class="px-3 py-2.5 tabular-nums text-foreground">{{ item.at }}</td>
                                <td class="px-3 py-2.5 font-medium tabular-nums text-foreground">
                                    <span class="rounded bg-muted px-2 py-0.5">{{ item.document }}</span>
                                </td>
                                <td class="px-3 py-2.5 font-medium text-foreground">{{ item.product }}</td>
                                <td class="px-3 py-2.5 text-muted-foreground">{{ item.warehouse }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums text-muted-foreground">
                                    {{ item.masuk > 0 ? `+${formatNumber(item.masuk)}` : '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-right font-semibold tabular-nums" :class="item.keluar > 0 ? 'text-destructive' : 'text-muted-foreground'">
                                    {{ item.keluar > 0 ? `-${formatNumber(item.keluar)}` : '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-right font-semibold tabular-nums text-foreground">
                                    {{ formatNumber(item.balance) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- Tab: Transfer antar gudang -->
            <Card v-else-if="activeTab === 'transfer'" class="overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-border/60 px-6 py-4">
                    <div>
                        <h2 class="text-sm font-semibold text-foreground">Transfer Antar Gudang</h2>
                        <p class="text-xs text-muted-foreground">Perpindahan barang antar lokasi beserta status pengirimannya.</p>
                    </div>
                    <Button v-if="canCreateTransfer" size="sm" @click="openTransfer">
                        Transfer Baru
                    </Button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[52rem] border-collapse text-left">
                        <thead>
                            <tr class="bg-muted text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                                <th class="px-4 py-3">No. Transfer</th>
                                <th class="px-4 py-3">Asal</th>
                                <th class="px-4 py-3">Tujuan</th>
                                <th class="px-4 py-3">SKU</th>
                                <th class="px-4 py-3 text-right">Jumlah</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 text-sm">
                            <tr
                                v-for="item in transferRows"
                                :key="item.number"
                                class="cursor-pointer transition-colors hover:bg-accent/60"
                            >
                                <td class="px-4 py-3 font-semibold tabular-nums text-foreground">{{ item.number }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ item.from }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ item.to }}</td>
                                <td class="px-4 py-3 tabular-nums text-foreground">{{ item.code }}</td>
                                <td class="px-4 py-3 text-right font-medium tabular-nums text-foreground">
                                    {{ formatNumber(item.qty) }}
                                </td>
                                <td class="px-4 py-3 tabular-nums text-muted-foreground">{{ item.date }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium"
                                        :class="{
                                            'border-border bg-muted text-muted-foreground': item.status === 'Draft',
                                            'border-warning-border bg-warning-surface text-warning': item.status === 'Dikirim',
                                            'border-success-border bg-success-surface text-success': item.status === 'Diterima',
                                        }"
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- Tab: Penyesuaian / opname -->
            <Card v-else class="overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-border/60 px-6 py-4">
                    <div>
                        <h2 class="text-sm font-semibold text-foreground">Penyesuaian Stok (Opname)</h2>
                        <p class="text-xs text-muted-foreground">
                            Selisih hasil hitung fisik terhadap stok sistem beserta alasan penyesuaiannya.
                        </p>
                    </div>
                    <Button v-if="canCreateTransfer" size="sm" @click="openOpname">
                        Opname Baru
                    </Button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[56rem] border-collapse text-left">
                        <thead>
                            <tr class="bg-muted text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                                <th class="px-4 py-3">No. Dokumen</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">SKU</th>
                                <th class="px-4 py-3 text-right">Stok Sistem</th>
                                <th class="px-4 py-3 text-right">Hitung Fisik</th>
                                <th class="px-4 py-3 text-right">Selisih</th>
                                <th class="px-4 py-3">Alasan</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 text-sm">
                            <tr
                                v-for="item in adjustmentRows"
                                :key="item.number"
                                class="cursor-pointer transition-colors hover:bg-accent/60"
                            >
                                <td class="px-4 py-3 font-semibold tabular-nums text-foreground">{{ item.number }}</td>
                                <td class="px-4 py-3 tabular-nums text-muted-foreground">{{ item.at }}</td>
                                <td class="px-4 py-3 tabular-nums text-foreground">{{ item.code }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-muted-foreground">
                                    {{ formatNumber(item.system) }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-foreground">
                                    {{ formatNumber(item.counted) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-semibold tabular-nums"
                                    :class="item.counted - item.system < 0 ? 'text-destructive' : 'text-success'"
                                >
                                    {{ item.counted - item.system > 0 ? '+' : '' }}{{ formatNumber(item.counted - item.system) }}
                                </td>
                                <td class="max-w-[16rem] truncate px-4 py-3 text-muted-foreground">{{ item.reason }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium"
                                        :class="item.status === 'Menunggu'
                                            ? 'border-warning-border bg-warning-surface text-warning'
                                            : 'border-success-border bg-success-surface text-success'"
                                    >
                                        {{ item.status }}
                                    </span>
                                    <Button
                                        v-if="item.status === 'Menunggu' && canApproveAdjustment"
                                        variant="ghost"
                                        size="sm"
                                        class="ml-1"
                                        @click="notify(`Penyesuaian ${item.number} disetujui.`)"
                                    >
                                        Setujui
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>

        <!-- Dialog: transfer gudang -->
        <Dialog
            v-model="showTransferDialog"
            title="Buat Transfer Antar Gudang"
            description="Pindahkan barang dari satu gudang ke gudang lain."
            :icon="ArrowLeftRight"
            wide
        >
            <form class="space-y-4" @submit.prevent="submitTransfer">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="transfer-from" required>Gudang Asal (Sumber)</Label>
                        <Select id="transfer-from" v-model="transferForm.from" name="from">
                            <option v-for="warehouse in warehouseOptions" :key="warehouse" :value="warehouse">
                                {{ warehouse }}
                            </option>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label for="transfer-to" required>Gudang Tujuan</Label>
                        <Select id="transfer-to" v-model="transferForm.to" name="to">
                            <option v-for="warehouse in warehouseOptions" :key="warehouse" :value="warehouse">
                                {{ warehouse }}
                            </option>
                        </Select>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="transfer-sku" required>Pilih Item / SKU</Label>
                        <Select id="transfer-sku" v-model="transferForm.code" name="code">
                            <option v-for="option in skuOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label for="transfer-qty" required>Jumlah Transfer</Label>
                        <Input
                            id="transfer-qty"
                            v-model="transferForm.qty"
                            name="qty"
                            type="number"
                            min="1"
                            class="tabular-nums"
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="transfer-note">Nomor Kendaraan / Catatan Logistik</Label>
                    <Input
                        id="transfer-note"
                        v-model="transferForm.note"
                        name="note"
                        placeholder="Contoh: B 9921 UZX — Supir: Pak Bambang"
                    />
                </div>

                <p
                    v-if="transferForm.from === transferForm.to"
                    class="text-sm text-destructive"
                    role="alert"
                >
                    Gudang asal dan tujuan tidak boleh sama.
                </p>

                <div class="flex items-center justify-end gap-2 border-t border-border/60 pt-4">
                    <Button type="button" variant="outline" @click="showTransferDialog = false">Batal</Button>
                    <Button type="submit" :disabled="! transferIsValid">Kirim Perintah Transfer</Button>
                </div>
            </form>
        </Dialog>

        <!-- Dialog: penyesuaian stok -->
        <Dialog
            v-model="showOpnameDialog"
            title="Form Penyesuaian (Opname)"
            description="Cocokkan hasil hitung fisik dengan stok tercatat."
            :icon="ClipboardCheck"
        >
            <form class="space-y-4" @submit.prevent="submitOpname">
                <div class="space-y-2">
                    <Label for="opname-sku" required>SKU Barang Terpilih</Label>
                    <Select id="opname-sku" v-model="opnameForm.code" name="code">
                        <option v-for="option in skuOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </Select>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="opname-system">Stok Sistem Tercatat</Label>
                        <Input id="opname-system" :model-value="selectedSystemStock" disabled class="tabular-nums" />
                    </div>

                    <div class="space-y-2">
                        <Label for="opname-counted" required>Hasil Hitung Fisik</Label>
                        <Input
                            id="opname-counted"
                            v-model="opnameForm.counted"
                            name="counted"
                            type="number"
                            min="0"
                            placeholder="0"
                            class="tabular-nums"
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="opname-reason" required>Alasan Penyesuaian</Label>
                    <textarea
                        id="opname-reason"
                        v-model="opnameForm.reason"
                        name="reason"
                        rows="3"
                        placeholder="Contoh: Kerusakan kemasan saat pemindahan rak B-04…"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    />
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-border/60 pt-4">
                    <Button type="button" variant="outline" @click="showOpnameDialog = false">Batal</Button>
                    <Button type="submit" :disabled="! opnameIsValid">Simpan Opname</Button>
                </div>
            </form>
        </Dialog>

        <!-- Toast -->
        <div
            v-if="toast"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-lg border border-success-border bg-success-surface px-4 py-3 text-sm font-medium text-success shadow-lg"
            role="status"
        >
            <CircleCheck class="h-4 w-4" />
            <span>{{ toast }}</span>
        </div>
    </div>
</template>
