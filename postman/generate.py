"""
Generator Postman Collection v2.1 untuk CrowdEase API.
Output:
  - CrowdEase.postman_collection.json
  - CrowdEase.postman_environment.json
"""
import json
import uuid
from pathlib import Path

OUT = Path(__file__).parent

# ─── Helpers ───────────────────────────────────────────────────────────────

JSON_HEADER = [{"key": "Content-Type", "value": "application/json"}]
SANCTUM_HEADER = [{"key": "Authorization", "value": "Bearer {{operator_token}}"}]
IOT_HEADER = [{"key": "X-API-Key", "value": "{{iot_api_key}}"}]

SUCCESS_TEST = [
    "pm.test('Status 2xx', function () {",
    "    pm.expect(pm.response.code).to.be.oneOf([200, 201]);",
    "});",
    "",
    "pm.test('Envelope success=true', function () {",
    "    const json = pm.response.json();",
    "    pm.expect(json.success).to.eql(true);",
    "});",
]


def make_request(name, method, path_segments, body=None, description=None,
                 headers=None, tests=None, query=None):
    """Bangun satu request item Postman."""
    raw_path = "/".join(path_segments)
    raw_url = "{{api_v1}}/" + raw_path
    if query:
        qs = "&".join(f"{q['key']}={q.get('value','')}" for q in query if q.get('value') is not None)
        raw_url += "?" + qs if qs else ""

    url_obj = {
        "raw": raw_url,
        "host": ["{{api_v1}}"],
        "path": path_segments,
    }
    if query:
        url_obj["query"] = query

    req = {"method": method, "header": headers or [], "url": url_obj}
    if description:
        req["description"] = description
    if body is not None:
        req["body"] = {
            "mode": "raw",
            "raw": json.dumps(body, indent=2),
            "options": {"raw": {"language": "json"}},
        }

    item = {"name": name, "request": req, "response": []}
    if tests:
        item["event"] = [{
            "listen": "test",
            "script": {"exec": tests, "type": "text/javascript"},
        }]
    return item


def folder(name, description, items):
    return {"name": name, "description": description, "item": items}


# ─── 1. PUBLIC (Penumpang) ─────────────────────────────────────────────────
public_folder = folder(
    "1. Public (Penumpang · TI-3)",
    "Endpoint untuk aplikasi penumpang. Tidak butuh auth. Rate limit 60/menit/IP.",
    [
        make_request(
            "List koridor", "GET", ["routes"],
            description="Daftar koridor aktif dengan jumlah halte dan armada.",
            tests=SUCCESS_TEST + ["",
                "// Simpan id koridor pertama untuk request lain",
                "const data = pm.response.json().data;",
                "if (data && data.length > 0) {",
                "    pm.environment.set('first_route_id', data[0].id);",
                "    pm.environment.set('first_route_code', data[0].code);",
                "}"],
        ),
        make_request(
            "Detail koridor", "GET", ["routes", "{{first_route_id}}"],
            description="Detail satu koridor.",
            tests=SUCCESS_TEST,
        ),
        make_request(
            "List halte di koridor", "GET", ["routes", "{{first_route_id}}", "stops"],
            description="Halte pada koridor, urut sequence.",
            tests=SUCCESS_TEST + ["",
                "const data = pm.response.json().data;",
                "if (data && data.length > 0) pm.environment.set('first_stop_id', data[0].id);"],
        ),
        make_request(
            "List armada di koridor (polling utama)", "GET",
            ["routes", "{{first_route_id}}", "vehicles"],
            description=(
                "Endpoint polling utama yang dipanggil app penumpang tiap 5 detik. "
                "Mengembalikan armada beserta kepadatan terkini (latest_density) dan "
                "meta.server_time untuk display 'Diperbarui jam X'."
            ),
            tests=SUCCESS_TEST + ["",
                "const data = pm.response.json().data;",
                "if (data && data.length > 0) pm.environment.set('first_vehicle_id', data[0].id);"],
        ),
        make_request(
            "Forecast armada", "GET",
            ["vehicles", "{{first_vehicle_id}}", "forecast"],
            description=(
                "Prediksi kepadatan 5/10/15 menit ke depan untuk satu armada. "
                "Dihasilkan ForecastingService dengan model moving_avg_v1."
            ),
            tests=SUCCESS_TEST,
        ),
        make_request(
            "History kepadatan armada", "GET",
            ["vehicles", "{{first_vehicle_id}}", "density", "history"],
            description="Riwayat density log armada beberapa menit terakhir.",
            query=[{"key": "minutes", "value": "30"}],
            tests=SUCCESS_TEST,
        ),
    ]
)


# ─── 2. IoT (Sensor) ───────────────────────────────────────────────────────
iot_folder = folder(
    "2. IoT Sensor (TI-1)",
    (
        "Endpoint untuk perangkat IoT mengirim data sensor. "
        "Auth via X-API-Key header. Rate limit 600/menit/key."
    ),
    [
        make_request(
            "Health check API key", "GET", ["iot", "check"],
            description="Cek apakah API key valid. Berguna untuk perangkat IoT verifikasi koneksi saat boot.",
            headers=IOT_HEADER,
            tests=SUCCESS_TEST,
        ),
        make_request(
            "Kirim data sensor", "POST", ["sensors", "readings"],
            description=(
                "Submit satu pembacaan sensor (penumpang naik/turun). "
                "Bila kepadatan tinggi, sistem akan trigger forecast update dan "
                "webhook outbound (jika TI-2 aktif)."
            ),
            headers=JSON_HEADER + IOT_HEADER,
            body={
                "vehicle_id": 1,
                "passenger_count": 35,
                "capacity_at_time": 60,
                "recorded_at": "2026-05-23T10:32:15+07:00",
            },
            tests=[
                "pm.test('Status 201 Created', function () {",
                "    pm.response.to.have.status(201);",
                "});",
                "",
                "pm.test('Returns density log with computed ratio', function () {",
                "    const data = pm.response.json().data;",
                "    pm.expect(data).to.have.property('occupancy_ratio');",
                "    pm.expect(parseFloat(data.occupancy_ratio)).to.be.within(0, 2);",
                "});",
            ],
        ),
    ]
)


# ─── 3. Operator - Auth ────────────────────────────────────────────────────
op_auth_folder = folder(
    "3. Operator Auth (TI-4)",
    (
        "Endpoint login/logout untuk dasbor operator. "
        "Login memakai email+password (seeder: operator@crowdease.test / secret123). "
        "Pre-request: tidak ada. Test script LOGIN otomatis menyimpan token ke "
        "environment variable {{operator_token}}."
    ),
    [
        make_request(
            "Login", "POST", ["admin", "auth", "login"],
            description=(
                "Login dan dapatkan bearer token Sanctum. "
                "**Penting**: Test script di sini auto-set {{operator_token}}, "
                "jadi request berikutnya di folder operator otomatis pakai token ini."
            ),
            headers=JSON_HEADER,
            body={
                "email": "operator@crowdease.test",
                "password": "secret123",
                "device_name": "postman",
            },
            tests=[
                "pm.test('Status 200', function () { pm.response.to.have.status(200); });",
                "pm.test('Returns token', function () {",
                "    const data = pm.response.json().data;",
                "    pm.expect(data).to.have.property('token');",
                "});",
                "",
                "// Simpan token ke environment untuk request berikutnya",
                "const data = pm.response.json().data;",
                "if (data && data.token) {",
                "    pm.environment.set('operator_token', data.token);",
                "    console.log('Token disimpan ke {{operator_token}}');",
                "}",
            ],
        ),
        make_request(
            "Profil saya", "GET", ["admin", "auth", "me"],
            description="Detail user yang sedang login.",
            headers=SANCTUM_HEADER,
            tests=SUCCESS_TEST,
        ),
        make_request(
            "Logout", "POST", ["admin", "auth", "logout"],
            description=(
                "Cabut token sesi sekarang. Setelah logout, request berikutnya "
                "yang butuh auth akan 401."
            ),
            headers=SANCTUM_HEADER,
            tests=[
                "pm.test('Status 200', function () { pm.response.to.have.status(200); });",
                "",
                "// Bersihkan token dari environment",
                "pm.environment.unset('operator_token');",
            ],
        ),
    ]
)


# ─── 4. Operator - Routes CRUD ─────────────────────────────────────────────
op_routes_folder = folder(
    "4. Operator Routes/Koridor CRUD (TI-4)",
    "Manajemen master data koridor. Wajib bearer token operator.",
    [
        make_request(
            "List koridor", "GET", ["admin", "routes"],
            description="Termasuk koridor non-aktif (admin perlu lihat semua).",
            headers=SANCTUM_HEADER,
            query=[
                {"key": "search", "value": "", "disabled": True, "description": "Filter by code/name"},
                {"key": "per_page", "value": "20", "disabled": True, "description": "Page size"},
                {"key": "page", "value": "1", "disabled": True},
            ],
            tests=SUCCESS_TEST,
        ),
        make_request(
            "Buat koridor", "POST", ["admin", "routes"],
            description="Buat koridor baru. is_active default true.",
            headers=JSON_HEADER + SANCTUM_HEADER,
            body={
                "code": "K99",
                "name": "Demo Postman Corridor",
                "color": "#9333EA",
                "is_active": True,
            },
            tests=SUCCESS_TEST + ["",
                "// Simpan ID koridor baru ke env untuk dipakai PUT/DELETE",
                "const data = pm.response.json().data;",
                "if (data && data.id) {",
                "    pm.environment.set('created_route_id', data.id);",
                "    console.log('Created route id:', data.id);",
                "}"],
        ),
        make_request(
            "Detail koridor", "GET", ["admin", "routes", "{{created_route_id}}"],
            description="Detail koridor dengan jumlah halte & armada.",
            headers=SANCTUM_HEADER,
            tests=SUCCESS_TEST,
        ),
        make_request(
            "Update koridor (nonaktifkan)", "PUT",
            ["admin", "routes", "{{created_route_id}}"],
            description="Contoh: set is_active=false untuk 'menghilangkan' dari app penumpang tanpa hard delete.",
            headers=JSON_HEADER + SANCTUM_HEADER,
            body={"is_active": False, "name": "Demo (Nonaktif)"},
            tests=SUCCESS_TEST,
        ),
        make_request(
            "Hapus koridor (sukses)", "DELETE",
            ["admin", "routes", "{{created_route_id}}"],
            description="Aman karena koridor baru ini belum punya halte/armada.",
            headers=SANCTUM_HEADER,
            tests=SUCCESS_TEST,
        ),
        make_request(
            "Hapus K1 (harus 409)", "DELETE", ["admin", "routes", "1"],
            description=(
                "Demonstrasi safety check: K1 punya halte+armada, jadi DELETE "
                "harus return HTTP 409 dengan code HAS_DEPENDENCIES."
            ),
            headers=SANCTUM_HEADER,
            tests=[
                "pm.test('Status 409 Conflict', function () {",
                "    pm.response.to.have.status(409);",
                "});",
                "",
                "pm.test('Error code HAS_DEPENDENCIES', function () {",
                "    const err = pm.response.json().error;",
                "    pm.expect(err.code).to.eql('HAS_DEPENDENCIES');",
                "});",
            ],
        ),
    ]
)


# ─── 5. Operator - Vehicles CRUD ───────────────────────────────────────────
op_vehicles_folder = folder(
    "5. Operator Vehicles/Armada CRUD (TI-4)",
    "Manajemen master data armada bus.",
    [
        make_request(
            "List armada", "GET", ["admin", "vehicles"],
            description="Filter optional: ?route_id=, ?status=, ?search=plate",
            headers=SANCTUM_HEADER,
            query=[
                {"key": "route_id", "value": "1", "disabled": True},
                {"key": "status", "value": "active", "disabled": True, "description": "active|inactive|maintenance"},
                {"key": "search", "value": "", "disabled": True, "description": "Cari nomor plat"},
            ],
            tests=SUCCESS_TEST,
        ),
        make_request(
            "Buat armada", "POST", ["admin", "vehicles"],
            description="Daftarkan armada baru ke salah satu koridor.",
            headers=JSON_HEADER + SANCTUM_HEADER,
            body={
                "route_id": 1,
                "plate_number": "B 9999 PMN",
                "capacity": 60,
                "status": "active",
            },
            tests=SUCCESS_TEST + ["",
                "const data = pm.response.json().data;",
                "if (data && data.id) pm.environment.set('created_vehicle_id', data.id);"],
        ),
        make_request(
            "Detail armada", "GET",
            ["admin", "vehicles", "{{created_vehicle_id}}"],
            headers=SANCTUM_HEADER,
            tests=SUCCESS_TEST,
        ),
        make_request(
            "Update armada (maintenance)", "PUT",
            ["admin", "vehicles", "{{created_vehicle_id}}"],
            description="Set status ke 'maintenance' (mis. bus servis).",
            headers=JSON_HEADER + SANCTUM_HEADER,
            body={"status": "maintenance"},
            tests=SUCCESS_TEST,
        ),
        make_request(
            "Hapus armada", "DELETE",
            ["admin", "vehicles", "{{created_vehicle_id}}"],
            description="Aman selama armada baru ini belum punya density_logs.",
            headers=SANCTUM_HEADER,
            tests=SUCCESS_TEST,
        ),
    ]
)


# ─── 6. Operator - Stops CRUD ──────────────────────────────────────────────
op_stops_folder = folder(
    "6. Operator Stops/Halte CRUD (TI-4)",
    "Manajemen master data halte. Halte aman di-hard-delete (no FK dari tabel lain).",
    [
        make_request(
            "List halte", "GET", ["admin", "stops"],
            description="Filter optional: ?route_id=, ?search=name",
            headers=SANCTUM_HEADER,
            query=[
                {"key": "route_id", "value": "1", "disabled": True},
                {"key": "search", "value": "", "disabled": True},
            ],
            tests=SUCCESS_TEST,
        ),
        make_request(
            "Buat halte", "POST", ["admin", "stops"],
            description="Sequence harus unik per koridor. Contoh: halte sequence 99 di koridor 1.",
            headers=JSON_HEADER + SANCTUM_HEADER,
            body={
                "route_id": 1,
                "name": "Halte Demo Postman",
                "latitude": -6.2088,
                "longitude": 106.8456,
                "sequence": 99,
            },
            tests=SUCCESS_TEST + ["",
                "const data = pm.response.json().data;",
                "if (data && data.id) pm.environment.set('created_stop_id', data.id);"],
        ),
        make_request(
            "Detail halte", "GET",
            ["admin", "stops", "{{created_stop_id}}"],
            headers=SANCTUM_HEADER,
            tests=SUCCESS_TEST,
        ),
        make_request(
            "Update halte", "PUT",
            ["admin", "stops", "{{created_stop_id}}"],
            headers=JSON_HEADER + SANCTUM_HEADER,
            body={"name": "Halte Demo (Updated)"},
            tests=SUCCESS_TEST,
        ),
        make_request(
            "Hapus halte", "DELETE",
            ["admin", "stops", "{{created_stop_id}}"],
            headers=SANCTUM_HEADER,
            tests=SUCCESS_TEST,
        ),
    ]
)


# ─── Assemble collection ───────────────────────────────────────────────────
COLLECTION_DESC = """\
# CrowdEase API Collection

Collection lengkap untuk testing API CrowdEase end-to-end.

## Urutan Test yang Disarankan

1. **Folder 1 (Public)** — verifikasi data seeder masuk
2. **Folder 3 (Operator Auth) → Login** — dapatkan token (auto-saved ke env)
3. **Folder 2 (IoT)** — submit data sensor pakai API key seeder
4. **Folder 4-6 (CRUD)** — testing manajemen master data

## Variables yang Otomatis Diset

- `operator_token` — dari respons Login
- `first_route_id`, `first_route_code` — dari List koridor
- `first_stop_id`, `first_vehicle_id` — dari respons terkait
- `created_route_id`, `created_vehicle_id`, `created_stop_id` — dari respons POST

## Convention

Semua respons sukses pakai envelope `{success: true, data: ..., meta: ...}`.
Error pakai `{success: false, error: {code, message, details}}`.

Lihat docs/API_CONTRACT.md untuk spec lengkap.
"""

collection = {
    "info": {
        "_postman_id": str(uuid.uuid4()),
        "name": "CrowdEase API",
        "description": COLLECTION_DESC,
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json",
    },
    "item": [
        public_folder,
        iot_folder,
        op_auth_folder,
        op_routes_folder,
        op_vehicles_folder,
        op_stops_folder,
    ],
    "variable": [],
}

with open(OUT / "CrowdEase.postman_collection.json", "w") as f:
    json.dump(collection, f, indent=2)


# ─── Environment ───────────────────────────────────────────────────────────
environment = {
    "id": str(uuid.uuid4()),
    "name": "CrowdEase Local",
    "values": [
        {"key": "base_url",     "value": "http://localhost:8000", "type": "default", "enabled": True},
        {"key": "api_v1",       "value": "{{base_url}}/api/v1",  "type": "default", "enabled": True},
        {"key": "iot_api_key",
         "value": "ce_iot_devkey_3f8a1c9e7b2d4056a8c1e9f7b3d5028a",
         "type": "secret", "enabled": True},
        # Auto-managed by test scripts:
        {"key": "operator_token",     "value": "", "type": "secret",  "enabled": True},
        {"key": "first_route_id",     "value": "", "type": "default", "enabled": True},
        {"key": "first_route_code",   "value": "", "type": "default", "enabled": True},
        {"key": "first_stop_id",      "value": "", "type": "default", "enabled": True},
        {"key": "first_vehicle_id",   "value": "", "type": "default", "enabled": True},
        {"key": "created_route_id",   "value": "", "type": "default", "enabled": True},
        {"key": "created_vehicle_id", "value": "", "type": "default", "enabled": True},
        {"key": "created_stop_id",    "value": "", "type": "default", "enabled": True},
    ],
    "_postman_variable_scope": "environment",
}

with open(OUT / "CrowdEase.postman_environment.json", "w") as f:
    json.dump(environment, f, indent=2)


# Ringkasan
print(f"✓ Collection: {sum(len(f['item']) for f in collection['item'])} request di {len(collection['item'])} folder")
print(f"✓ Environment: {len(environment['values'])} variable")
print(f"✓ Output:")
for f in [OUT / "CrowdEase.postman_collection.json", OUT / "CrowdEase.postman_environment.json"]:
    print(f"  - {f.name} ({f.stat().st_size} bytes)")
