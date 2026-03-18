# Accurate API Integration — Dokumentasi Teknis

## Daftar Isi

1. [Overview](#1-overview)
2. [Konfigurasi Environment](#2-konfigurasi-environment)
3. [File Konfigurasi](#3-file-konfigurasi)
4. [Flow Autentikasi API Token](#4-flow-autentikasi-api-token)
5. [Cara Kerja `dataClient()`](#5-cara-kerja-dataclient)
6. [Caching Host Database](#6-caching-host-database)
7. [Struktur HTTP Request](#7-struktur-http-request)
8. [Operasi yang Tersedia](#8-operasi-yang-tersedia)
9. [Sync Manual & Otomatis](#9-sync-manual--otomatis)
10. [Error Handling & Logging](#10-error-handling--logging)

---

## 1. Overviewww

Integrasi dengan **Accurate Online** menggunakan **API Token** (bukan OAuth) yang cocok digunakan di background job / queue worker karena tidak bergantung pada session pengguna.

Setiap request ke Accurate API diamankan dengan dua lapisan:

| Lapisan | Mekanisme |
|---|---|
| Autentikasi | Bearer token (`ACCURATE_API_TOKEN`) |
| Verifikasi Integritas | HMAC-SHA256 signature dari timestamp |

---

## 2. Konfigurasi Environment

Tambahkan variabel berikut di file `.env`:

```dotenv
# Base URL Accurate Online
ACCURATE_API_URL=https://account.accurate.id

# API Token Authentication (digunakan untuk sync & queue)
ACCURATE_API_TOKEN=your_api_token_here
ACCURATE_APP_KEY=your_app_key_here
ACCURATE_SIGNATURE_SECRET=your_signature_secret_here

# Pengaturan Sync
ACCURATE_SYNC_INTERVAL_HOURS=2
ACCURATE_SYNC_INTERVAL_MINUTES=       # Opsional, override jam (untuk dev/testing)
ACCURATE_AUTO_SYNC_ENABLED=true
ACCURATE_SYNC_BATCH_SIZE=100

# Timeout request ke Accurate (detik)
ACCURATE_API_TIMEOUT=120
```

> **Cara mendapatkan kredensial:**
> Login ke Accurate Online → Settings → API → buat API Token baru.
> `ACCURATE_API_TOKEN` adalah token yang dihasilkan.
> `ACCURATE_SIGNATURE_SECRET` adalah secret key yang ditampilkan saat pembuatan token.

---

## 3. File Konfigurasi

File: `config/accurate.php`

```php
'api_url'          => env('ACCURATE_API_URL', 'https://account.accurate.id'),
'api_token'        => env('ACCURATE_API_TOKEN'),
'app_key'          => env('ACCURATE_APP_KEY'),
'signature_secret' => env('ACCURATE_SIGNATURE_SECRET'),
'api_timeout'      => env('ACCURATE_API_TIMEOUT', 120),
```

Tidak ada nilai hardcoded. Semua kredensial harus ada di `.env`.

---

## 4. Flow Autentikasi API Token

### 4.1 Diagram Flow

```
Application                        Accurate Online
    │                                    │
    │  Buat timestamp (WIB)              │
    │  "09/03/2026 14:30:00"             │
    │                                    │
    │  Hitung HMAC-SHA256                │
    │  hash_hmac('sha256',               │
    │    timestamp,                      │
    │    SIGNATURE_SECRET)               │
    │                                    │
    │──── POST /api/api-token.do ───────▶│
    │     Authorization: Bearer TOKEN    │
    │     X-Api-Timestamp: timestamp     │
    │     X-Api-Signature: hmac_result   │
    │                                    │
    │◀─── { d: { database: { host } } }──│
    │                                    │
    │  Cache host 8 jam                  │
    │                                    │
    │──── GET /api/item/list.do ────────▶│
    │     (ke host yang didapat)         │
    │     Authorization: Bearer TOKEN    │
    │     X-Api-Timestamp: timestamp     │
    │     X-Api-Signature: hmac_result   │
    │                                    │
    │◀─── { s: true, d: [...items] } ───│
```

### 4.2 Implementasi (`apiTokenClient`)

File: `app/Services/AccurateService.php`

```php
protected function apiTokenClient()
{
    $apiToken = config('accurate.api_token');
    $secret   = config('accurate.signature_secret');
    $host     = $this->resolveApiTokenHost();

    // Timestamp dalam timezone WIB (wajib format ini)
    $timestamp = now('Asia/Jakarta')->format('d/m/Y H:i:s');

    // Signature: HMAC-SHA256(timestamp, secret)
    $signature = hash_hmac('sha256', $timestamp, $secret);

    return Http::withToken($apiToken)
        ->withHeaders([
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature' => $signature,
        ])
        ->acceptJson()
        ->timeout(config('accurate.api_timeout', 120))
        ->baseUrl($host . '/accurate');
}
```

### 4.3 Catatan Penting tentang Signature

- **Input signature** adalah timestamp saja (`d/m/Y H:i:s`), bukan body request
- **Timezone wajib** WIB (Asia/Jakarta) — penggunaan UTC akan ditolak Accurate
- Signature harus dibuat **baru setiap request** (timestamp berubah per detik)
- Algoritma: `HMAC-SHA256(message=timestamp, key=SIGNATURE_SECRET)` → output hex string

---

## 5. Cara Kerja `dataClient()`

Method `dataClient()` adalah entry point untuk semua request. Ia menentukan metode autentikasi secara otomatis:

```php
protected function dataClient()
{
    // Prioritas 1: API Token (untuk queue worker & sync)
    if (config('accurate.api_token')) {
        return $this->apiTokenClient();
    }

    // Fallback: OAuth session (legacy web flow)
    $accessToken = Cache::get('accurate_access_token')
                ?? session('accurate_access_token');
    $database    = Cache::get('accurate_database')
                ?? session('accurate_database');

    return Http::withToken($accessToken)
        ->withHeaders(['X-Session-ID' => $database['session']])
        ->acceptJson()
        ->baseUrl($database['host'] . '/accurate');
}
```

| Kondisi | Metode yang digunakan |
|---|---|
| `ACCURATE_API_TOKEN` ada di `.env` | API Token + HMAC Signature |
| Tidak ada API Token | OAuth session (harus login via browser) |

**Rekomendasi:** Selalu gunakan API Token untuk production agar sync dapat berjalan di Artisan command dan queue worker.

---

## 6. Caching Host Database

Setiap API Token terikat pada satu database Accurate. Host database didapat dari endpoint `/api/api-token.do` dan di-cache selama **8 jam** agar tidak ada round-trip tambahan di setiap request:

```php
protected function resolveApiTokenHost(): string
{
    return Cache::remember('accurate_api_token_host', now()->addHours(8), function () {
        // Kirim request ke Accurate untuk resolve host
        $response = Http::withToken($apiToken)
            ->withHeaders([
                'X-Api-Timestamp' => $timestamp,
                'X-Api-Signature' => $signature,
            ])
            ->post(config('accurate.api_url') . '/api/api-token.do');

        return $response->json()['d']['database']['host'];
    });
}
```

> **Jika ganti database atau API token:** jalankan `php artisan cache:clear` untuk memaksa re-resolve host.

---

## 7. Struktur HTTP Request

Setiap request ke Accurate API memiliki struktur berikut:

### Headers

```
Authorization: Bearer {ACCURATE_API_TOKEN}
X-Api-Timestamp: {dd/MM/yyyy HH:mm:ss WIB}
X-Api-Signature: {hmac_sha256_hex}
Accept: application/json
```

### Base URL

```
{host_dari_api_token}/accurate
```

Contoh: `https://d1.accurate.id/accurate`

### Contoh Request — List Item

```
GET {baseUrl}/api/item/list.do
    ?fields=id,name,no,itemType,categoryName,unitName,warehouseReceipt.stock,sellPrice1
    &sort=name asc
    &sp.page=1
    &sp.pageSize=100
```

### Format Response

```json
{
  "s": true,
  "d": [
    { "id": 1, "name": "Coca Cola", "no": "BEV001", ... },
    ...
  ]
}
```

| Field | Arti |
|---|---|
| `s` | `true` = sukses, `false` = gagal |
| `d` | Data (array untuk list, object untuk detail) |
| `m` | Pesan error (jika `s = false`) |

---

## 8. Operasi yang Tersedia

Semua public method berada di `app/Services/AccurateService.php`.

### Items

| Method | Keterangan |
|---|---|
| `getItems($request, $fields)` | Ambil daftar item |
| `getDetailItem($id)` | Ambil detail item berdasarkan ID |
| `getStockItems($request)` | Ambil daftar stok item |
| `saveItem($data)` | Simpan/update item |
| `deleteItem($id)` | Hapus item |

### BOM (Bill of Materials)

| Method | Keterangan |
|---|---|
| `getDetailItem($id)` | Detail item termasuk `detailGroup` (komposisi bahan) |

### Categories, Units, Warehouses

| Method | Keterangan |
|---|---|
| `getItemCategories($request)` | Daftar kategori item |
| `getUnits($request)` | Daftar satuan |

---

## 9. Sync Manual & Otomatis

### Artisan Commands

```bash
# Sync item dari Accurate ke database lokal
php artisan accurate:sync-items

# Sync BOM (Bill of Materials)
php artisan accurate:sync-bom
```

### Web Routes (Admin)

| Method | URL | Keterangan |
|---|---|---|
| `GET` | `/admin/accurate/sync` | Halaman status sync |
| `POST` | `/admin/accurate/sync/items` | Trigger sync items manual |
| `POST` | `/admin/accurate/sync/bom` | Trigger sync BOM manual |
| `GET` | `/admin/accurate/sync/status` | Cek status sync terakhir |
| `POST` | `/admin/accurate/sync/interval` | Update interval auto-sync |
| `POST` | `/admin/accurate/sync/toggle` | Enable/disable auto-sync |

### Auto Sync

Jika `ACCURATE_AUTO_SYNC_ENABLED=true`, sync berjalan otomatis setiap `ACCURATE_SYNC_INTERVAL_HOURS` jam via Laravel Scheduler.

Pastikan scheduler berjalan di server:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### Lock Mekanisme

Sync menggunakan distributed lock (`Cache::lock`) untuk mencegah race condition jika dua proses sync berjalan bersamaan. Lock TTL: **30 menit**. Jika sync tergantung, cache key `accurate_sync_lock` dapat dihapus manual:

```bash
php artisan cache:forget accurate_sync_lock
```

---

## 10. Error Handling & Logging

Semua error tercatat di Laravel log dengan prefix `ACCURATE_ERROR`.

### Melihat log

```bash
tail -f storage/logs/laravel.log | grep ACCURATE
```

### Jenis Error Umum

| Error | Penyebab | Solusi |
|---|---|---|
| `Gagal mendapatkan host database` | API Token tidak valid atau salah secret | Cek `ACCURATE_API_TOKEN` dan `ACCURATE_SIGNATURE_SECRET` di `.env` |
| `Token Akses Accurate tidak ditemukan` | Tidak ada API Token dan tidak ada OAuth session | Set `ACCURATE_API_TOKEN` di `.env` |
| `HTTP Error 401` | Token expired atau signature tidak valid | Pastikan timezone server WIB atau offset +07:00 |
| `HTTP Error 429` | Rate limit Accurate | Tambah delay, kurangi `ACCURATE_SYNC_BATCH_SIZE` |
| Timeout | Koneksi lambat | Naikkan `ACCURATE_API_TIMEOUT` |

### Checklist Debug

```bash
# 1. Pastikan variabel .env terbaca
php artisan tinker --execute="dd(config('accurate.api_token'))"

# 2. Clear cache host (jika ganti database)
php artisan cache:forget accurate_api_token_host

# 3. Test sync manual
php artisan accurate:sync-items

# 4. Cek log
tail -100 storage/logs/laravel.log
```
