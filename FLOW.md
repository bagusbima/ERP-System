# Alur Sistem ERP — Navigasi, Bisnis & Data

Dokumen ini memetakan tiga lapis alur aplikasi:

| Bagian | Cakupan | Sumber kebenaran |
| --- | --- | --- |
| **A — Navigasi & User Journey** | Rute, guard, sidebar, kondisi halaman | `resources/js/router/index.js`, `layouts/navigation.js` |
| **B — Alur Bisnis per Modul** | Siklus dokumen & interaksi halaman | `resources/js/pages/*.vue` (data contoh) |
| **C — Alur Data / API** | Autentikasi, middleware, multi-tenant, RBAC | `routes/api.php`, `bootstrap/app.php`, `stores/auth.js` |

Diagram memakai Mermaid. Renderer GitHub/GitLab/VS Code akan menampilkannya otomatis.

> **Status saat ini:** modul **Sales, Finance, Manufacturing** belum punya halaman. Kunci izinnya sudah tersedia (37 total) tetapi belum ada rute maupun endpoint.

---

## 0. Arsitektur Ringkas

```mermaid
flowchart LR
    subgraph Browser
        SPA[Vue 3 SPA<br/>vue-router + Pinia]
        UI[Komponen UI<br/>Tailwind v4 + lucide]
        SPA --> UI
    end

    subgraph Server["Laravel (PHP 8.4)"]
        MW["Middleware<br/>auth:sanctum → company → permission"]
        CNT[Controller API]
        CTX[CompanyContext<br/>scope company_id]
        DB[(Database<br/>shared + company_id)]
        MW --> CNT --> CTX --> DB
    end

    UI -- "fetch Bearer token" --> MW
    CNT -- "envelope JSON" --> UI
```

- **Auth:** Laravel Sanctum bearer token (disimpan di `localStorage`).
- **Multi-tenant:** database bersama + kolom `company_id`; penjaganya `SetCurrentCompany` + global scope yang **menolak secara bawaan** (`where 1 = 0`), dengan pintu keluar `CompanyContext::run()`.
- **RBAC ringan kustom:** 9 modul × aksi = **37 kunci izin**, 4 peran sistem, hanya-baca.

---

## A. Navigasi & User Journey

### A1. Guard router — dijalankan pada **setiap** navigasi

```mermaid
flowchart TD
    START(["router.beforeEach(to)"]) --> REST["auth.restore()<br/>muat /api/auth/me bila ada token"]
    REST --> G{"to.meta.guest?<br/>(hanya /login)"}
    G -- "ya + sudah login" --> DASH["lempar ke /"]
    G -- "ya + belum login" --> PASS1["lolos → tampilkan /login"]
    G -- "tidak" --> AUTH{"auth.isAuthenticated?"}
    AUTH -- "tidak" --> LOGIN["lempar ke /login<br/>?redirect=to.fullPath"]
    AUTH -- "ya" --> COMP{"to.meta.requiresCompany?<br/>dan company aktif kosong?"}
    COMP -- "ya" --> COMPPAGE["lempar ke /companies"]
    COMP -- "tidak" --> PERM{"to.meta.permission?<br/>dan auth.can() = false?"}
    PERM -- "ya" --> HOME["lempar ke /"]
    PERM -- "tidak" --> OK(["render halaman"])
```

**Empat lapis penjaga:** `guest` → `requiresAuth` → `requiresCompany` → `permission`.
Catch-all `/:pathMatch(.*)*` dilempar ke `/`, jadi URL asing tidak pernah memunculkan halaman kosong.

### A2. Peta sidebar

Sumber: `layouts/navigation.js`. Item **tidak dirender** sama sekali bila izinnya tidak dimiliki.

| Grup | Item | Rute | Izin yang menampilkan |
| --- | --- | --- | --- |
| Umum | Dashboard | `/` | — (semua pengguna login) |
| Master Data | Katalog Produk | `/master-data` | `master.view` |
| Operasional | Inventory | `/inventory` | `inventory.view` |
| Operasional | Purchasing | `/purchasing` | `purchasing.view` |
| Pengaturan | Perusahaan | `/companies` | — |
| Pengaturan | Pengguna | `/users` | `users.view` |
| Pengaturan | Peran & Izin | `/roles` | `users.view` |
| — | Sales / Finance / Manufacturing / Laporan | *belum ada* | `sales.*`, `finance.*`, `manufacturing.*`, `report.view` |

### A3. Journey dua tipe pengguna

```mermaid
flowchart TD
    START(["Buka aplikasi"]) --> LOGIN["/login<br/>POST /api/auth/login<br/>throttle 10/menit"]
    LOGIN -- "429 Too Many Requests" --> WAIT["Tunggu 1 menit / flush cache"]
    LOGIN -- "200 OK" --> PICK["pickInitialCompany()<br/>pakai company_id tersimpan<br/>atau perusahaan pertama"]
    PICK --> SESSION["GET /api/auth/me<br/>→ user + company + permissions"]

    SESSION --> Q{"Berapa perusahaan?"}
    Q -- "0 (superadmin)" --> FORM["/companies<br/>form Buat Perusahaan"]
    Q -- "≥ 1 (owner)" --> DASH["/ → Dashboard"]

    FORM -- "POST /api/companies" --> DASH
    DASH --> SWITCH["Ganti perusahaan<br/>select di sidebar"]
    SWITCH -- "X-Company / company_id" --> SESSION
    DASH --> LOGOUT["POST /api/auth/logout<br/>+ $reset()"] --> LOGIN
```

**Akun demo:** `owner@erp.test` / `password` (2 perusahaan → langsung Dashboard) · `superadmin@erp.test` / `password` (0 perusahaan → form buat perusahaan).

### A4. Kondisi yang bisa ditemui di halaman

```mermaid
stateDiagram-v2
    [*] --> Memuat: buka rute
    Memuat --> Terisi: data contoh / API sukses
    Memuat --> Kosong: hasil filter 0 baris
    Kosong --> Terisi: Pulihkan Semua Filter
    Terisi --> Detail: klik baris / tombol mata
    Detail --> Terisi: tutup slide-over / dialog
    Terisi --> Dibatalkan: izin tidak ada (tombol disembunyikan)
    Dibatalkan --> [*]

    note right of Kosong
        EmptyState selalu punya judul,
        deskripsi, dan aksi pemulihan.
    end note
```

- **Tombol aksi** muncul/hilang mengikuti izin (`v-if="canCreate"` dll), bukan hanya jadi `disabled`.
- **Pagination baris 2+** sengaja `disabled` — datanya masih placeholder sampai endpoint terpasang; alasannya dikirim lewat `title`.
- **Toast** (`role="status"`) muncul di kanan-bawah selama 3 detik setelah aksi.

### A5. Shell aplikasi (`AppLayout`)

```mermaid
flowchart LR
    subgraph SIDEBAR["Sidebar (fixed, w-60; drawer di < md)"]
        LOGO[Nama perusahaan + kode ID]
        NAV[Nav bergrup per izin]
        FOOT[Status server + profil + logout]
    end
    subgraph TOPBAR["Topbar (sticky, h-16, backdrop-blur)"]
        CRUMB[Company / Nama halaman]
        TOOLS[Peran · Notifikasi · Pengaturan · Avatar · Logout]
    end
    MAIN["Area konten utama → router-view"]
    SIDEBAR --- MAIN
    TOPBAR --- MAIN
```

---

## B. Alur Bisnis per Modul

### B1. Purchasing — siklus Purchase Order

```mermaid
stateDiagram-v2
    [*] --> Draft
    Draft --> Disetujui: Setujui (purchasing.approve) + confirm()
    Disetujui --> Disetujui: Cetak PO (window.print)
    Draft --> Draft: Cetak PO
    Disetujui --> Diterima: Terima Barang (purchasing.update)
    Draft --> Dibatalkan: dibatalkan pemasok
    Disetujui --> Dibatalkan: dibatalkan pemasok
    Diterima --> [*]
    Dibatalkan --> [*]

    note right of Draft
        Dokumen masih berstatus Draft —
        dapat diubah sebelum approval
    end note
    note right of Disetujui
        Dokumen terkunci secara sistem
        setelah approval level 2.
        Tombol berubah jadi
        Sudah Disetujui (nonaktif).
    end note
```

**Interaksi halaman:**

```mermaid
flowchart TD
    FILTER["Filter bar<br/>cari No.PO/pemasok · status · periode"] --> TABLE["Tabel arsip 10 PO<br/>Urutkan: tanggal / nominal / status"]
    TABLE -- "klik baris atau tombol mata" --> PREVIEW["Kartu dokumen aktif<br/>scroll mulus ke #po-preview"]
    PREVIEW --> META["4 metadata: pemasok · tanggal &<br/>ketentuan · gudang · logistik"]
    PREVIEW --> ITEMS["Tabel item: qty × harga − diskon"]
    PREVIEW --> FIN["Subtotal → PPN 11% →<br/>biaya kirim → Total Akhir PO"]
    PREVIEW --> ACT{"Aksi tersedia"}
    ACT -- "purchasing.create" --> NEW["Buat PO Baru (placeholder)"]
    ACT -- "report.export" --> CSV["Ekspor CSV (placeholder)"]
    ACT -- "purchasing.approve" --> APPROVE["Setujui → toast"]
    ACT -- "purchasing.update" --> RECEIVE["Terima Barang → toast"]
```

Semua angka keuangan **dihitung dari baris item**, bukan angka mati:
`total = subtotal + round(subtotal × 0,11) + biayaKirim`.

### B2. Inventory — empat tab

```mermaid
flowchart TD
    subgraph T1["1 · Posisi Stok Real-Time"]
        F1["Filter: gudang · cari SKU ·<br/>Hanya Stok Kritis"] --> R1["Tabel stok<br/>selisih + chip Kritis/Normal"]
        R1 -- "Buat PO (purchasing.create)" --> POTOAST["toast placeholder"]
        R1 -. "Buat PO (purchasing.update)" .-> WO["Baris kritis →<br/>tombol Buat PO menonjol"]
    end

    subgraph T2["2 · Mutasi & Kartu Stok"]
        LOG["Log masuk / keluar / saldo akhir<br/>per surat jalan, GRN, transfer, opname"]
    end

    subgraph T3["3 · Transfer Antar Gudang"]
        TRF["Tabel transfer: Dikirim / Diterima / Draft"]
        TRFD("Dialog Transfer Gudang")
        TRF --> TRFD
        TRFD -- "asal = tujuan" --> TRFERR["role=alert:<br/>Gudang asal dan tujuan tidak boleh sama"]
        TRFD -- "valid" --> TRFSUB["POST → toast + chip Draft"]
    end

    subgraph T4["4 · Penyesuaian (Opname)"]
        OPN["Tabel opname: Menunggu / Disetujui"]
        OPND("Dialog Opname")
        OPN --> OPND
        OPND -- "hitung fisik + alasan kosong" --> OPNERR["submit disabled + alasan"]
        OPND -- "valid" --> OPNSUB["ajukan → status Menunggu"]
        OPN -- "Setujui (inventory.approve)" --> OPNOK["status Disetujui"]
    end

    TABS["Tab bar (roving tabindex)"] --> T1
    TABS --> T2
    TABS --> T3
    TABS --> T4
    R1 -- "Lihat Mutasi" --> LOG
```

### B3. Master Data — slide-over tiga tab

```mermaid
flowchart TD
    HDR["Header: Impor Excel · Tambah Produk"] --> KPI["4 KPI: 1.428 SKU · 14 menipis ·<br/>Rp 4,82 M · Bahan Baku"]
    KPI --> F["Filter: cari kode/nama ·<br/>kategori · status"]
    F --> T["Tabel katalog 9 kolom<br/>(baris nonaktif diredupkan)"]
    T -- "Tombol pensil (master.update)" --> EDIT["Mode edit"]
    T -- "Tombol mata" --> DETAIL["Mode detail →<br/>tab langsung ke Stok per Gudang"]
    HDR -- "Tambah Produk (master.create)" --> CREATE["Mode create"]
    EDIT & DETAIL & CREATE --> SLIDE["Slide-over kanan (Dialog side=right)"]

    SLIDE --> TB["Tab Informasi Dasar"]
    SLIDE --> TS["Tab Stok per Gudang"]
    SLIDE --> TH["Tab Riwayat Transaksi"]

    TB --> VALID{"Simpan"}
    VALID -- "nama < 3 karakter" --> ERR["border merah + role=alert<br/>fokus pindah ke field nama"]
    VALID -- "valid" --> SAVE["toast sukses + tutup"]
```

### B4. Dashboard — konsumen lintas modul

```mermaid
flowchart LR
    M1[Master Data] -.-> D
    M2[Inventory] -.-> D
    M3[Purchasing] -.-> D
    M4[Sales · belum ada] -.-> D
    M5[Finance · belum ada] -.-> D
    D["Dashboard"] --> KPI["4 KPI + kartu Hutang/Piutang"]
    D --> CH1["Tren penjualan 12 bulan<br/>(bar, nilai saat hover/fokus)"]
    D --> CH2["Donut penerimaan barang<br/>+ 3 bar breakdown"]
    D --> TB1["Purchase Order Terbaru → /purchasing"]
    D --> TB2["Faktur Jatuh Tempo (belum ada modul)"]
```

---

## C. Alur Data / API

### C1. Autentikasi & bootstrap sesi

```mermaid
sequenceDiagram
    autonumber
    actor U as Pengguna
    participant SP as Vue (Pinia auth)
    participant API as Laravel API

    U->>SP: isi email + password + Submit
    SP->>API: POST /api/auth/login<br/>throttle:10,1 · device_name
    API-->>SP: {success:true, data:{token, user}}
    SP->>SP: writeToken(localStorage)
    SP->>SP: pickInitialCompany()<br/>pakai company_id tersimpan atau [0]
    SP->>API: GET /api/auth/me (Authorization: Bearer)
    API-->>SP: {success:true, data:{user, company, permissions[]}}
    SP->>SP: isi user / company / permissions
    Note over SP: auth.can(kunci) = superadmin || permissions.includes(kunci)
    U->>SP: ganti perusahaan di sidebar
    SP->>SP: writeCompanyId(id)
    SP->>API: GET /api/auth/me (company aktif baru)
    API-->>SP: permissions perusahaan baru → seluruh sidebar & tombol dihitung ulang
    U->>SP: Logout
    SP->>API: POST /api/auth/logout
    SP->>SP: $reset() → bersihkan token, company, permissions
```

**Penyimpanan:** `localStorage` hanya memuat **token** dan **company_id aktif** — bukan daftar izin. Izin selalu diambil ulang dari `/api/auth/me`, sehingga pencabutan izin di server langsung berlaku pada muat ulang berikutnya.

### C2. Pipeline satu request API

```mermaid
flowchart TD
    subgraph PRI["Urutan middleware (middleware priority list)"]
        direction LR
        M1["auth:sanctum<br/>autentikasi"] --> M2["SetCurrentCompany<br/>set konteks tenant"] --> M3["EnsurePermission<br/>permission:module.action"] --> M4["SubstituteBindings<br/>resolve route model"]
    end

    REQ["Request masuk<br/>/api/*"] --> M1
    M1 -- "tanpa token" --> E401["401<br/>Sesi Anda telah berakhir."]
    M2 -- "X-Company-Id hilang<br/>atau bukan angka" --> E422C["422<br/>Perusahaan belum dipilih."]
    M2 -- "bukan anggota /<br/>perusahaan nonaktif" --> E403A["403 atau 404"]
    M3 -- "izin tidak dimiliki" --> E403B["403<br/>Anda tidak memiliki akses."]
    M4 --> SCOPE["Global scope: company_id<br/>menolak bawaan → where 1 = 0"]
    SCOPE --> CNT["Controller API"]
    CNT --> RESP["Envelope sukses<br/>{success:true, data}"]
    REQ -.-> ERR["Pengecualian di-render JSON<br/>karena request is api/*"]
    ERR --> E422["422 ValidationException<br/>message + errors{}"]
    ERR --> E404["404/403 HttpException"]
```

> **Urutan ini penting dan memang diatur lewat `prependToPriorityList(SubstituteBindings, …)` di `bootstrap/app.php`:**
>
> 1. **`auth:sanctum`** — menolak request tanpa token (401) sebelum ada pemeriksaan lain.
> 2. **`SetCurrentCompany`** — menyetel `CompanyContext` dari header `X-Company-Id` dan memastikan pengguna memang anggota perusahaan tersebut.
> 3. **`EnsurePermission`** — membaca `CompanyContext` lalu `user->canPerform($permission, $company)`; menolak (403) **sebelum** query apa pun berjalan. Middleware ini **wajib** berjalan setelah langkah 2, karena ia menganggap `company !== null`.
> 4. **`SubstituteBindings`** — resolve route model **paling akhir**, supaya konteks tenant dan izin sudah jelas sebelum database disentuh.

### C3. Bentuk respons (envelope)

```json
// Sukses
{ "success": true, "data": { "token": "...", "user": { } } }

// Gagal
{
  "success": false,
  "message": "Data yang diberikan tidak valid.",
  "errors": { "email": ["Kolom email wajib diisi."] }
}
```

Sisi klien membaca pola ini di `lib/errors.js` → pesan diterjemahkan ke Bahasa Indonesia lalu tampil sebagai toast atau error per-field.

### C4. RBAC — 37 kunci, 9 modul

```mermaid
flowchart TD
    ROLE["Peran (tabel roles)"] -- "array izin" --> KEY["37 kunci module.action"]
    KEY --> MOD["master · inventory · purchasing<br/>sales · finance · manufacturing<br/>report · settings · users"]
    MOD --> ACT["view · create · update · delete<br/>(inventory/purchasing/sales/finance/manufacturing + approve)<br/>report: view, export · settings: view, update"]

    KEY --> L1["Route guard<br/>meta.permission → redirect /"]
    KEY --> L2["Sidebar<br/>item disembunyikan"]
    KEY --> L3["Dalam halaman<br/>v-if can() pada tombol"]
    KEY --> L4["API<br/>middleware permission:..."]

    SUPER["is_superadmin = true"] -. lolos semua .-> L1
```

Empat lapis penjagaan yang sama-sama mengecek kunci yang sama — tidak ada pintu belakang: rute, sidebar, tombol, dan endpoint.

### C5. Peta endpoint

| Endpoint | Metode | Middleware | Status |
| --- | --- | --- | --- |
| `/api/auth/login` | POST | `throttle:10,1` | ✅ terpasang |
| `/api/auth/logout` · `/api/auth/me` | POST · GET | `auth:sanctum` | ✅ terpasang |
| `/api/companies` | GET · POST | `auth:sanctum` | ✅ terpasang |
| `/api/companies/{company}` | PATCH | `+ company` | ✅ terpasang |
| `/api/users` · `/api/roles` · `/api/permissions` | GET | `+ permission:users.view` | ✅ terpasang |
| `/api/users` · `/api/roles` | POST | `permission:users.create` | ✅ terpasang |
| `/api/users/{id}` · `/api/roles/{id}` | PATCH | `permission:users.update` | ✅ terpasang |
| `/api/users/{id}` · `/api/roles/{id}` | DELETE | `permission:users.delete` | ✅ terpasang |
| `/api/products` (Master Data) | — | `master.*` | ⏳ `TODO(M4)` — halaman sudah ada |
| `/api/inventory/*` (stok, mutasi, transfer, opname) | — | `inventory.*` | ⏳ `TODO(M3)` — halaman sudah ada |
| `/api/purchase-orders` (Purchasing) | — | `purchasing.*` | ⏳ `TODO(M5)` — halaman sudah ada |
| `/api/dashboard/*` | — | `report.view` | ⏳ `TODO(M2–M7)` |
| Sales · Finance · Manufacturing | — | kunci sudah tersedia | ⬜ belum ada halaman |

---

## Lampiran: Lokasi Kode Cepat

| Ingin mengubah… | Buka… |
| --- | --- |
| Rute / guard / izin halaman | `resources/js/router/index.js` |
| Item sidebar | `resources/js/layouts/navigation.js` |
| Shell (sidebar, topbar, drawer) | `resources/js/layouts/AppLayout.vue` |
| Sesi, ganti perusahaan, `auth.can()` | `resources/js/stores/auth.js` |
| Grup izin (tambah modul) | `app/Support/Permissions.php` |
| Urutan middleware & render error | `bootstrap/app.php` |
| Endpoint & topeng izin | `routes/api.php` |
| Warna & token tema | `resources/css/app.css` |
