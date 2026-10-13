<script setup>
import { computed, ref } from 'vue';
import {
    AlertTriangle,
    ArrowRight,
    Building2,
    Package,
    Printer,
    RefreshCw,
    ShoppingCart,
    TrendingUp,
    Wallet,
} from 'lucide-vue-next';
import Button from '../components/ui/Button.vue';
import Card from '../components/ui/Card.vue';
import { formatCurrency, formatNumber } from '../lib/format';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const refreshing = ref(false);

/**
 * Data contoh untuk dashboard.
 *
 * TODO(M2–M7): ganti dengan GET /api/dashboard/* begitu modul Master Data,
 * Inventory, Purchasing, Sales, Finance dan Manufacturing tersedia.
 * Struktur baris di bawah ini sudah mengikuti bentuk respon yang diharapkan.
 */
const revenue = 842_500_000;
const openOrders = { total: 28, pending: 4 };
const criticalStock = { skus: 6 };
const payables = 145_200_000;
const receivables = 320_800_000;

const salesTrend = [
    { month: 'Jan', value: 520_000_000 },
    { month: 'Feb', value: 580_000_000 },
    { month: 'Mar', value: 610_000_000 },
    { month: 'Apr', value: 690_000_000 },
    { month: 'Mei', value: 640_000_000 },
    { month: 'Jun', value: 720_000_000 },
    { month: 'Jul', value: 750_000_000 },
    { month: 'Agu', value: 710_000_000 },
    { month: 'Sep', value: 790_000_000 },
    { month: 'Okt', value: 810_000_000 },
    { month: 'Nov', value: 835_000_000 },
    { month: 'Des', value: 842_500_000, active: true },
];

const receipts = {
    rate: 88,
    target: 95,
    inflowUnits: 1420,
    pendingQc: 12,
    breakdown: [
        { label: 'Bahan Baku', value: 92 },
        { label: 'Komponen Rakitan', value: 84 },
        { label: 'Barang Jadi', value: 76 },
    ],
};

const recentPurchaseOrders = [
    { number: 'PO-2024-0891', supplier: 'PT Sinar Mandiri', date: '2024-12-14', total: 48_500_000, status: 'Disetujui' },
    { number: 'PO-2024-0890', supplier: 'CV Baja Perkasa', date: '2024-12-13', total: 12_800_000, status: 'Draft' },
    { number: 'PO-2024-0889', supplier: 'PT Aneka Plastik', date: '2024-12-12', total: 34_200_000, status: 'Disetujui' },
    { number: 'PO-2024-0888', supplier: 'Indo Kimia Raya', date: '2024-12-11', total: 65_000_000, status: 'Disetujui' },
    { number: 'PO-2024-0887', supplier: 'Global Paper Utama', date: '2024-12-10', total: 8_900_000, status: 'Draft' },
];

const overdueInvoices = [
    { number: 'INV-24-1044', customer: 'PT Mega Sejahtera', dueDate: '2024-12-05', remaining: 54_000_000 },
    { number: 'INV-24-1039', customer: 'CV Maju Sentosa', dueDate: '2024-12-08', remaining: 22_350_000 },
    { number: 'INV-24-1025', customer: 'Toko Rezeki Abadi', dueDate: '2024-12-10', remaining: 16_700_000 },
    { number: 'INV-24-1018', customer: 'PT Surya Mas Teknik', dueDate: '2024-12-12', remaining: 41_500_000 },
    { number: 'INV-24-1002', customer: 'CV Berkah Jaya', dueDate: '2024-12-13', remaining: 10_650_000 },
];

const currency = computed(() => auth.company?.currency ?? 'IDR');

const totalSales = salesTrend.reduce((sum, item) => sum + item.value, 0);
const averageGrowth = salesTrend.reduce((sum, item, index) => {
    if (index === 0) {
        return sum;
    }

    return sum + (item.value - salesTrend[index - 1].value) / salesTrend[index - 1].value;
}, 0) / (salesTrend.length - 1);

const maxSales = Math.max(...salesTrend.map((item) => item.value));

const receivableShare = Math.round((receivables / (receivables + payables)) * 1000) / 10;
const payableShare = Math.round(1000 - receivableShare * 10) / 10;

const donutCircumference = 2 * Math.PI * 42;
const donutOffset = donutCircumference * (1 - receipts.rate / 100);

const purchaseOrderTotal = recentPurchaseOrders.reduce((sum, item) => sum + item.total, 0);
const overdueTotal = overdueInvoices.reduce((sum, item) => sum + item.remaining, 0);
const overdueDebtors = new Set(overdueInvoices.map((item) => item.customer)).size;

const statusTone = {
    Disetujui: 'border-success-border bg-success-surface text-success',
    Draft: 'border-border bg-muted text-muted-foreground',
};

const kpis = computed(() => [
    {
        label: 'Pendapatan Bulan Ini',
        icon: Wallet,
        tone: 'bg-muted text-foreground',
        value: formatCurrency(revenue, { currency: currency.value }),
        unit: null,
        badge: { label: '+12.4% vs bln lalu', icon: TrendingUp, tone: 'border-success-border bg-success-surface text-success' },
    },
    {
        label: 'Pesanan Terbuka',
        icon: ShoppingCart,
        tone: 'bg-muted text-foreground',
        value: formatNumber(openOrders.total),
        unit: 'Pesanan',
        badge: { label: `${openOrders.pending} menunggu konfirmasi`, icon: null, tone: 'border-warning-border bg-warning-surface text-warning' },
    },
    {
        label: 'Stok Kritis',
        icon: Package,
        tone: 'border border-danger-border bg-danger-surface text-danger',
        value: formatNumber(criticalStock.skus),
        unit: 'SKU Item',
        badge: { label: 'Segera restock', icon: AlertTriangle, tone: 'border-danger-border bg-danger-surface text-danger' },
    },
]);

function money(value) {
    return formatCurrency(value, { currency: currency.value });
}

function compactMoney(value) {
    if (value >= 1_000_000_000) {
        return `Rp ${(value / 1_000_000_000).toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })} M`;
    }

    return `Rp ${Math.round(value / 1_000_000)} Jt`;
}

function refresh() {
    if (refreshing.value) {
        return;
    }

    refreshing.value = true;
    setTimeout(() => {
        refreshing.value = false;
    }, 800);
}

function printReport() {
    window.print();
}
</script>

<template>
    <div class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6">
        <div class="space-y-6">
            <!-- Page header -->
            <div class="flex flex-col gap-4 border-b border-border/60 pb-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-success" />
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Live Console</span>
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground">Dashboard Operasional</h1>
                    <p class="text-sm text-muted-foreground">
                        Ringkasan performa finansial, logistik, dan transaksi hari ini.
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Data contoh — angka akan tersambung ke API saat modul Master Data, Inventory,
                        Purchasing, Sales, dan Finance dirilis.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Button variant="outline" @click="refresh">
                        <RefreshCw class="h-4 w-4 text-muted-foreground transition-transform duration-500" :class="refreshing && 'animate-spin'" />
                        Refresh Data
                    </Button>
                    <Button @click="printReport">
                        <Printer class="h-4 w-4" />
                        Cetak Laporan
                    </Button>
                </div>
            </div>

            <!-- KPI cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card
                    v-for="item in kpis"
                    :key="item.label"
                    class="p-5 transition-shadow hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-sm text-muted-foreground">{{ item.label }}</span>
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg" :class="item.tone">
                            <component :is="item.icon" class="h-4 w-4" />
                        </span>
                    </div>

                    <div class="mt-4">
                        <p class="text-2xl font-semibold tabular-nums tracking-tight text-foreground">
                            {{ item.value }}
                            <span v-if="item.unit" class="text-sm font-normal text-muted-foreground">{{ item.unit }}</span>
                        </p>
                        <div class="mt-2 flex items-center gap-1.5">
                            <span
                                class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium"
                                :class="item.badge.tone"
                            >
                                <component :is="item.badge.icon" v-if="item.badge.icon" class="h-3.5 w-3.5" />
                                {{ item.badge.label }}
                            </span>
                        </div>
                    </div>
                </Card>

                <!-- KPI: AP / AR -->
                <Card class="p-5 transition-shadow hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-sm text-muted-foreground">Hutang / Piutang</span>
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-muted text-foreground">
                            <Building2 class="h-4 w-4" />
                        </span>
                    </div>

                    <div class="mt-3 flex flex-col gap-1.5">
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="text-muted-foreground">Hutang (AP):</span>
                            <span class="font-semibold tabular-nums text-danger">{{ money(payables) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="text-muted-foreground">Piutang (AR):</span>
                            <span class="font-semibold tabular-nums text-success">{{ money(receivables) }}</span>
                        </div>
                        <div class="mt-1 flex h-1.5 w-full overflow-hidden rounded-full bg-muted">
                            <div class="h-full bg-danger/70" :style="{ width: `${payableShare}%` }" />
                            <div class="h-full bg-success" :style="{ width: `${receivableShare}%` }" />
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <Card class="p-6 lg:col-span-2">
                    <div class="flex flex-col gap-2 border-b border-border/60 pb-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-lg font-medium text-foreground">Tren Penjualan 12 Bulan</h2>
                            <p class="text-xs text-muted-foreground">
                                Realisasi pergerakan omzet kumulatif Januari – Desember 2024
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                                <span class="h-2.5 w-2.5 rounded-full bg-primary" />
                                Volume Penjualan
                            </span>
                            <span class="rounded-md bg-muted px-2.5 py-1 text-xs font-semibold tabular-nums text-foreground">
                                Total: {{ compactMoney(totalSales) }}
                            </span>
                        </div>
                    </div>

                    <div class="pt-8">
                        <div class="flex h-44 items-end gap-1.5 sm:gap-2">
                            <div
                                v-for="item in salesTrend"
                                :key="item.month"
                                class="group relative flex h-full flex-1 items-end"
                            >
                                <span
                                    class="pointer-events-none absolute inset-x-0 -top-5 whitespace-nowrap text-center text-[10px] font-semibold tabular-nums text-foreground opacity-0 transition-opacity group-hover:opacity-100"
                                >
                                    {{ compactMoney(item.value) }}
                                </span>
                                <div
                                    class="w-full rounded-t-sm transition-colors"
                                    :class="item.active
                                        ? 'bg-primary shadow-sm'
                                        : 'bg-muted group-hover:bg-primary/40'"
                                    :style="{ height: `${Math.max(8, (item.value / maxSales) * 100)}%` }"
                                />
                            </div>
                        </div>

                        <div class="mt-2 flex gap-1.5 sm:gap-2">
                            <span
                                v-for="item in salesTrend"
                                :key="`${item.month}-label`"
                                class="flex-1 text-center text-[11px]"
                                :class="item.active ? 'font-semibold text-foreground' : 'text-muted-foreground'"
                            >
                                {{ item.month }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-4 border-t border-border/60 pt-4 text-xs text-muted-foreground">
                        <span>* Pembaruan data otomatis per penutupan buku harian (EOD).</span>
                        <span class="tabular-nums">
                            Avg Growth: {{ averageGrowth >= 0 ? '+' : '' }}{{ (averageGrowth * 100).toFixed(1) }}% MoM
                        </span>
                    </div>
                </Card>

                <Card class="p-6">
                    <div class="border-b border-border/60 pb-4">
                        <h2 class="text-lg font-medium text-foreground">Distribusi Penerimaan Barang</h2>
                        <p class="text-xs text-muted-foreground">Fulfillment gudang utama vs target SLA bulanan</p>
                    </div>

                    <div class="flex items-center justify-center py-5">
                        <div class="relative flex h-36 w-36 items-center justify-center">
                            <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90">
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="42"
                                    fill="transparent"
                                    stroke="currentColor"
                                    stroke-width="9"
                                    class="text-muted"
                                />
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="42"
                                    fill="transparent"
                                    stroke="currentColor"
                                    stroke-width="9"
                                    stroke-linecap="round"
                                    class="text-primary"
                                    :stroke-dasharray="donutCircumference"
                                    :stroke-dashoffset="donutOffset"
                                />
                            </svg>
                            <div class="absolute flex flex-col items-center">
                                <span class="text-3xl font-bold tabular-nums text-foreground">{{ receipts.rate }}%</span>
                                <span class="text-[11px] font-medium text-muted-foreground">Target {{ receipts.target }}%</span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3.5">
                        <div v-for="item in receipts.breakdown" :key="item.label">
                            <div class="mb-1 flex items-center justify-between gap-3 text-xs">
                                <span class="text-foreground">{{ item.label }}</span>
                                <span class="font-semibold tabular-nums text-foreground">{{ item.value }}%</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                <div class="h-full rounded-full bg-primary" :style="{ width: `${item.value}%` }" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-3 border-t border-border/60 pt-4 text-xs text-muted-foreground">
                        <span class="tabular-nums">Inflow total: {{ formatNumber(receipts.inflowUnits) }} Unit</span>
                        <span class="font-medium tabular-nums text-danger">{{ receipts.pendingQc }} Unit Pending QC</span>
                    </div>
                </Card>
            </div>

            <!-- Summary tables -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Card class="overflow-hidden">
                    <div class="flex items-center justify-between gap-3 border-b border-border/60 p-5">
                        <div>
                            <h3 class="text-sm font-semibold text-foreground">Purchase Order Terbaru</h3>
                            <p class="text-xs text-muted-foreground">Transaksi pengadaan perbekalan aktif</p>
                        </div>
                        <a href="#" class="inline-flex items-center gap-1 text-xs font-semibold text-foreground hover:underline">
                            Semua PO <ArrowRight class="h-3.5 w-3.5" />
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[520px] border-collapse text-left">
                            <thead>
                                <tr class="bg-muted text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                                    <th class="px-4 py-3">No PO</th>
                                    <th class="px-4 py-3">Pemasok</th>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3 text-right">Total</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/60 text-xs">
                                <tr
                                    v-for="item in recentPurchaseOrders"
                                    :key="item.number"
                                    class="cursor-pointer transition-colors hover:bg-accent/60"
                                >
                                    <td class="px-4 py-3 font-semibold tabular-nums text-foreground">{{ item.number }}</td>
                                    <td class="max-w-[130px] truncate px-4 py-3 text-foreground">{{ item.supplier }}</td>
                                    <td class="px-4 py-3 tabular-nums text-muted-foreground">{{ item.date }}</td>
                                    <td class="px-4 py-3 text-right font-medium tabular-nums text-foreground">{{ money(item.total) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium"
                                            :class="statusTone[item.status]"
                                        >
                                            {{ item.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-border/60 bg-muted/60 px-4 py-3 text-xs text-muted-foreground">
                        <span class="tabular-nums">Total Terbit Hari Ini: {{ recentPurchaseOrders.length }} Dokumen</span>
                        <span class="font-semibold tabular-nums text-foreground">Total: {{ money(purchaseOrderTotal) }}</span>
                    </div>
                </Card>

                <Card class="overflow-hidden">
                    <div class="flex items-center justify-between gap-3 border-b border-border/60 p-5">
                        <div>
                            <h3 class="text-sm font-semibold text-foreground">Faktur Jatuh Tempo</h3>
                            <p class="text-xs text-muted-foreground">Daftar tagihan pelanggan yang melewati tanggal batas</p>
                        </div>
                        <a href="#" class="inline-flex items-center gap-1 text-xs font-semibold text-danger hover:underline">
                            Rekonsiliasi <ArrowRight class="h-3.5 w-3.5" />
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[520px] border-collapse text-left">
                            <thead>
                                <tr class="bg-muted text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                                    <th class="px-4 py-3">No Faktur</th>
                                    <th class="px-4 py-3">Pelanggan</th>
                                    <th class="px-4 py-3">Jatuh Tempo</th>
                                    <th class="px-4 py-3 text-right">Sisa Tagihan</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/60 text-xs">
                                <tr
                                    v-for="item in overdueInvoices"
                                    :key="item.number"
                                    class="cursor-pointer transition-colors hover:bg-danger-surface/60"
                                >
                                    <td class="px-4 py-3 font-semibold tabular-nums text-foreground">{{ item.number }}</td>
                                    <td class="max-w-[130px] truncate px-4 py-3 text-foreground">{{ item.customer }}</td>
                                    <td class="px-4 py-3 font-medium tabular-nums text-danger">{{ item.dueDate }}</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums text-danger">{{ money(item.remaining) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center rounded-full border border-danger-border bg-danger-surface px-2 py-0.5 text-[11px] font-semibold text-danger">
                                            Jatuh Tempo
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-danger-border bg-danger-surface/70 px-4 py-3 text-xs text-danger">
                        <span class="font-medium tabular-nums">Perlu Tindak Lanjut: {{ overdueDebtors }} Debitur</span>
                        <span class="font-bold tabular-nums">Total Overdue: {{ money(overdueTotal) }}</span>
                    </div>
                </Card>
            </div>
        </div>
    </div>
</template>
