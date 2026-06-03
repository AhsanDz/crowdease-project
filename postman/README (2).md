# Postman Collection — CrowdEase API

Collection lengkap untuk testing dan demo API CrowdEase. Sudah include **27 request** di **6 folder** dengan pre/test scripts yang otomatis menyimpan token dan ID.

## File yang Disediakan

| File | Isi |
|------|-----|
| `CrowdEase.postman_collection.json` | Collection (27 request, 6 folder, semua sudah ada body/header/test) |
| `CrowdEase.postman_environment.json` | Environment variables siap pakai (base_url, IoT key, token slot) |

## Cara Import (3 langkah)

1. Buka Postman → klik tombol **Import** (kiri atas)
2. Drag kedua file JSON ke dialog import → klik **Import**
3. Klik dropdown environment di kanan atas → pilih **"CrowdEase Local"**

Selesai. Collection muncul di sidebar dengan 6 folder.

## Urutan Test yang Disarankan

Ikuti urutan ini sekali jalan untuk verifikasi end-to-end:

### 1. Verifikasi data dari seeder

Folder **"1. Public (Penumpang)"** → klik **"List koridor"** → klik **Send**

- Harus return 5 koridor dengan stops_count dan vehicles_count
- Test script auto-set `first_route_id` dan `first_route_code` ke environment

Lalu klik **"List armada di koridor"** → akan otomatis pakai `first_route_id`. Test script set `first_vehicle_id`.

### 2. Login operator (TI-4)

Folder **"3. Operator Auth"** → **"Login"** → **Send**

- Body sudah ter-set: `operator@crowdease.test` / `secret123`
- Test script **otomatis simpan token ke `operator_token`**
- Bisa lihat di tab "Console" Postman: log "Token disimpan ke {{operator_token}}"

Sekarang semua request di folder 4-6 otomatis pakai token ini di header.

### 3. Submit data sensor IoT (TI-1)

Folder **"2. IoT Sensor"** → **"Kirim data sensor"** → **Send**

- Sudah pakai `X-API-Key: {{iot_api_key}}` (key dari seeder)
- Body example: vehicle_id=1, passenger_count=35, capacity=60
- Harus return 201 Created dengan occupancy_ratio computed

### 4. CRUD demo (Koridor)

Folder **"4. Operator Routes CRUD"** → klik tiap request berurutan:

1. **List koridor** — lihat 5 koridor existing
2. **Buat koridor** — bikin "K99 Demo" (test script set `created_route_id`)
3. **Detail koridor** — pakai ID yang baru dibuat
4. **Update koridor** — set is_active=false
5. **Hapus koridor (sukses)** — boleh karena belum ada halte/armada
6. **Hapus K1 (harus 409)** — demo safety check: gagal karena K1 punya halte+armada

### 5. CRUD Armada & Halte

Folder 5 dan 6 — pola sama: list → create → show → update → delete.

## Variabel Otomatis

Test script di tiap request **menyimpan ID ke environment** supaya request berikutnya bisa langsung pakai:

| Variabel | Diisi oleh |
|----------|------------|
| `operator_token` | Login (folder 3) |
| `first_route_id` / `first_route_code` | List koridor (folder 1) |
| `first_vehicle_id` | List armada (folder 1) |
| `first_stop_id` | List halte (folder 1) |
| `created_route_id` | Buat koridor (folder 4) |
| `created_vehicle_id` | Buat armada (folder 5) |
| `created_stop_id` | Buat halte (folder 6) |

Jadi kalian **tidak perlu copy-paste ID manual** antar request. Semua otomatis ngalir.

## Pakai untuk Demo

Untuk demo akhir, paling efektif: **buka Postman Runner**.

1. Di sidebar, hover folder → klik 3 titik → **Run folder**
2. Centang request mana saja yang mau di-run
3. Klik **Run CrowdEase API**

Postman akan eksekusi semua request berurutan dan tampilkan pass/fail tiap test. Total ~5 detik buat seluruh collection.

Cocok untuk **opening demo**: "Pertama, saya tampilkan dulu semua endpoint API kami otomatis di-test pass." → klik Run → semua hijau.

## Catatan Edge Cases

**Request "Hapus K1 (harus 409)"** sengaja dibuat **expecting 409**. Test scriptnya `pm.response.to.have.status(409)`. Jadi kalau berhasil 409, **dianggap pass**. Ini cocok untuk demonstrasi safety check ke dosen.

**Request "Logout"** akan **menghapus** `operator_token` dari environment. Kalau jalankan logout lalu coba request auth lagi, harus 401. Untuk lanjutkan testing setelah logout, **login lagi**.

## Update Collection Bila Endpoint Berubah

Collection ini di-generate dari script Python (`generate.py`). Kalau ada endpoint baru atau parameter berubah:

1. Edit `generate.py` (tambah/ubah `make_request(...)`)
2. `python3 generate.py` — regenerate JSON
3. Re-import ke Postman

Sumber kebenaran ada di kode generator, bukan di JSON manual. **Lebih maintainable** daripada edit JSON langsung.

## Yang Belum Ada di Collection

Endpoint berikut belum dibangun di backend, jadi belum di collection:

- API Keys management (di Fase 5)
- Webhooks management (di Fase 5, bonus TI-2)
- Dashboard stats endpoint (akan dibangun saat operator UI di Fase 3)

Akan ditambah ke generator script setelah endpoint-nya jadi.

## Tip Penggunaan

- **Gunakan Postman Console** (View → Show Postman Console) untuk lihat detail request/response, plus `console.log` dari test scripts
- **Save Response** di Postman: klik "Save as Example" di response — bagus untuk dokumentasi
- **Mock server** kalau perlu: kanan-klik collection → "Mock collection" — bikin URL public untuk respons example (tidak butuh backend running)
