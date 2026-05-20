# Webhook Testing Guide — CrowdEase

Panduan lengkap untuk setup, mendaftarkan, dan menguji webhook Telegram di aplikasi CrowdEase.

---

## Daftar Isi

- [Prasyarat](#prasyarat)
- [Setup Telegram Bot](#1-setup-telegram-bot)
- [Konfigurasi Environment](#2-konfigurasi-environment)
- [Jalankan Migration & Seeder](#3-jalankan-migration--seeder)
- [Menjalankan Queue Worker](#4-menjalankan-queue-worker)
- [Testing Webhook](#5-testing-webhook)
  - [Test Ping via Endpoint Admin](#51-test-ping-via-endpoint-admin)
  - [Test Manual via Artisan](#52-test-manual-via-artisan)
  - [Test via Postman](#53-test-via-postman)
  - [Test End-to-End dengan Simulator](#54-test-end-to-end-dengan-simulator)
- [Format Pesan Telegram](#6-format-pesan-telegram)
- [Troubleshooting](#7-troubleshooting)
- [Cek Log Delivery](#8-cek-log-delivery)

---

## Prasyarat

- Laravel sudah berjalan (`php artisan serve`)
- MySQL sudah terkoneksi (cek `.env`)
- Queue driver sudah dikonfigurasi (minimal `database` atau `redis`)
- Akun Telegram aktif

---

## 1. Setup Telegram Bot

### Buat Bot Baru

1. Buka Telegram → cari **@BotFather**
2. Ketik `/newbot`
3. Ikuti instruksi — beri nama dan username bot
4. Salin **Bot Token** yang diberikan, formatnya:
   ```
   123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ
   ```

### Dapatkan Chat ID

**Opsi A — Chat pribadi:**
1. Kirim pesan apa saja ke bot kamu
2. Buka URL berikut di browser (ganti `<TOKEN>` dengan token kamu):
   ```
   https://api.telegram.org/bot<TOKEN>/getUpdates
   ```
3. Cari field `"chat": {"id": ...}` di response JSON
4. Contoh output:
   ```json
   {
     "message": {
       "chat": {
         "id": 123456789,
         "type": "private"
       }
     }
   }
   ```

**Opsi B — Group/Channel:**
1. Tambahkan bot ke group atau channel
2. Kirim pesan di group
3. Buka URL `/getUpdates` seperti di atas
4. Chat ID group biasanya negatif, contoh: `-1001234567890`

> **Catatan:** Jika `/getUpdates` mengembalikan array kosong (`"result": []`), kirim pesan ke bot terlebih dahulu lalu coba lagi.

---

## 2. Konfigurasi Environment

Tambahkan dua variable berikut ke file `.env`:

```env
TELEGRAM_BOT_TOKEN=123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ
TELEGRAM_CHAT_ID=123456789
```

Untuk group/channel, Chat ID menggunakan angka negatif:

```env
TELEGRAM_CHAT_ID=-1001234567890
```

Pastikan juga queue driver sudah diset:

```env
QUEUE_CONNECTION=database
```

---

## 3. Jalankan Migration & Seeder

```bash
# Buat tabel webhooks dan webhook_deliveries
php artisan migrate

# Daftarkan webhook Telegram ke database
php artisan db:seed --class=TelegramWebhookSeeder
```

Output yang diharapkan:

```
INFO  Seeding database.
✓ Webhook Telegram berhasil didaftarkan.
  URL: https://api.telegram.org/bot123.../sendMessage?chat_id=123456789
  Simpan secret key dari tabel webhooks untuk verifikasi HMAC.
```

Seeder akan membuat **dua webhook**:

| Nama | Events yang di-subscribe |
|---|---|
| `Telegram Alert` | `density.high_threshold_crossed`, `density.low_threshold_recovered` |
| `Telegram Summary` | `density.recorded` |

---

## 4. Menjalankan Queue Worker

Webhook dikirim via queue. Wajib jalankan worker di terminal terpisah:

```bash
php artisan queue:work
```

Untuk development dengan auto-restart saat ada perubahan kode:

```bash
php artisan queue:listen
```

Untuk summary terjadwal tiap 5 menit, jalankan scheduler di terminal lain:

```bash
php artisan schedule:work
```

---

## 5. Testing Webhook

### 5.1 Test Ping via Endpoint Admin

Endpoint ini mengirim event dummy langsung (synchronous) tanpa perlu data sensor.

**Login dulu untuk dapatkan token:**

```bash
curl -X POST http://localhost:8000/api/v1/admin/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "operator@crowdease.test",
    "password": "secret123"
  }'
```

**Cek ID webhook yang terdaftar:**

```bash
curl http://localhost:8000/api/v1/admin/webhooks \
  -H "Authorization: Bearer <TOKEN>"
```

**Kirim ping test ke webhook:**

```bash
curl -X POST http://localhost:8000/api/v1/admin/webhooks/1/test \
  -H "Authorization: Bearer <TOKEN>"
```

Response sukses:

```json
{
  "message": "Ping berhasil dikirim.",
  "success": true,
  "status_code": 200,
  "response_body": "{\"ok\":true,\"result\":{...}}"
}
```

---

### 5.2 Test Manual via Artisan

Kirim summary kepadatan langsung ke Telegram tanpa nunggu scheduler:

```bash
php artisan crowdease:density-summary
```

Output yang diharapkan:

```
✓ Terkirim ke webhook #2 (Telegram Summary)
Summary terkirim ke 1 webhook Telegram.
```

---

### 5.3 Test via Postman

Import collection atau buat request manual:

**POST sensor reading (trigger alert jika overcrowded):**

```
POST http://localhost:8000/api/v1/iot/sensors/readings
Header: X-API-Key: ce_iot_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

```json
{
  "vehicle_id": 1,
  "passenger_count": 60,
  "recorded_at": "2026-05-20T10:00:00+07:00"
}
```

> Jika kapasitas bus adalah 60, maka `passenger_count: 60` akan trigger level `high` dan mengirim alert ke Telegram.

**Simulasi pemulihan (turun ke normal):**

```json
{
  "vehicle_id": 1,
  "passenger_count": 20,
  "recorded_at": "2026-05-20T10:01:00+07:00"
}
```

---

### 5.4 Test End-to-End dengan Simulator

```bash
# Masuk ke folder simulator
cd iot-simulator

# Set environment
export CROWDEASE_API_KEY=ce_iot_xxxxxxxx
export CROWDEASE_BASE_URL=http://localhost:8000

# Jalankan simulator
python simulator.py
```

Amati terminal queue worker — setiap kali level berubah akan ada log seperti:

```
[2026-05-20 10:05:00] Processing: App\Jobs\DeliverWebhook
[2026-05-20 10:05:00] Processed: App\Jobs\DeliverWebhook
```

---

## 6. Format Pesan Telegram

### Alert — Kepadatan Tinggi (`density.high_threshold_crossed`)

```
🚨 ALERT — Kepadatan Tinggi

🚌 Bus: B 7042 TRN
🛣 Koridor: K1 — Blok M - Kota
👥 Penumpang: 52 / 60 (87%)
📶 Status: 🔴 HIGH
🕐 Waktu: 2026-05-20T10:05:00+07:00

🔮 Prediksi ke depan:
  🔴 +5 menit → HIGH
  🟡 +10 menit → MEDIUM
  🟢 +15 menit → LOW

—
CrowdEase · TIS TI-D Kelompok 7
```

### Pemulihan (`density.low_threshold_recovered`)

```
✅ Kepadatan Kembali Normal

🚌 Bus: B 7042 TRN
🛣 Koridor: K1 — Blok M - Kota
👥 Penumpang: 20 / 60 (33%)
📶 Status: 🟢 LOW
🕐 Waktu: 2026-05-20T10:10:00+07:00
```

### Summary Berkala (tiap 5 menit)

```
🕐 Ringkasan Kepadatan Bus
Update tiap 5 menit

K1 — Blok M - Kota
  Armada: 8/10 online · Avg: 72%
   ⛔1 🔴2 🟡3 🟢2

K2 — Pulogadung - Harmoni
  Armada: 6/8 online · Avg: 45%
   🟡1 🟢5

⏱ 2026-05-20T10:05:00+07:00
CrowdEase · TIS TI-D Kelompok 7
```

---

## 7. Troubleshooting

### Pesan tidak masuk ke Telegram

| Gejala | Kemungkinan Penyebab | Solusi |
|---|---|---|
| Test ping return `502` | Token atau Chat ID salah | Cek `.env`, pastikan tidak ada spasi |
| Test ping return `200` tapi pesan tidak muncul | Bot belum pernah di-start | Kirim `/start` ke bot di Telegram |
| Queue job tidak jalan | Queue worker tidak aktif | Jalankan `php artisan queue:work` |
| Status delivery `permanently_failed` | 5 attempt semua gagal | Cek `response_body` di tabel `webhook_deliveries` |
| `getUpdates` kosong | Belum pernah kirim pesan ke bot | Buka bot di Telegram, klik Start |

### Cek apakah Telegram menerima request

Test langsung ke Telegram API tanpa melalui Laravel:

```bash
curl -X POST "https://api.telegram.org/bot<TOKEN>/sendMessage" \
  -H "Content-Type: application/json" \
  -d '{
    "chat_id": "<CHAT_ID>",
    "text": "Test dari CrowdEase",
    "parse_mode": "Markdown"
  }'
```

Response sukses dari Telegram:

```json
{
  "ok": true,
  "result": {
    "message_id": 123,
    "chat": { "id": 123456789 }
  }
}
```

Jika `"ok": false`, periksa pesan error-nya:

- `"Unauthorized"` → Token salah
- `"chat not found"` → Chat ID salah atau bot belum di-start
- `"bot was blocked by the user"` → User memblokir bot

---

## 8. Cek Log Delivery

Lihat semua riwayat pengiriman webhook:

```bash
# Via endpoint admin
curl http://localhost:8000/api/v1/admin/webhooks/1/deliveries \
  -H "Authorization: Bearer <TOKEN>"

# Filter hanya yang gagal
curl "http://localhost:8000/api/v1/admin/webhooks/1/deliveries?status=permanently_failed" \
  -H "Authorization: Bearer <TOKEN>"
```

Atau cek langsung di database:

```sql
SELECT
  wd.delivery_id,
  wd.event,
  wd.status,
  wd.response_code,
  wd.response_body,
  wd.attempt,
  wd.created_at
FROM webhook_deliveries wd
JOIN webhooks w ON w.id = wd.webhook_id
ORDER BY wd.created_at DESC
LIMIT 20;
```

---

> Dokumentasi ini bagian dari proyek **CrowdEase** — Sistem Informasi Kepadatan Bus TransJakarta  
> TIS TI-D Kelompok 7