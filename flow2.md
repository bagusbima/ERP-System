# Alur Sistem ERP — Navigasi, Bisnis & Data (Tanpa Diagram)

Versi polos dari `FLOW.md`: isi dan urutannya sama persis, hanya diagram Mermaid diganti tabel, daftar berjenjang, dan diagram ASCII biasa. Cocok untuk dibaca di terminal, editor tanpa plugin Mermaid, atau dicetak.

Dokumen ini memetakan tiga lapis alur aplikasi:

| Bagian | Cakupan | Sumber kebenaran |
| --- | --- | --- |
| **A — Navigasi & User Journey** | Rute, guard, sidebar, kondisi halaman | `resources/js/router/index.js`, `layouts/navigation.js` |
| **B — Alur Bisnis per Modul** | Siklus dokumen & interaksi halaman | `resources/js/pages/*.vue` (data contoh) |
| **C — Alur Data / API** | Autentikasi, middleware, multi-tenant, RBAC | `routes/api.php`, `bootstrap/app.php`, `stores/auth.js` |

> **Status saat ini:** modul **Sales, Finance, Manufacturing** belum punya halaman. Kunci izinnya sudah tersedia (37 total) tetapi belum ada rute maupun endpoint.

---

## 0. Arsitektur Ringkas

```
BROWSER                               SERVER (Laravel, PHP 8.4)
+-----------------------------+       +------------------------------------------+
| Vue 3 SPA                   |       | Middleware                                |
|   vue-router + Pinia        | Bearer|   auth:sanctum -> company -> permission   |
|         |                   |======>|         |                                 |
|         v                   |       |         v                                 |
| Komponen UI                 | JSON  | Controller API                            |
|   Tailwind v4 + lucide      |<======|         |                                 |
+-----------------------------+envelope|        v                                 |
                                      | CompanyContext (scope company_id)         |
                                      |         |                                 |
                                      |         v                                 |
                                      | Database bersama + kolom company_id        |
                                      +------------------------------------------+
```

- **Auth:** Laravel Sanctum bearer token (disimpan di `localStorage`).
- **Multi-tenant:** database bersama + kolom `company_id`; penjaganya `SetCurrentCompany` + global scope yang **menolak secara bawaan** (`where 1 = 0`), dengan pintu keluar `CompanyContext::run()`.
- **RBAC ringan kustom:** 9 modul × aksi = **37 kunci izin**, 4 peran sistem, hanya-baca.

---

## A. Navigasi & User Journey

### A1. Guard router — dijalankan pada **setiap** navigasi

```
router.beforeEach(to)
  |
  +-- auth.restore()          muat /api/auth/me bila ada token
  |
  +-- to.meta.guest ?         (hanya /login)
  |     |-- ya + sudah login  -> lempar ke /
  |     |-- ya + belum login  -> lolos, tampilkan /login
  |     +-- tidak ------------+
  |                           |
  +---------------------------+
        |
        v
  auth.isAuthenticated ?
        |-- tidak -> lempar ke /login?redirect=to.fullPath
        |-- ya ----+
                  |
                  v
        to.meta.requiresCompany && !auth.company ?
                  |-- ya   -> lempar ke /companies
                  |-- tidak+
                          v
        to.meta.permission && !auth.can(permission) ?
                  |-- ya   -> lempar ke /
                  |-- tidak-> render halaman
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

```
Buka aplikasi
    |
    v
/login  --POST /api/auth/login (throttle 10/menit)-->  429 ? Tunggu 1 menit / flush cache
    |  200 OK
    v
pickInitialCompany()      pakai company_id tersimpan, atau perusahaan pertama
    |
    v
GET /api/auth/me   ->   user + company + permissions
    |
    +-- 0 perusahaan (superadmin) --> /companies (form Buat Perusahaan)
    |                                     |
    |                    POST /api/companies
    |                                     |
    +-- >= 1 perusahaan (owner) ---+     |
                                         v
                                   /  Dashboard
                                         |
                      +------------------+------------------+
                      |                                     |
                Ganti perusahaan                      Logout
           select di sidebar ->                POST /api/auth/logout
           writeCompanyId(id) ->               + $reset()
           GET /auth/me lagi                        |
           (izin dihitung ulang)                    +--> kembali ke /login
```

**Akun demo:** `owner@erp.test` / `password` (2 perusahaan → langsung Dashboard) · `superadmin@erp.test` / `password` (0 perusahaan → form buat perusahaan).

### A4. Kondisi yang bisa ditemui di halaman

| Dari | Ke | Pemicu |
| --- | --- | --- |
| — | **Memuat** | membuka rute |
| Memuat | **Terisi** | data contoh / respon API sukses |
| Memuat | **Kosong** | hasil filter 0 baris |
| Kosong | **Terisi** | tombol *Pulihkan Semua Filter* |
| Terisi | **Detail** | klik baris atau tombol mata |
| Detail | **Terisi** | menutup slide-over / dialog |
| Terisi | **Dibatalkan** | izin tidak dimiliki → tombol disembunyikan |
| Dibatalkan | *selesai* | pengguna dialihkan keluar dari aksi |

*Catatan:* `EmptyState` selalu memuat judul, deskripsi, dan aksi pemulihan.

- **Tombol aksi** muncul/hilang mengikuti izin (`v-if="canCreate"` dll), bukan hanya jadi `disabled`.
- **Pagination baris 2+** sengaja `disabled` — datanya masih placeholder sampai endpoint terpasang; alasannya dikirim lewat `title`.
- **Toast** (`role="status"`) muncul di kanan-bawah selama 3 detik setelah aksi.

### A5. Shell aplikasi (`AppLayout`)

```
+---------------------------+------------------------------------------+
| SIDEBAR                   | TOPBAR (sticky, h-16, backdrop-blur)     |
| (fixed w-60;              |                                          |
|  drawer di < md)          |  - perusahaan / nama halaman             |
|                           |  - peran - lonceng - pengaturan          |
|  - nama perusahaan        |  - avatar - logout                       |
|    + kode ID              +------------------------------------------+
|  - nav bergrup            | MAIN  ->  <router-view/>                 |
|    (dipengaruhi izin)     |                                          |
|  - status server          |                                          |
|  - profil + logout        |                                          |
+---------------------------+------------------------------------------+
```

---

## B. Alur Bisnis per Modul

### B1. Purchasing — siklus Purchase Order

| Status | Aksi → Status berikutnya | Izin | Catatan |
| --- | --- | --- | --- |
| `Draft` | **Setujui** → `Disetujui` | `purchasing.approve` | muncul `confirm()` dulu |
| `Draft` | **Cetak PO** | `purchasing.view` | `window.print()` |
| `Draft` | `Dibatalkan` | — | datang dari pemasok |
| `Disetujui` | **Cetak PO** | `purchasing.view` | tombol *Setujui* kini nonaktif: **Sudah Disetujui** |
| `Disetujui` | **Terima Barang** → `Diterima` | `purchasing.update` | — |
| `Disetujui` | `Dibatalkan` | — | datang dari pemasok |
| `Diterima` / `Dibatalkan` | *(status akhir)* | — | — |

Catatan yang tampil di kartu dokumen:

- `Draft` → *"Dokumen masih berstatus Draft — dapat diubah sebelum approval."*
- selain `Draft` → *"Dokumen terkunci secara sistem setelah approval level 2."*

**Interaksi halaman:**

```
Filter bar (cari No.PO/pemasok - status - periode)
    |
    v
Tabel arsip 10 PO   (urutkan: tanggal terbaru / nominal tertinggi / status)
    |  klik baris atau tombol mata
    v
Kartu dokumen aktif  --scroll mulus ke-->  #po-preview
    |-- 4 metadata : pemasok - tanggal & ketentuan - gudang - logistik
    |-- Tabel item : qty x harga - diskon
    |-- Keuangan   : Subtotal -> PPN 11% -> biaya kirim -> Total Akhir PO
    +-- Aksi
          +-- purchasing.create  -> + Buat PO Baru   (placeholder)
          +-- report.export      -> Ekspor CSV       (placeholder)
          +-- purchasing.approve -> Setujui          -> toast
          +-- purchasing.update  -> Terima Barang    -> toast
```

Semua angka keuangan **dihitung dari baris item**, bukan angka mati:
`total = subtotal + round(subtotal × 0,11) + biayaKirim`.

### B2. Inventory — empat tab

```
Tab bar
  |
  +-- 1. Posisi Stok Real-Time
  |      Filter: gudang - cari SKU - Hanya Stok Kritis
  |         -> Tabel stok: selisih + chip Kritis/Normal
  |              |-- "Lihat Mutasi"  -> pindah ke tab 2 + toast
  |              +-- "Buat PO"       -> toast placeholder (purchasing.create)
  |                  pada baris kritis tombolnya tampil menonjol
  |
  +-- 2. Mutasi & Kartu Stok
  |      Log masuk / keluar / saldo akhir per surat jalan, GRN, transfer, opname
  |
  +-- 3. Transfer Antar Gudang
  |      Tabel: Dikirim / Diterima / Draft
  |         -> Dialog Transfer Gudang
  |              |-- asal = tujuan -> role=alert:
  |              |                   "Gudang asal dan tujuan tidak boleh sama"
  |              +-- valid         -> submit -> toast + baris baru status Draft
  |
  +-- 4. Penyesuaian (Opname)
         Tabel: Menunggu / Disetujui
            -> Dialog Opname
                 |-- hitung fisik atau alasan kosong
                 |      -> submit disabled (penjelasan lewat title)
                 +-- valid -> ajukan -> status Menunggu
            -> "Setujui" (inventory.approve) -> status Disetujui
```

### B3. Master Data — slide-over tiga tab

```
Header: [Impor Excel] [Tambah Produk (master.create)]
   |
   v
4 KPI: 1.428 SKU - 14 menipis - Rp 4,82 M - Bahan Baku
   |
   v
Filter: cari kode/nama - kategori - status
   |
   v
Tabel katalog 9 kolom (baris nonaktif diredupkan)
   |-- tombol pensil (master.update) -> Mode edit
   |-- tombol mata                   -> Mode detail (langsung ke tab Stok per Gudang)
   +-- tombol Tambah Produk          -> Mode create
                 |
                 v
      Slide-over kanan (Dialog side=right)
                 |
      +----------+-----------+
      |          |           |
   Informasi   Stok per   Riwayat
    Dasar      Gudang    Transaksi
      |
      v
   Simpan
      |-- nama < 3 karakter -> border merah + role=alert
      |                       fokus pindah ke field nama
      +-- valid              -> toast sukses + dialog tertutup
```

### B4. Dashboard — konsumen lintas modul

```
Master Data ---------+
Inventory ------------+
Purchasing -----------+--> Dashboard
Sales   (belum ada) --+        |-- 4 KPI + kartu Hutang/Piutang
Finance (belum ada) -+        |-- Tren penjualan 12 bulan (bar,
                               |   nilai saat hover ATAU fokus keyboard)
                               |-- Donut penerimaan barang
                               |   + 3 bar breakdown
                               |-- Purchase Order Terbaru -> /purchasing
                               +-- Faktur Jatuh Tempo (modul belum ada)
```

---

## C. Alur Data / API

### C1. Autentikasi & bootstrap sesi

| # | Langkah | Pihak | Tujuan / Respon |
| --- | --- | --- | --- |
| 1 | Isi email + password, tekan Submit | Pengguna → Vue | — |
| 2 | `POST /api/auth/login` (`throttle:10,1`, `device_name`) | Vue → API | `{success:true, data:{token, user}}` |
| 3 | `writeToken()` | Vue internal | token disimpan ke `localStorage` |
| 4 | `pickInitialCompany()` | Vue internal | pakai `company_id` tersimpan, atau `[0]` |
| 5 | `GET /api/auth/me` (`Authorization: Bearer`) | Vue → API | `{success:true, data:{user, company, permissions[]}}` |
| 6 | Isi `user` / `company` / `permissions` | Vue internal | `auth.can(kunci) = is_superadmin \|\| permissions.includes(kunci)` |
| 7 | Ganti perusahaan (select di sidebar) | Pengguna → Vue | `writeCompanyId(id)` |
| 8 | `GET /api/auth/me` (company aktif baru) | Vue → API | izin baru → seluruh sidebar & tombol dihitung ulang |
| 9 | Logout | Pengguna → Vue | `POST /api/auth/logout` |
| 10 | `$reset()` | Vue internal | bersihkan token, company, permissions → kembali ke `/login` |

**Penyimpanan:** `localStorage` hanya memuat **token** dan **company_id aktif** — bukan daftar izin. Izin selalu diambil ulang dari `/api/auth/me`, sehingga pencabutan izin di server langsung berlaku pada muat ulang berikutnya.

### C2. Pipeline satu request API

```
Request /api/*
    |
    +-- auth:sanctum
    |     |-- tanpa token   -> 401 "Sesi Anda telah berakhir."
    |     +-- token valid --+
    |                       |
    |     urutan prioritas (dari atas, di-prepend ke atas SubstituteBindings):
    |       1. SetCurrentCompany   konteks tenant   [422/403/404 bila header salah]
    |       2. EnsurePermission    permission:...   [403 bila izin kurang; baca CompanyContext]
    |       3. SubstituteBindings  resolve route model (paling akhir)
    |                       |
    +-----------------------+
                            v
      Global scope company_id   menolak bawaan -> where 1 = 0
                            |
                            v
                     Controller API
                            |
                            v
             Sukses: { success: true, data: ... }


    Jalur kegagalan (pengecualian dirender JSON karena request is 'api/*'):
      ValidationException -> 422 { success:false, message, errors{} }
      HttpException       -> 404 / 403 { success:false, message }
```

> **Catatan penting urutan:** `SetCurrentCompany` dan `EnsurePermission` sengaja di-*prepend* ke atas `SubstituteBindings`, agar pengecekan tenant dan izin terjadi **sebelum** pemodelan rute menyentuh database.

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

```
Peran (tabel roles)
    |
    +-- array izin
          |
          v
    37 kunci  module.action
          |
          +-- modul : master - inventory - purchasing - sales - finance
          |          manufacturing - report - settings - users
          +-- aksi  : view - create - update - delete
                      (+ approve pada inventory/purchasing/sales/finance/manufacturing)
                      (report: view, export)   (settings: view, update)
          |
          +--> [1] Route guard    meta.permission -> redirect /
          +--> [2] Sidebar        item tidak dirender sama sekali
          +--> [3] Dalam halaman  v-if can() pada tombol
          +--> [4] API            middleware permission:module.action

    is_superadmin = true   ->   lolos keempat lapis di atas
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
