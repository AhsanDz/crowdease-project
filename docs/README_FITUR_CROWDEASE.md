# CrowdEase — Dokumentasi Fitur

> Sistem Deteksi Kepadatan Transportasi Umum berbasis API dan IoT (Simulasi)

CrowdEase adalah sistem manajemen dan pemantauan kepadatan armada bus TransJakarta yang dibangun di atas Laravel 11 dengan 5 titik integrasi API. Dokumen ini menjelaskan seluruh fitur yang tersedia dalam repositori ini.

---

## Daftar Isi

1. [Gambaran Umum Sistem](#1-gambaran-umum-sistem)
2. [Fitur Aplikasi Penumpang](#2-fitur-aplikasi-penumpang)
3. [Fitur Dasbor Operator](#3-fitur-dasbor-operator)
4. [Fitur API Backend](#4-fitur-api-backend)
5. [Fitur IoT Simulator](#5-fitur-iot-simulator)
6. [Fitur Forecasting Kepadatan](#6-fitur-forecasting-kepadatan)
7. [Fitur Webhook Outbound](#7-fitur-webhook-outbound)
8. [Fitur Manajemen API Key](#8-fitur-manajemen-api-key)
9. [Fitur Autentikasi & Keamanan](#9-fitur-autentikasi--keamanan)
10. [Titik Integrasi API](#10-titik-integrasi-api)

---

## 1. Gambaran Umum Sistem

CrowdEase terdiri dari tiga komponen utama yang saling terintegrasi:

```
┌──────────────────┐        POST /sensors/readings        ┌─────────────────────────┐
│  IoT Simulator   │ ────────────────────────────────────► │                         │
│  (Python script) │                                       │   Backend Laravel API   │
└──────────────────┘                                       │   (Integration Hub)     │
                                                           │                         │
┌──────────────────┐        GET (polling 5 detik)          │   - Auth (Sanctum)      │
│ Aplikasi         │ ◄──────────────────────────────────── │   - Forecasting Service │
│ Penumpang (Web)  │                                       │   - Webhook Dispatcher  │
└──────────────────┘                                       │   - MySQL Database      │
                                                           └──────────┬──────────────┘
┌──────────────────┐        REST CRUD + Auth                         │
│ Dasbor Operator  │ ◄──────────────────────────────────────────────┘
│ (Web)            │
└──────────────────┘
```

**Tech Stack:**

| Lapisan | Teknologi |
|---------|-----------|
| Backend | Laravel 11, PHP 8.2 |
| Database | MySQL 8 |
| Autentikasi | Laravel Sanctum (operator), API Key (IoT) |
| Frontend Templating | Blade + Tailwind CSS + Alpine.js |
| Visualisasi Peta | Leaflet.js + OpenStreetMap |
| Visualisasi Data | Chart.js |
| IoT Simulator | Python 3.10, requests, python-dotenv |
| Dokumentasi API | Scribe (auto-generate) |

---

## 2. Fitur Aplikasi Penumpang

Aplikasi web mobile-friendly yang dapat diakses publik **tanpa login** di `http://localhost:8000`.

### 2.1 Halaman Beranda

Menampilkan daftar semua **koridor bus** yang aktif. Setiap koridor ditampilkan beserta kode, nama, warna identitas koridor, dan jumlah armada yang beroperasi.

### 2.2 Peta Kepadatan Real-time

Diakses via `/koridor/{code}`. Menampilkan:

- **Peta interaktif** berbasis Leaflet.js + OpenStreetMap yang menunjukkan posisi halte-halte pada koridor yang dipilih.
- **Indikator kepadatan bus** dalam warna hijau (LOW), kuning (MED), dan merah (HIGH) yang diperbarui otomatis setiap **5 detik** (polling).
- **Detail bus** saat marker di-klik: nomor plat, jumlah penumpang, kapasitas, persentase kepadatan, dan hasil **forecast** 5/10/15 menit ke depan.

### 2.3 Daftar Halte

Diakses via `/halte`. Menampilkan semua halte lintas koridor beserta nama, kode, dan lokasi.

### 2.4 Detail Halte

Diakses via `/halte/{stop}`. Menampilkan informasi lengkap satu halte termasuk koridor yang melewatinya dan bus yang saat ini menuju halte tersebut.

---

## 3. Fitur Dasbor Operator

Antarmuka desktop yang diakses via `/operator` dengan login wajib (`operator@crowdease.test` / `secret123` pada environment development).

### 3.1 Dashboard KPI

Halaman utama dasbor menampilkan ringkasan data secara agregat dalam satu tampilan:

- **Kartu statistik**: Total rute, rute aktif, total armada, armada aktif, total halte, dan jumlah log hari ini.
- **Rata-rata kepadatan** armada saat ini (dalam persen).
- **Distribusi level kepadatan**: pie chart jumlah bus yang saat ini LOW / MED / HIGH / tanpa data.
- **Grafik tren per jam** (24 jam terakhir) menggunakan Chart.js.
- **Tabel per koridor**: rata-rata kepadatan dan jumlah armada aktif per koridor.

### 3.2 Manajemen Rute (Koridor)

Fitur CRUD lengkap untuk data master koridor bus:

- Melihat daftar semua koridor (termasuk yang nonaktif).
- Menambah koridor baru dengan kode unik, nama, dan warna.
- Mengedit data koridor.
- Menonaktifkan / menghapus koridor (soft delete).

### 3.3 Manajemen Armada (Kendaraan)

Fitur CRUD lengkap untuk data master bus:

- Melihat daftar semua bus beserta statusnya.
- Menambah bus baru dengan nomor plat, kapasitas penumpang, dan penugasan ke koridor.
- Mengedit data bus (termasuk memindah ke koridor lain).
- Menonaktifkan / menghapus bus.

### 3.4 Manajemen Halte

Fitur CRUD lengkap untuk data master halte:

- Melihat daftar semua halte.
- Menambah halte baru dengan nama, kode, koordinat (lat/lng), dan penugasan ke koridor.
- Mengedit dan menghapus halte.

### 3.5 Manajemen API Key (untuk IoT)

Halaman pengelolaan API key yang digunakan oleh perangkat IoT:

- Melihat daftar semua API key yang terdaftar (nilai key **tidak ditampilkan** ulang demi keamanan).
- Membuat API key baru — nilai key asli **hanya muncul sekali** saat dibuat.
- Mengaktifkan / menonaktifkan key (toggle).
- Mencabut (menghapus) key yang tidak dibutuhkan.

### 3.6 Manajemen Webhook

Halaman pengelolaan webhook outbound untuk integrasi dengan sistem eksternal:

- Melihat daftar webhook yang terdaftar beserta status aktif/nonaktif dan event yang di-subscribe.
- Mendaftarkan URL webhook baru dengan memilih event yang ingin diterima.
- Mengedit konfigurasi webhook.
- Mengirim **test ping** ke URL webhook untuk memverifikasi koneksi.
- Melihat **log pengiriman** (delivery history) tiap webhook termasuk status sukses/gagal.
- Menghapus webhook.

---

## 4. Fitur API Backend

Semua endpoint diakses via prefix `/api/v1/` dengan tiga kelompok konsumer.

### 4.1 Public API (tanpa autentikasi)

Endpoint read-only yang dapat diakses siapa saja, digunakan oleh aplikasi penumpang:

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/routes` | Daftar semua koridor aktif |
| GET | `/routes/{id}` | Detail koridor + daftar halte |
| GET | `/routes/{id}/vehicles` | Daftar bus pada koridor + kepadatan terkini |
| GET | `/vehicles/{id}/density/current` | Kepadatan terkini satu bus |
| GET | `/vehicles/{id}/density/forecast` | Prediksi kepadatan 5/10/15 menit ke depan |
| GET | `/vehicles/{id}/density/history` | Riwayat kepadatan untuk grafik |

### 4.2 IoT API (autentikasi API Key)

Endpoint yang dikonsumsi oleh perangkat IoT atau simulator, menggunakan header `X-API-Key`:

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| POST | `/sensors/readings` | Kirim satu pembacaan sensor kepadatan |
| GET | `/sensors/check` | Verifikasi cepat bahwa API key valid |

Setiap kali data sensor masuk, sistem otomatis:
1. Menyimpan data ke tabel `density_logs`.
2. Menghitung ulang forecast kepadatan ke depan.
3. Memeriksa apakah threshold kepadatan tinggi terlampaui, lalu men-trigger webhook.

### 4.3 Admin/Operator API (autentikasi Bearer Token Sanctum)

Endpoint CRUD yang digunakan oleh dasbor operator:

- **Autentikasi**: `POST /auth/login`, `POST /auth/logout`, `GET /auth/me`
- **Dashboard**: `GET /admin/dashboard/stats`
- **CRUD Rute**: `GET/POST /admin/routes`, `PUT/DELETE /admin/routes/{id}`
- **CRUD Armada**: `GET/POST /admin/vehicles`, `PUT/DELETE /admin/vehicles/{id}`
- **CRUD Halte**: `GET/POST /admin/stops`, `PUT/DELETE /admin/stops/{id}`
- **API Key**: `GET/POST /admin/apikeys`, `PATCH /admin/apikeys/{id}/toggle`, `DELETE /admin/apikeys/{id}`
- **Webhook**: `GET/POST /admin/webhooks`, `PUT/DELETE /admin/webhooks/{id}`, `POST /admin/webhooks/{id}/test`

### 4.4 Konvensi API

- **Versioning via URL prefix** (`/api/v1/`): v1 tetap hidup minimal 6 bulan setelah v2 rilis.
- **Format respons standar**: semua respons membungkus data dalam `{ success, data, meta }`.
- **Kode error semantik**: `VALIDATION_FAILED`, `UNAUTHORIZED`, `FORBIDDEN`, `NOT_FOUND`, dll.
- **Pagination**: semua endpoint list mendukung parameter `per_page` dan `page`.
- **Rate limiting**: Public 60 req/menit per IP, IoT 600 req/menit per key, Operator 120 req/menit per user.

### 4.5 Dokumentasi API Otomatis

Dokumentasi interaktif di-generate oleh **Scribe** dan dapat diakses di `http://localhost:8000/docs`. Mendukung *try it out* langsung dari browser.

---

## 5. Fitur IoT Simulator

Script Python di direktori `iot-simulator/` yang mensimulasikan perangkat sensor di dalam bus, menggantikan perangkat keras (kamera + mikrokontroler) untuk keperluan pengembangan dan demo.

### 5.1 Mode Operasi

| Mode | Perintah | Keterangan |
|------|----------|------------|
| Kontinu (default) | `python simulator.py` | Kirim data terus-menerus setiap 2 detik |
| Burst | `python simulator.py --burst` | Kirim satu kali untuk semua armada lalu keluar |
| Timer | `python simulator.py --duration 60` | Jalan selama N detik lalu berhenti otomatis |
| Tick kustom | `python simulator.py --tick 1` | Ubah interval pengiriman (detik) |
| Jumlah armada | `python simulator.py --vehicles 7` | Simulasikan N armada |

### 5.2 Pola Data Realistis

Simulator tidak menghasilkan data acak murni. Data mengikuti pola berbasis waktu:

- **Jam sibuk pagi (07:00–09:00)** dan **sore (17:00–19:00)**: kepadatan mendekati 95% kapasitas.
- **Off-peak**: kepadatan stabil di sekitar 20% kapasitas.
- **Random walk** dengan noise ±3 penumpang per tick: tidak ada lompatan ekstrem.
- **Setiap armada punya baseline berbeda** (±10%) untuk variasi alami antar kendaraan.

### 5.3 Output Terminal Berwarna

Simulator menampilkan log real-time dengan kode warna:
- `✓` **hijau** = data berhasil dikirim
- `✗` **merah** = gagal beserta pesan error
- Persentase kepadatan berwarna mengikuti level: hijau (LOW), kuning (MED), merah (HIGH)

### 5.4 Armada Default

Simulator memuat 7 armada yang ID-nya disesuaikan dengan seeder database Laravel:

| ID | Plat | Koridor | Kapasitas |
|----|------|---------|-----------|
| 1 | B 7001 TRN | K1 | 60 |
| 2 | B 7002 TRN | K1 | 60 |
| 3 | B 7011 TRN | K2 | 60 |
| 4 | B 7012 TRN | K2 | 80 |
| 5 | B 7021 TRN | K3 | 60 |
| 6 | B 7031 TRN | K9 | 60 |
| 7 | B 7041 TRN | K13 | 80 |

---

## 6. Fitur Forecasting Kepadatan

Diimplementasikan sebagai `ForecastingService` di dalam Laravel (bukan microservice terpisah).

### 6.1 Cara Kerja

Setiap kali data sensor baru masuk, `ForecastingService` dipanggil otomatis untuk menghasilkan prediksi kepadatan ke depan menggunakan algoritma **moving average dengan peredam tren (damped trend)**:

```
prediksi(h) = rata_rata_window + tren × faktor_peredam(h)
```

- **Window**: 5 data terakhir dari kendaraan yang bersangkutan.
- **Tren**: dihitung dari selisih rata-rata paruh terbaru vs paruh lama dalam window.
- **Faktor peredam**: mencegah ekstrapolasi tren meledak pada horizon yang lebih jauh.

### 6.2 Horizon Prediksi

| Horizon | Faktor Peredam |
|---------|---------------|
| 5 menit | 0.5 |
| 10 menit | 0.8 |
| 15 menit | 1.0 |

### 6.3 Penyimpanan Forecast

Forecast lama kendaraan selalu dihapus sebelum forecast baru disimpan — tabel `forecasts` hanya menyimpan prediksi **terkini** per kendaraan. Model versi dicatat di kolom `model_version` (`moving_avg_v1`) untuk traceability.

---

## 7. Fitur Webhook Outbound

Sistem webhook memungkinkan backend CrowdEase mengirim notifikasi real-time ke sistem eksternal (misalnya Slack, Discord, atau backend pihak ketiga) setiap kali terjadi event tertentu.

### 7.1 Event yang Didukung

| Event | Keterangan |
|-------|------------|
| `density.recorded` | Setiap kali ada pencatatan kepadatan baru |
| `density.high_threshold_crossed` | Kepadatan bus melampaui batas tinggi |
| `density.low_threshold_recovered` | Kepadatan bus kembali normal setelah tinggi |
| `vehicle.created` | Armada baru ditambahkan |
| `vehicle.updated` | Data armada diperbarui |

### 7.2 Keamanan Payload (HMAC Signature)

Setiap payload yang dikirim ditandatangani dengan **HMAC SHA-256** menggunakan secret unik yang di-generate saat webhook didaftarkan. Penerima dapat memverifikasi keaslian payload menggunakan signature di header `X-CrowdEase-Signature`. Pola ini mengikuti standar industri yang digunakan oleh GitHub, Stripe, dan Slack.

### 7.3 Retry Policy Eksponensial

Jika pengiriman gagal (timeout atau respons non-2xx), sistem akan mencoba ulang secara otomatis dengan jeda yang semakin panjang:

```
Percobaan 1: langsung
Percobaan 2: +30 detik
Percobaan 3: +5 menit
Percobaan 4: +30 menit
Percobaan 5: +6 jam
```

Setelah 5 kali gagal, delivery ditandai sebagai `failed` di tabel `webhook_deliveries`. Setiap pengiriman (sukses maupun gagal) dicatat lengkap beserta status code, response body, dan waktu pengiriman.

### 7.4 Target yang Didukung

`WebhookDispatcher` mendukung pengiriman ke tiga jenis target:
- **Generic HTTP endpoint** (JSON POST)
- **Telegram Bot API**
- **Discord Webhook**

---

## 8. Fitur Manajemen API Key

API key digunakan untuk autentikasi machine-to-machine antara IoT simulator dan backend.

- Key dibuat oleh operator via dasbor dan **hanya ditampilkan sekali** saat dibuat.
- Nilai key disimpan **hashed** di database (`Hash::make()`) sehingga tidak ada yang bisa membaca nilai aslinya, termasuk operator.
- Key dapat di-toggle aktif/nonaktif tanpa dihapus, berguna saat perangkat perlu sementara diblokir.
- Key yang tidak dibutuhkan dapat dicabut (dihapus) permanen.
- Setiap request IoT melalui middleware `ApiKeyAuth` yang memvalidasi hash key secara real-time.

---

## 9. Fitur Autentikasi & Keamanan

CrowdEase mengimplementasikan **tiga lapis autentikasi** yang berbeda sesuai jenis konsumer:

| Konsumer | Mekanisme | Header |
|----------|-----------|--------|
| Penumpang (publik) | Tanpa autentikasi | — |
| IoT Simulator | API Key (hashed) | `X-API-Key: <key>` |
| Operator | Sanctum Bearer Token | `Authorization: Bearer <token>` |

**Middleware yang diimplementasikan:**

- `ApiKeyAuth`: Memvalidasi header `X-API-Key` terhadap hash yang tersimpan di database.
- `EnsureOperatorRole`: Memastikan token yang digunakan memiliki role operator (mencegah token biasa mengakses endpoint admin).
- `auth:sanctum`: Bawaan Laravel Sanctum untuk validasi bearer token operator.

---

## 10. Titik Integrasi API

CrowdEase mengimplementasikan 5 titik integrasi sebagai studi kasus Teknologi Integrasi Sistem:

| Kode | Sumber | Tujuan | Pola | Auth |
|------|--------|--------|------|------|
| **TI-1** | IoT Simulator (Python) | Backend Laravel | REST POST inbound | API Key (X-API-Key) |
| **TI-2** | Backend Laravel | Sistem eksternal (Slack/Discord/dll) | REST POST outbound, event-driven | HMAC SHA-256 signature |
| **TI-3** | Aplikasi Penumpang | Backend Laravel | REST GET polling 5 detik | Tanpa auth (public) |
| **TI-4** | Dasbor Operator | Backend Laravel | REST CRUD dengan autentikasi | Sanctum bearer token |
| **TI-5** | Aplikasi Penumpang | OpenStreetMap | HTTP tile request | Tanpa auth |

---

## Struktur Repository

```
crowdease/
├── backend/laravel/
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   ├── Api/V1/Admin/      ← Controller operator (TI-4)
│   │   │   ├── Api/V1/Iot/        ← Controller IoT (TI-1)
│   │   │   ├── Api/V1/Public/     ← Controller penumpang (TI-3)
│   │   │   ├── Operator/          ← Page controller dasbor
│   │   │   └── Passenger/         ← Page controller penumpang
│   │   ├── Models/                ← Eloquent models (Route, Vehicle, Stop, DensityLog, dll)
│   │   ├── Services/
│   │   │   ├── ForecastingService.php   ← Algoritma prediksi kepadatan
│   │   │   └── WebhookDispatcher.php    ← Logic pengiriman webhook outbound
│   │   ├── Events/                ← SensorDataReceived
│   │   ├── Listeners/             ← HandleSensorData
│   │   └── Jobs/                  ← DispatchWebhook (queued)
│   ├── resources/views/
│   │   ├── operator/              ← Template Blade dasbor operator
│   │   └── passenger/             ← Template Blade aplikasi penumpang
│   └── routes/
│       ├── api/v1/admin.php       ← Route TI-4
│       ├── api/v1/iot.php         ← Route TI-1
│       └── api/v1/public.php      ← Route TI-3
├── iot-simulator/
│   └── simulator.py               ← Script Python simulator IoT
├── docs/
│   ├── API_CONTRACT.md            ← Spesifikasi endpoint lengkap
│   └── ARCHITECTURE.md            ← Penjelasan keputusan arsitektur
└── postman/
    └── CrowdEase.postman_collection.json  ← Collection API testing
```

---

*Dokumentasi ini di-generate dari analisis kode sumber repositori `crowdease-project-dev`.*
