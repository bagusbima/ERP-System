<script setup>
import { computed, reactive, ref } from 'vue';
import {
    BadgeCheck,
    CalendarDays,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Eye,
    FileDown,
    Filter,
    HelpCircle,
    History,
    Lock,
    PackageCheck,
    Plus,
    Printer,
    ReceiptText,
    RotateCcw,
    Search,
    Warehouse,
} from 'lucide-vue-next';
import Button from '../components/ui/Button.vue';
import Card from '../components/ui/Card.vue';
import EmptyState from '../components/EmptyState.vue';
import Input from '../components/ui/Input.vue';
import Select from '../components/ui/Select.vue';
import { formatCurrency, formatNumber } from '../lib/format';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();

const PPN_RATE = 0.11;

/**
 * Data contoh modul Purchasing.
 *
 * TODO(M5): ganti dengan GET /api/purchase-orders/* — subtotal, PPN, dan total
 * di bawah ini dihitung dari baris item, bukan angka mati.
 */
const purchaseOrders = [
    {
        number: 'PO-2025-0482',
        ref: 'REQ-2025-0819',
        createdBy: 'Procurement Ops',
        status: 'Disetujui',
        supplier: 'PT Steelindo Perkasa',
        supplierAddress: 'Jl. Industri No. 12, Cikarang Industrial Estate, Bekasi 17530',
        date: '18 Mei 2025',
        createdAt: '2025-05-18',
        terms: 'Net 30 Hari',
        dueDate: '17 Jun 2025',
        warehouse: 'Gudang Bahan Baku Cakung',
        warehouseDetail: 'Seksi A-04 • Dock 02',
        listWarehouse: 'Gudang Cakung',
        shippingTerm: 'Franco Cakung',
        pic: 'Budi Hermanto (0812-9988-1234)',
        shipping: 500000,
        notes: 'Pengiriman bertahap dimulai 22 Mei 2025. Wajib menyertakan sertifikat uji tarik material (Mill Certificate) saat pengiriman fisik ke gudang Cakung. Inspeksi mutu awal dilakukan oleh tim QC pabrik sebelum penandatanganan Surat Jalan.',
        items: [
            { sku: 'STL-CRB-1045-12', name: 'Pelat Baja Karbon AISI 1045 (12mm x 1200mm)', qty: 20, unit: 'Lembar', price: 1800000, discount: 0 },
            { sku: 'STL-DFM-D16-12M', name: 'Besi Beton Ulir D16 SNI 2052:2017', qty: 100, unit: 'Batang', price: 210000, discount: 0 },
            { sku: 'STL-HBM-150-STD', name: 'H-Beam Struktural 150 x 150 x 7 x 10', qty: 2, unit: 'Batang', price: 3500000, discount: 0 },
        ],
    },
    {
        number: 'PO-2025-0481',
        ref: 'REQ-2025-0816',
        createdBy: 'Procurement Ops',
        status: 'Diterima',
        supplier: 'CV Mitra Tehnik Mandiri',
        supplierAddress: 'Jl. Raya Industri No. 7, Cikarang, Bekasi 17530',
        date: '17 Mei 2025',
        createdAt: '2025-05-17',
        terms: 'Net 14 Hari',
        dueDate: '31 Mei 2025',
        warehouse: 'Gudang Sparepart Cikarang',
        warehouseDetail: 'Rak C-11 • Dock 01',
        listWarehouse: 'Gudang Sparepart Cikarang',
        shippingTerm: 'Franco Cikarang',
        pic: 'Surya Aditya (0813-7711-9080)',
        shipping: 516000,
        notes: 'Barang diterima sebagian pada 20 Mei 2025, sisanya menyusul sesuai konfirmasi pemasok.',
        items: [
            { sku: 'SPR-SEAL-042', name: 'Mechanical Seal 42mm Carbon-SiC', qty: 100, unit: 'Unit', price: 110000, discount: 0 },
            { sku: 'SPR-COUP-15A', name: 'Kopling Elastis 15A B Type', qty: 10, unit: 'Unit', price: 140000, discount: 0 },
        ],
    },
    {
        number: 'PO-2025-0480',
        ref: 'REQ-2025-0812',
        createdBy: 'Chemical Buyer',
        status: 'Diterima',
        supplier: 'PT Duta Kimia Nusantara',
        supplierAddress: 'Jl. Rungkut Industri III, Surabaya 60293',
        date: '16 Mei 2025',
        createdAt: '2025-05-16',
        terms: 'Net 30 Hari',
        dueDate: '15 Jun 2025',
        warehouse: 'Gudang Bahan Kimia Gresik',
        warehouseDetail: 'Gudang Kimia B • Ventilasi Mekanis',
        listWarehouse: 'Gudang Bahan Kimia Gresik',
        shippingTerm: 'FOB Gresik',
        pic: 'Rina Kusuma (0822-4455-1122)',
        shipping: 800000,
        notes: 'Wajib melampirkan MSDS dan sertifikat kadar bahan aktif untuk setiap palet yang dikirim.',
        items: [
            { sku: 'KIM-RESN-EPOX', name: 'Resin Epoksi Keras Type E44 (Pail 25kg)', qty: 50, unit: 'Pail', price: 1850000, discount: 0 },
            { sku: 'KIM-HARD-N90', name: 'Hardener Alifatik N90 (Pail 20kg)', qty: 50, unit: 'Pail', price: 450000, discount: 0 },
        ],
    },
    {
        number: 'PO-2025-0479',
        ref: 'REQ-2025-0808',
        createdBy: 'Procurement Ops',
        status: 'Diterima',
        supplier: 'PT Indo Perkasa Fastener',
        supplierAddress: 'Kawasan Industri MM2100, Cikarang, Bekasi 17530',
        date: '15 Mei 2025',
        createdAt: '2025-05-15',
        terms: 'COD',
        dueDate: '15 Mei 2025',
        warehouse: 'Gudang Bahan Baku Cakung',
        warehouseDetail: 'Seksi A-01 • Dock 02',
        listWarehouse: 'Gudang Cakung',
        shippingTerm: 'Franco Cakung',
        pic: 'Budi Hermanto (0812-9988-1234)',
        shipping: 40000,
        notes: 'Pengambilan sendiri menggunakan armada perusahaan.',
        items: [
            { sku: 'FST-BLT-M8-040', name: 'Baut Baja Karbon M8 x 40 Grade 8.8', qty: 10000, unit: 'Pcs', price: 800, discount: 0 },
        ],
    },
    {
        number: 'PO-2025-0478',
        ref: 'REQ-2025-0804',
        createdBy: 'Metal Buyer',
        status: 'Disetujui',
        supplier: 'PT Logam Utama Semesta',
        supplierAddress: 'Jl. Industri Selatan V, Karawang 41361',
        date: '14 Mei 2025',
        createdAt: '2025-05-14',
        terms: 'Net 45 Hari',
        dueDate: '28 Jun 2025',
        warehouse: 'Gudang Logistik Karawang',
        warehouseDetail: 'Bay 07 • Rak D-02',
        listWarehouse: 'Gudang Logistik Karawang',
        shippingTerm: 'Franco Karawang',
        pic: 'Hendra Wijaya (0817-2200-3311)',
        shipping: 1080000,
        notes: 'Pengiriman dijadwalkan bertahap dua termin, masing-masing 50% dari total volume.',
        items: [
            { sku: 'LMG-PLT-AL5', name: 'Plat Aluminium 5052 (1200x2400x3mm)', qty: 20, unit: 'Lembar', price: 3500000, discount: 0 },
            { sku: 'LMG-COIL-CU1', name: 'Kawat Tembaga Bulat 1.0mm (Coil 25kg)', qty: 4, unit: 'Coil', price: 3000000, discount: 0 },
        ],
    },
    {
        number: 'PO-2025-0477',
        ref: 'REQ-2025-0799',
        createdBy: 'Maintenance Lead',
        status: 'Draft',
        supplier: 'CV Sumber Makmur Tehnik',
        supplierAddress: 'Jl. Otomotif No. 22, Cikarang, Bekasi 17530',
        date: '12 Mei 2025',
        createdAt: '2025-05-12',
        terms: 'Net 7 Hari',
        dueDate: '19 Mei 2025',
        warehouse: 'Gudang Sparepart Cikarang',
        warehouseDetail: 'Rak B-08 • Dock 01',
        listWarehouse: 'Gudang Sparepart Cikarang',
        shippingTerm: 'Franco Cikarang',
        pic: 'Surya Aditya (0813-7711-9080)',
        shipping: 120000,
        notes: 'Menunggu persetujuan kepala bagian pemeliharaan sebelum dikirim ke pemasok.',
        items: [
            { sku: 'SPR-COOL-GLY', name: 'Coolant Glycol Concentrate (Kanister 5L)', qty: 30, unit: 'Kanister', price: 100000, discount: 0 },
        ],
    },
    {
        number: 'PO-2025-0476',
        ref: 'REQ-2025-0795',
        createdBy: 'Electrical Buyer',
        status: 'Disetujui',
        supplier: 'PT Global Solusi Elektrikal',
        supplierAddress: 'Jl. Gading Kirana Raya, Jakarta Utara 14240',
        date: '10 Mei 2025',
        createdAt: '2025-05-10',
        terms: 'Net 30 Hari',
        dueDate: '09 Jun 2025',
        warehouse: 'Gudang Bahan Baku Cakung',
        warehouseDetail: 'Seksi E-02 • Dock 03',
        listWarehouse: 'Gudang Cakung',
        shippingTerm: 'Franco Cakung',
        pic: 'Yuni Astari (0812-3344-5566)',
        shipping: 90000,
        notes: 'Sertifikat SNI kabel wajib disertakan bersama surat jalan.',
        items: [
            { sku: 'ELC-CBL-NYYM4', name: 'Kabel NYM 4x4mm2 (Rol 100m)', qty: 50, unit: 'Rol', price: 750000, discount: 0 },
            { sku: 'ELC-MCB-3P63', name: 'Miniature Circuit Breaker 3P 63A', qty: 5, unit: 'Unit', price: 700000, discount: 0 },
        ],
    },
    {
        number: 'PO-2025-0475',
        ref: 'REQ-2025-0790',
        createdBy: 'Lubricant Buyer',
        status: 'Dibatalkan',
        supplier: 'PT Sentosa Pelumas Otomotif',
        supplierAddress: 'Jl. Industri Kimia No. 4, Gresik 61122',
        date: '08 Mei 2025',
        createdAt: '2025-05-08',
        terms: 'Net 14 Hari',
        dueDate: '22 Mei 2025',
        warehouse: 'Gudang Bahan Kimia Gresik',
        warehouseDetail: 'Gudang Kimia A • Rak K-03',
        listWarehouse: 'Gudang Bahan Kimia Gresik',
        shippingTerm: 'FOB Gresik',
        pic: 'Rina Kusuma (0822-4455-1122)',
        shipping: 283000,
        notes: 'Dibatalkan karena pemasok tidak dapat memenuhi volume pada tenggat yang disepakati.',
        items: [
            { sku: 'LUB-OLI-SAE40', name: 'Pelumas Mesin SAE 40 (Jerigen 20L)', qty: 100, unit: 'Jerigen', price: 197000, discount: 0 },
        ],
    },
    {
        number: 'PO-2025-0474',
        ref: 'REQ-2025-0786',
        createdBy: 'Maintenance Lead',
        status: 'Diterima',
        supplier: 'CV Cahaya Bintang Tehnik',
        supplierAddress: 'Jl. Raya Cikarang KM 29, Bekasi 17530',
        date: '05 Mei 2025',
        createdAt: '2025-05-05',
        terms: 'COD',
        dueDate: '05 Mei 2025',
        warehouse: 'Gudang Sparepart Cikarang',
        warehouseDetail: 'Rak A-05 • Dock 01',
        listWarehouse: 'Gudang Sparepart Cikarang',
        shippingTerm: 'Franco Cikarang',
        pic: 'Surya Aditya (0813-7711-9080)',
        shipping: 140000,
        notes: 'Pengiriman langsung ke area bengkel pemeliharaan.',
        items: [
            { sku: 'SPR-FLT-025', name: 'Filter Oli Spin-On 25 Micron', qty: 60, unit: 'Unit', price: 100000, discount: 0 },
        ],
    },
    {
        number: 'PO-2025-0473',
        ref: 'REQ-2025-0781',
        createdBy: 'Metal Buyer',
        status: 'Diterima',
        supplier: 'PT Berkat Baja Sejahtera',
        supplierAddress: 'Jl. Kali Raya Terban, Cakung, Jakarta Timur 13920',
        date: '03 Mei 2025',
        createdAt: '2025-05-03',
        terms: 'Net 30 Hari',
        dueDate: '02 Jun 2025',
        warehouse: 'Gudang Bahan Baku Cakung',
        warehouseDetail: 'Seksi A-04 • Dock 02',
        listWarehouse: 'Gudang Cakung',
        shippingTerm: 'Franco Cakung',
        pic: 'Budi Hermanto (0812-9988-1234)',
        shipping: 1050000,
        notes: 'Seluruh barang sudah diterima dan lolos inspeksi dimensi pada 07 Mei 2025.',
        items: [
            { sku: 'STL-PIP-PSP3', name: 'Pipa Stainless SP 3" Sch40 (6m)', qty: 150, unit: 'Batang', price: 500000, discount: 0 },
        ],
    },
];

/** Total arsip yang dipaginasi — placeholder, dihitung dari seluruh buku PO. */
const archivedTotal = 42;

const periods = [
    { value: '2025-05', label: '01 Mei 2025 - 31 Mei 2025' },
    { value: '2025-04', label: '01 Apr 2025 - 30 Apr 2025' },
    { value: '2025-06', label: '01 Jun 2025 - 30 Jun 2025' },
];

const statusOptions = ['Semua Status', 'Draft', 'Disetujui', 'Diterima', 'Dibatalkan'];

const sortOptions = [
    { value: 'date-desc', label: 'Tanggal Pembuatan (Terbaru)' },
    { value: 'total-desc', label: 'Nominal Tertinggi' },
    { value: 'status', label: 'Status Approval' },
];

const statusStyles = {
    Draft: 'border-border bg-muted text-muted-foreground',
    Disetujui: 'border-border bg-accent text-foreground',
    Diterima: 'border-success-border bg-success-surface text-success',
    Dibatalkan: 'border-danger-border bg-danger-surface text-danger',
};

const statusDots = {
    Draft: 'bg-muted-foreground/60',
    Disetujui: 'bg-primary',
    Diterima: 'bg-success',
    Dibatalkan: 'bg-destructive',
};

const statusOrder = { Draft: 0, Disetujui: 1, Diterima: 2, Dibatalkan: 3 };

const filters = reactive({
    search: '',
    status: 'Semua Status',
    period: '2025-05',
    sort: 'date-desc',
});

const selected = ref(purchaseOrders[0]);
const toast = ref('');
let toastTimer = null;

const canCreate = computed(() => auth.can('purchasing.create'));
const canApprove = computed(() => auth.can('purchasing.approve'));
const canReceive = computed(() => auth.can('purchasing.update'));
const canExport = computed(() => auth.can('report.export'));

const filteredOrders = computed(() => {
    const query = filters.search.toLowerCase().trim();

    const rows = purchaseOrders.filter((order) => {
        const matchesSearch = `${order.number} ${order.supplier}`.toLowerCase().includes(query);
        const matchesStatus = filters.status === 'Semua Status' || order.status === filters.status;
        const matchesPeriod = filters.period === '' || order.createdAt.startsWith(filters.period);

        return matchesSearch && matchesStatus && matchesPeriod;
    });

    return rows.sort(compareOrders);
});

const hasActiveFilters = computed(() => filters.search !== ''
    || filters.status !== 'Semua Status'
    || filters.period !== '');

/**
 * @param {{createdAt: string, items: Array, shipping: number, status: string}} order
 * @param {{createdAt: string, items: Array, shipping: number, status: string}} other
 * @returns {number}
 */
function compareOrders(order, other) {
    if (filters.sort === 'total-desc') {
        return totalOf(other) - totalOf(order);
    }

    if (filters.sort === 'status') {
        return statusOrder[order.status] - statusOrder[other.status];
    }

    return other.createdAt.localeCompare(order.createdAt);
}

/**
 * @param {{items: Array<{qty: number, price: number, discount: number}>}} order
 * @returns {number}
 */
function subtotalOf(order) {
    return order.items.reduce((sum, item) => {
        const lineTotal = item.qty * item.price * (1 - item.discount / 100);

        return sum + Math.round(lineTotal);
    }, 0);
}

/**
 * @param {{items: Array<{qty: number, price: number, discount: number}>}} order
 * @returns {number}
 */
function ppnOf(order) {
    return Math.round(subtotalOf(order) * PPN_RATE);
}

/**
 * @param {{items: Array<{qty: number, price: number, discount: number}>, shipping: number}} order
 * @returns {number}
 */
function totalOf(order) {
    return subtotalOf(order) + ppnOf(order) + order.shipping;
}

/**
 * @param {{items: Array<{qty: number, price: number, discount: number}>}} order
 * @param {{qty: number, price: number, discount: number}} item
 * @returns {number}
 */
function lineTotalOf(order, item) {
    return Math.round(item.qty * item.price * (1 - item.discount / 100));
}

/**
 * @param {{status: string}} order
 * @returns {boolean}
 */
function isLocked(order) {
    return order.status !== 'Draft';
}

function resetFilters() {
    filters.search = '';
    filters.status = 'Semua Status';
    filters.period = '';
    filters.sort = 'date-desc';
}

/**
 * @param {{number: string}} order
 */
function selectOrder(order) {
    selected.value = order;
    document.getElementById('po-preview')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/**
 * @param {{number: string, status: string}} order
 */
function approveOrder(order) {
    const confirmed = window.confirm(
        `Konfirmasi persetujuan Pesanan Pembelian ${order.number}? Notifikasi otomatis akan diteruskan ke pemasok.`,
    );

    if (! confirmed) {
        return;
    }

    order.status = 'Disetujui';
    notify(`${order.number} disetujui dan diteruskan ke pemasok.`);
}

/**
 * @param {{number: string}} order
 */
function receiveOrder(order) {
    order.status = 'Diterima';
    notify(`Penerimaan barang untuk ${order.number} telah dicatat.`);
}

function createOrder() {
    notify('Formulir PO baru menyusul setelah endpoint pembelian tersambung.');
}

function printOrder() {
    window.print();
}

function exportCsv() {
    notify('Berkas CSV pesanan pembelian sedang disiapkan.');
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
    <div class="mx-auto w-full max-w-6xl space-y-6 px-4 py-8 sm:px-6">
        <!-- Page header -->
        <div class="flex flex-col gap-4 border-b border-border/60 pb-6 md:flex-row md:items-center md:justify-between">
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                    <span class="rounded bg-muted px-2 py-0.5">Modul Pengadaan</span>
                    <span>/</span>
                    <span class="normal-case tracking-normal text-foreground">{{ selected.number }}</span>
                </div>

                <h1 class="text-3xl font-semibold tracking-tight text-foreground">
                    Pesanan Pembelian (Purchase Orders)
                </h1>
                <p class="text-sm text-muted-foreground">
                    Pengadaan barang, persetujuan pesanan pemasok, dan pelacakan status penerimaan.
                </p>
                <p class="text-xs text-muted-foreground">
                    Data contoh — angka akan tersambung ke API Purchasing setelah endpoint pesanan pembelian selesai.
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <Button v-if="canExport" variant="outline" @click="exportCsv">
                    <FileDown class="h-4 w-4" />
                    Ekspor CSV
                </Button>

                <Button v-if="canCreate" @click="createOrder">
                    <Plus class="h-4 w-4" />
                    Buat PO Baru
                </Button>
            </div>
        </div>

        <!-- Filter bar -->
        <Card class="flex flex-col gap-3 p-4 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                <div class="flex flex-1 items-center gap-2">
                    <Search class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                    <Input
                        v-model="filters.search"
                        type="search"
                        aria-label="Cari pesanan pembelian"
                        placeholder="Cari No. PO atau nama pemasok…"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <Filter class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                    <Select v-model="filters.status" aria-label="Saring menurut status" class="sm:w-40">
                        <option v-for="status in statusOptions" :key="status" :value="status">
                            {{ status }}
                        </option>
                    </Select>
                </div>

                <div class="flex items-center gap-2">
                    <CalendarDays class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                    <Select v-model="filters.period" aria-label="Saring menurut periode" class="sm:w-56">
                        <option value="">Semua Tanggal</option>
                        <option v-for="period in periods" :key="period.value" :value="period.value">
                            {{ period.label }}
                        </option>
                    </Select>
                </div>
            </div>

            <div class="flex justify-end">
                <Button
                    variant="ghost"
                    size="sm"
                    title="Reset Filter"
                    :disabled="! hasActiveFilters"
                    @click="resetFilters"
                >
                    <RotateCcw class="h-4 w-4" />
                    Reset Filter
                </Button>
            </div>
        </Card>

        <!-- Active document preview -->
        <Card id="po-preview" class="overflow-hidden">
            <!-- Context bar -->
            <div class="flex flex-col gap-4 border-b border-border/60 bg-muted/50 p-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                        <ReceiptText class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-lg font-semibold text-foreground">{{ selected.number }}</span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium"
                                :class="statusStyles[selected.status]"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusDots[selected.status]" />
                                {{ selected.status }}
                            </span>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            ID Referensi internal: {{ selected.ref }} • Dibuat oleh {{ selected.createdBy }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2">
                    <Button
                        v-if="canApprove && selected.status === 'Draft'"
                        variant="outline"
                        @click="approveOrder(selected)"
                    >
                        <BadgeCheck class="h-4 w-4" />
                        Setujui
                    </Button>

                    <Button v-else-if="selected.status === 'Disetujui'" variant="outline" disabled>
                        <CheckCircle2 class="h-4 w-4" />
                        Sudah Disetujui
                    </Button>

                    <Button variant="outline" @click="printOrder">
                        <Printer class="h-4 w-4" />
                        Cetak PO
                    </Button>

                    <Button
                        v-if="canReceive && selected.status === 'Disetujui'"
                        @click="receiveOrder(selected)"
                    >
                        <PackageCheck class="h-4 w-4" />
                        Terima Barang
                    </Button>
                </div>
            </div>

            <!-- Metadata grid -->
            <div class="grid gap-6 border-b border-border/60 p-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="space-y-1">
                    <p class="text-xs uppercase tracking-wider text-muted-foreground">Pemasok Resmi</p>
                    <p class="text-sm font-semibold text-foreground">{{ selected.supplier }}</p>
                    <p class="text-xs leading-relaxed text-muted-foreground">{{ selected.supplierAddress }}</p>
                </div>

                <div class="space-y-1">
                    <p class="text-xs uppercase tracking-wider text-muted-foreground">Tanggal &amp; Ketentuan</p>
                    <div class="flex items-center gap-2">
                        <CalendarDays class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                        <span class="text-sm font-semibold text-foreground">{{ selected.date }}</span>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Jatuh Tempo: {{ selected.terms }} ({{ selected.dueDate }})
                    </p>
                </div>

                <div class="space-y-1">
                    <p class="text-xs uppercase tracking-wider text-muted-foreground">Gudang Penerima</p>
                    <div class="flex items-center gap-2">
                        <Warehouse class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                        <span class="text-sm font-semibold text-foreground">{{ selected.warehouse }}</span>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ selected.warehouseDetail }}</p>
                </div>

                <div class="space-y-1">
                    <p class="text-xs uppercase tracking-wider text-muted-foreground">Informasi Logistik</p>
                    <p class="text-sm text-foreground">Pengiriman: {{ selected.shippingTerm }}</p>
                    <p class="text-xs text-muted-foreground">Kontak PIC: {{ selected.pic }}</p>
                </div>
            </div>

            <!-- Item lines -->
            <div class="flex flex-col gap-4 p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-foreground">Rincian Barang &amp; Material</h2>
                    <span class="text-xs text-muted-foreground">
                        {{ selected.items.length }} Baris Item Terdaftar
                    </span>
                </div>

                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full min-w-[46rem] border-collapse text-left">
                        <thead>
                            <tr class="bg-muted text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                                <th class="w-12 px-4 py-3 text-center">No</th>
                                <th class="min-w-[16rem] px-4 py-3">Kode &amp; Nama Produk</th>
                                <th class="px-4 py-3 text-right">Qty Pesan</th>
                                <th class="px-4 py-3 text-center">Satuan</th>
                                <th class="px-4 py-3 text-right">Harga Satuan</th>
                                <th class="px-4 py-3 text-right">Diskon (%)</th>
                                <th class="px-4 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 text-sm">
                            <tr
                                v-for="(item, index) in selected.items"
                                :key="item.sku"
                                class="transition-colors hover:bg-accent/60"
                            >
                                <td class="px-4 py-3 text-center text-xs tabular-nums text-muted-foreground">
                                    {{ index + 1 }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-foreground">{{ item.name }}</span>
                                        <span class="text-xs text-muted-foreground">SKU: {{ item.sku }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-foreground">
                                    {{ formatNumber(item.qty) }}
                                </td>
                                <td class="px-4 py-3 text-center text-xs text-muted-foreground">{{ item.unit }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-foreground">
                                    {{ formatCurrency(item.price) }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-muted-foreground">
                                    {{ item.discount }}%
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-foreground">
                                    {{ formatCurrency(lineTotalOf(selected, item)) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Notes + financial summary -->
                <div class="grid gap-4 md:grid-cols-12">
                    <div class="flex flex-col justify-between rounded-lg bg-muted/60 p-4 md:col-span-7">
                        <div>
                            <p class="text-sm font-medium text-foreground">Catatan &amp; Ketentuan Pembelian</p>
                            <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                                {{ selected.notes }}
                            </p>
                        </div>

                        <div class="mt-4 flex items-center gap-2 text-xs text-muted-foreground">
                            <Lock class="h-4 w-4 shrink-0" aria-hidden="true" />
                            <span v-if="isLocked(selected)">
                                Dokumen terkunci secara sistem setelah approval level 2.
                            </span>
                            <span v-else>
                                Dokumen masih berstatus Draft — dapat diubah sebelum approval.
                            </span>
                        </div>
                    </div>

                    <div class="space-y-2 rounded-lg bg-muted/60 p-4 md:col-span-5">
                        <div class="flex items-center justify-between gap-3 text-sm text-muted-foreground">
                            <span>Subtotal Barang:</span>
                            <span class="font-semibold tabular-nums text-foreground">
                                {{ formatCurrency(subtotalOf(selected)) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-3 text-sm text-muted-foreground">
                            <span class="flex items-center gap-1.5">
                                PPN (11%)
                                <HelpCircle
                                    class="h-3.5 w-3.5 cursor-help"
                                    title="Perhitungan PPN resmi UU HPP"
                                />
                            </span>
                            <span class="font-semibold tabular-nums text-foreground">
                                {{ formatCurrency(ppnOf(selected)) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-3 text-sm text-muted-foreground">
                            <span>Biaya Pengiriman &amp; Asuransi:</span>
                            <span class="font-semibold tabular-nums text-foreground">
                                {{ formatCurrency(selected.shipping) }}
                            </span>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-3 rounded-md bg-accent p-3">
                            <span class="text-sm font-medium text-foreground">Total Akhir PO:</span>
                            <span class="text-lg font-semibold tabular-nums text-foreground">
                                {{ formatCurrency(totalOf(selected)) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </Card>

        <!-- Archive list -->
        <Card class="overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-border/60 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2">
                    <History class="h-5 w-5 text-foreground" aria-hidden="true" />
                    <h3 class="text-sm font-semibold text-foreground">Daftar Arsip Pesanan Pembelian</h3>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs text-muted-foreground">Urutkan:</span>
                    <Select v-model="filters.sort" aria-label="Urutkan daftar pesanan" class="w-56">
                        <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </Select>
                </div>
            </div>

            <div v-if="filteredOrders.length > 0" class="overflow-x-auto">
                <table class="w-full min-w-[52rem] border-collapse text-left">
                    <thead>
                        <tr class="bg-muted text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                            <th class="px-4 py-3">No. PO</th>
                            <th class="px-4 py-3">Pemasok</th>
                            <th class="px-4 py-3">Tanggal Pesan</th>
                            <th class="px-4 py-3">Gudang Alokasi</th>
                            <th class="px-4 py-3 text-right">Total Nilai</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60 text-sm">
                        <tr
                            v-for="order in filteredOrders"
                            :key="order.number"
                            class="cursor-pointer transition-colors hover:bg-accent/60"
                            :class="{ 'bg-muted/40': order.number === selected.number }"
                            @click="selectOrder(order)"
                        >
                            <td class="px-4 py-3 font-semibold tabular-nums text-foreground">
                                {{ order.number }}
                                <span
                                    v-if="order.number === selected.number"
                                    class="ml-2 rounded bg-primary px-1.5 py-0.5 text-[10px] font-semibold text-primary-foreground"
                                >
                                    Aktif
                                </span>
                            </td>
                            <td class="px-4 py-3" :class="order.number === selected.number ? 'font-semibold' : ''">
                                {{ order.supplier }}
                            </td>
                            <td class="px-4 py-3 tabular-nums text-muted-foreground">{{ order.date }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ order.listWarehouse }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-foreground">
                                {{ formatCurrency(totalOf(order)) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-xs font-medium"
                                    :class="statusStyles[order.status]"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="statusDots[order.status]" />
                                    {{ order.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    title="Lihat Detail PO"
                                    :aria-label="`Lihat detail ${order.number}`"
                                    @click.stop="selectOrder(order)"
                                >
                                    <Eye class="h-4 w-4" />
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-else
                :icon="Search"
                title="Pesanan Tidak Ditemukan"
                description="Kata kunci, status, atau periode tanggal yang Anda pilih tidak cocok dengan pesanan pembelian mana pun."
                action-label="Pulihkan Semua Filter"
                @action="resetFilters"
            />

            <div
                class="flex flex-col gap-3 border-t border-border/60 bg-muted/40 px-4 py-4 text-xs sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="tabular-nums text-muted-foreground">
                    Menampilkan <span class="font-semibold text-foreground">1-{{ filteredOrders.length }}</span>
                    dari <span class="font-semibold text-foreground">{{ archivedTotal }}</span> PO
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
                        v-for="page in [2, 3, 4, 5]"
                        :key="page"
                        type="button"
                        disabled
                        title="Menyusul setelah data pembelian tersambung ke API."
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-muted text-xs tabular-nums text-muted-foreground transition-colors hover:bg-accent disabled:opacity-40"
                    >
                        {{ page }}
                    </button>
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

        <!-- Toast -->
        <div
            v-if="toast"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-lg border border-success-border bg-success-surface px-4 py-3 text-sm font-medium text-success shadow-lg"
            role="status"
        >
            <CheckCircle2 class="h-4 w-4" />
            <span>{{ toast }}</span>
        </div>
    </div>
</template>
