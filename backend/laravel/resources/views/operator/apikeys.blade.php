@extends('operator.layouts.app')

@section('title', 'API Keys · Operator')
@section('page-title', 'API Keys')
@section('page-subtitle', 'Kelola kunci akses untuk perangkat IoT (sensor bus)')

@php $activePage = 'apikeys'; @endphp

@section('content')

{{-- ─── Info banner ─────────────────────────────────────────────── --}}
<div class="flex items-start mb-4"
     style="gap: 10px; padding: 12px 16px; background: var(--info-bg);
            border: 1px solid #BFDBFE; border-radius: 10px; font-size: 13px; color: var(--info)">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0; margin-top:1px">
        <circle cx="12" cy="12" r="10"/>
        <path d="M12 16v-4"/><path d="M12 8h.01"/>
    </svg>
    <div>
        API key dikirim oleh IoT simulator via header <code class="mono"
            style="background: #DBEAFE; padding: 1px 5px; border-radius: 4px">X-API-Key</code>.
        Plaintext key <strong>hanya tampil sekali</strong> saat dibuat — setelah itu hanya
        hash yang tersimpan. Hilang = harus buat baru.
    </div>
</div>

{{-- ─── Toolbar ─────────────────────────────────────────────────── --}}
<div class="flex items-center mb-3.5" style="gap: 10px">
    <div style="flex: 1"></div>
    <button onclick="openCreate()" class="btn btn-primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Buat API Key
    </button>
</div>

{{-- ─── Table card ──────────────────────────────────────────────── --}}
<div class="bg-white border" style="border-color: var(--ink-100); border-radius: 14px; overflow: hidden">
    <div id="loading" class="text-center" style="padding: 60px 20px; color: var(--ink-400); font-size: 13.5px">
        <div class="inline-block animate-spin rounded-full border-2 border-current border-r-transparent"
             style="width: 20px; height: 20px; margin-bottom: 10px"></div>
        <div>Memuat data...</div>
    </div>
    <div id="error" class="hidden text-center" style="padding: 40px 20px">
        <p class="font-semibold" style="color: var(--c-high); margin-bottom: 8px" id="error-msg">Gagal memuat</p>
        <button onclick="loadList()" class="text-sm underline" style="color: var(--c-high)">Coba lagi</button>
    </div>
    <div id="empty" class="hidden text-center" style="padding: 60px 20px; color: var(--ink-400); font-size: 13.5px">
        Belum ada API key. Klik "Buat API Key" untuk membuat.
    </div>

    <div id="table-wrapper" class="hidden" style="overflow-x: auto">
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Preview Kunci</th>
                    <th>Status</th>
                    <th>Terakhir Dipakai</th>
                    <th>Dibuat</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="table-body"></tbody>
        </table>
    </div>
</div>

{{-- ─── Modal: Buat Key (hanya nama) ───────────────────────────── --}}
<div id="create-modal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal" style="width: 420px">
        <div class="modal-header">
            <div class="modal-title">Buat API Key Baru</div>
            <button onclick="closeModal('create-modal')" class="icon-btn" aria-label="Tutup">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form id="create-form" onsubmit="submitCreate(event)">
            <div class="modal-body">
                <div class="field">
                    <label class="label label-required" for="form-name">Nama / Keterangan</label>
                    <input id="form-name" type="text" maxlength="100" class="input"
                           placeholder="Mis. Bus K1 — Sensor depan">
                    <div id="error-name" class="field-error"></div>
                </div>
                <div style="font-size: 12.5px; color: var(--ink-400)">
                    Key akan dibuat otomatis dalam format
                    <code class="mono" style="background: var(--ink-50); padding: 1px 5px; border-radius: 4px">
                        ce_iot_xxxxxxxx...</code>.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('create-modal')" class="btn btn-ghost">Batal</button>
                <button type="submit" id="create-btn" class="btn btn-primary">Buat Key</button>
            </div>
        </form>
    </div>
</div>

{{-- ─── Modal: Reveal Key (tampil SEKALI) ───────────────────────── --}}
<div id="reveal-modal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal" style="width: 500px">
        <div class="modal-header">
            <div class="modal-title">API Key Berhasil Dibuat</div>
            {{-- Tidak ada tombol tutup supaya user pasti copy dulu --}}
        </div>
        <div class="modal-body">
            {{-- Warning --}}
            <div class="flex items-start" style="gap: 10px; padding: 12px 14px; margin-bottom: 16px;
                         background: #FEF3C7; border: 1px solid #FCD34D; border-radius: 10px">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#D97706"
                     stroke-width="2" stroke-linecap="round" style="flex-shrink:0; margin-top:1px">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                    <path d="M12 9v4"/><path d="M12 17h.01"/>
                </svg>
                <div style="font-size: 13px; color: #92400E">
                    <strong>Simpan kunci ini sekarang.</strong>
                    Kunci tidak akan ditampilkan lagi setelah modal ini ditutup.
                    Kalau hilang, kunci harus dicabut dan dibuat baru.
                </div>
            </div>

            {{-- Key display --}}
            <label class="label">API Key</label>
            <div class="relative flex items-center">
                <input id="reveal-key" type="text" readonly class="input mono"
                       style="padding-right: 80px; font-size: 13px; background: var(--ink-25)">
                <button type="button" onclick="copyKey()"
                        id="copy-btn"
                        class="btn btn-outline"
                        style="position: absolute; right: 4px; padding: 5px 10px; font-size: 12px">
                    Salin
                </button>
            </div>
            <div id="copy-feedback" class="hidden mt-1"
                 style="font-size: 12px; color: var(--c-low)">✓ Tersalin ke clipboard!</div>
        </div>
        <div class="modal-footer">
            <button onclick="closeReveal()" class="btn btn-primary">Sudah Disimpan — Tutup</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const API_BASE = '/api/v1/admin/apikeys';
let items = [];

function setState(active) {
    ['loading','error','empty','table-wrapper'].forEach(id => {
        document.getElementById(id)?.classList.add('hidden');
    });
    document.getElementById(active)?.classList.remove('hidden');
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s ?? ''; return d.innerHTML;
}

// ─── Load ────────────────────────────────────────────────────────────
async function loadList() {
    setState('loading');
    try {
        const res  = await fetchAuth(API_BASE);
        const json = await res.json();
        if (!json.success) throw new Error(json.error?.message);
        items = json.data;
        renderTable();
        setState(items.length ? 'table-wrapper' : 'empty');
    } catch (e) {
        document.getElementById('error-msg').textContent = e.message;
        setState('error');
    }
}

function renderTable() {
    document.getElementById('table-body').innerHTML = items.map(it => {
        const statusPill = it.is_active
            ? `<span class="pill pill-low"><span class="pill-dot" style="background:var(--c-low)"></span>Aktif</span>`
            : `<span class="pill pill-neutral"><span class="pill-dot" style="background:var(--ink-300)"></span>Non-aktif</span>`;

        return `
            <tr>
                <td><span style="font-weight:600">${escapeHtml(it.name)}</span></td>
                <td><code class="mono" style="font-size:12px;color:var(--ink-500)">${escapeHtml(it.key_preview)}</code></td>
                <td>${statusPill}</td>
                <td style="color:var(--ink-400);font-size:12.5px">${it.last_used_at ?? '—'}</td>
                <td style="color:var(--ink-400);font-size:12.5px">${it.created_at ?? '—'}</td>
                <td>
                    <div class="flex" style="gap:4px">
                        <button onclick="toggleKey(${it.id})" class="icon-btn" title="${it.is_active ? 'Nonaktifkan' : 'Aktifkan'}">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                ${it.is_active
                                    ? '<path d="M18.36 6.64A9 9 0 1 1 5.64 5.64"/><path d="M12 2v10"/>'
                                    : '<path d="M12 22c5.52 0 10-4.48 10-10S17.52 2 12 2 2 6.48 2 12s4.48 10 10 10z"/><path d="m9 12 2 2 4-4"/>'}
                            </svg>
                        </button>
                        <button onclick="confirmRevoke(${it.id})" class="icon-btn danger" title="Cabut key">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                <path d="M10 11v6"/><path d="M14 11v6"/>
                                <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// ─── Create ──────────────────────────────────────────────────────────
function openCreate() {
    document.getElementById('form-name').value = '';
    document.getElementById('error-name').textContent = '';
    openModal('create-modal');
    setTimeout(() => document.getElementById('form-name').focus(), 50);
}

async function submitCreate(e) {
    e.preventDefault();
    const btn = document.getElementById('create-btn');
    btn.disabled = true; btn.textContent = 'Membuat...';
    document.getElementById('error-name').textContent = '';

    try {
        const res  = await fetchAuth(API_BASE, {
            method: 'POST',
            body: JSON.stringify({ name: document.getElementById('form-name').value.trim() }),
        });
        const json = await res.json();
        if (!json.success) {
            if (res.status === 422) document.getElementById('error-name').textContent = json.error?.details?.name?.[0] || json.error?.message;
            else alert(json.error?.message || 'Terjadi kesalahan saat membuat API key.');
            return;
        }
        closeModal('create-modal');
        // Tampilkan reveal modal
        document.getElementById('reveal-key').value = json.data.key;
        document.getElementById('copy-feedback').classList.add('hidden');
        openModal('reveal-modal');
        await loadList();
    } catch (e) { alert('Tidak bisa terhubung ke server.'); }
    finally { btn.disabled = false; btn.textContent = 'Buat Key'; }
}

// ─── Reveal modal ────────────────────────────────────────────────────
async function copyKey() {
    const val = document.getElementById('reveal-key').value;
    await navigator.clipboard.writeText(val);
    document.getElementById('copy-feedback').classList.remove('hidden');
    document.getElementById('copy-btn').textContent = '✓';
}

function closeReveal() {
    closeModal('reveal-modal');
    document.getElementById('reveal-key').value = '';
}

// ─── Toggle ──────────────────────────────────────────────────────────
async function toggleKey(id) {
    try {
        const res  = await fetchAuth(`${API_BASE}/${id}/toggle`, { method: 'PATCH' });
        const json = await res.json();
        if (!json.success) { alert(json.error?.message); return; }
        await loadList();
    } catch (e) { alert('Tidak bisa terhubung.'); }
}

// ─── Revoke ──────────────────────────────────────────────────────────
function confirmRevoke(id) {
    const it = items.find(x => x.id === id);
    if (!it) return;
    showConfirm(
        `Cabut API key "${it.name}"?`,
        'Perangkat IoT yang menggunakan key ini akan langsung ditolak aksesnya.',
        () => doRevoke(id)
    );
}

async function doRevoke(id) {
    try {
        const res  = await fetchAuth(`${API_BASE}/${id}`, { method: 'DELETE' });
        const json = await res.json();
        if (!json.success) { alert(json.error?.message); return; }
        await loadList();
    } catch (e) { alert('Tidak bisa terhubung.'); }
}

loadList();
</script>
@endpush
