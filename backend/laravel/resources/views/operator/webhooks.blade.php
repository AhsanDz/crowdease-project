@extends('operator.layouts.app')

@section('title', 'Webhooks · Operator')
@section('page-title', 'Webhooks')
@section('page-subtitle', 'Kirim notifikasi keluar saat kepadatan bus mencapai ambang batas')

@php $activePage = 'webhooks'; @endphp

@section('content')

{{-- ─── Info banner TI-2 ───────────────────────────────────────── --}}
<div class="flex items-start mb-4"
     style="gap: 10px; padding: 12px 16px; background: var(--ink-25);
            border: 1px solid var(--ink-100); border-radius: 10px; font-size: 13px; color: var(--ink-500)">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0; margin-top:1px">
        <circle cx="12" cy="12" r="10"/>
        <path d="M12 16v-4"/><path d="M12 8h.01"/>
    </svg>
    <div>
        Ketika sensor IoT melaporkan <strong>kepadatan ≥ 85%</strong> (<code class="mono"
            style="background: var(--ink-100); padding: 1px 5px; border-radius:4px">density.alert</code>)
        atau <strong>≥ 95%</strong> (<code class="mono"
            style="background: var(--ink-100); padding: 1px 5px; border-radius:4px">density.critical</code>),
        backend otomatis POST payload ke URL webhook yang terdaftar —
        dilengkapi tanda tangan <code class="mono"
            style="background: var(--ink-100); padding: 1px 5px; border-radius:4px">X-CrowdEase-Signature</code>
        untuk verifikasi. Demokan dengan <a href="https://webhook.site" target="_blank"
            style="color:var(--brand)">webhook.site</a> atau Discord webhook.
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
        Tambah Webhook
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
        Belum ada webhook terdaftar.
    </div>

    <div id="table-wrapper" class="hidden" style="overflow-x: auto">
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>URL Tujuan</th>
                    <th>Events</th>
                    <th>Status</th>
                    <th>Terakhir Dikirim</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="table-body"></tbody>
        </table>
    </div>
</div>

{{-- ─── Toast notifikasi test ───────────────────────────────────── --}}
<div id="toast" class="hidden"
     style="position: fixed; bottom: 24px; right: 24px; z-index: 2000;
            padding: 12px 18px; border-radius: 10px;
            font-size: 13px; font-weight: 600;
            box-shadow: 0 8px 24px rgba(11,15,20,0.12);
            transition: opacity 0.3s">
</div>

{{-- ─── Create/Edit Modal ───────────────────────────────────────── --}}
<div id="crud-modal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal" style="width: 520px">
        <div class="modal-header">
            <div class="modal-title" id="modal-title">Tambah Webhook</div>
            <button onclick="closeModal('crud-modal')" class="icon-btn" aria-label="Tutup">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form id="crud-form" onsubmit="submitForm(event)">
            <div class="modal-body">
                <div class="field">
                    <label class="label label-required" for="form-name">Nama</label>
                    <input id="form-name" type="text" maxlength="100" class="input"
                           placeholder="Mis. Notifikasi Discord Ops">
                    <div id="error-name" class="field-error"></div>
                </div>

                <div class="field">
                    <label class="label label-required" for="form-url">URL Tujuan</label>
                    <input id="form-url" type="url" class="input mono"
                           placeholder="https://discord.com/api/webhooks/... atau https://webhook.site/...">
                    <div id="error-url" class="field-error"></div>
                </div>

                <div class="field">
                    <label class="label">Subscribe ke Events</label>
                    <div class="flex flex-col" style="gap: 8px; margin-top: 4px">
                        @foreach ([
                            ['density.alert',    'Kepadatan Padat (≥ 85%)',  'Paling umum untuk notifikasi operasi'],
                            ['density.critical', 'Kritis (≥ 95%)',           'Hanya saat hampir penuh'],
                            ['*',                'Semua event',              'Termasuk webhook.test'],
                        ] as [$val, $label, $hint])
                            <label class="flex items-start" style="gap: 8px; cursor: pointer">
                                <input type="checkbox" name="events[]" value="{{ $val }}"
                                       class="event-check"
                                       style="width: 15px; height: 15px; margin-top: 2px;
                                              accent-color: var(--brand); flex-shrink:0">
                                <div>
                                    <div class="font-semibold" style="font-size: 13px">
                                        {{ $label }}
                                        <code class="mono"
                                              style="background: var(--ink-50); padding: 1px 6px;
                                                     border-radius: 4px; font-size: 11px">{{ $val }}</code>
                                    </div>
                                    <div style="font-size: 11.5px; color: var(--ink-400); margin-top: 1px">{{ $hint }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <div id="error-events" class="field-error"></div>
                </div>

                <div class="field">
                    <label class="label" for="form-secret">Secret (untuk verifikasi HMAC)</label>
                    <div class="flex" style="gap: 8px">
                        <input id="form-secret" type="text" class="input mono"
                               style="flex:1" placeholder="Biarkan kosong untuk auto-generate">
                        <button type="button" onclick="generateSecret()" class="btn btn-outline" style="flex-shrink:0">
                            Acak
                        </button>
                    </div>
                    <div style="margin-top: 5px; font-size: 11.5px; color: var(--ink-400)">
                        Header kiriman: <code class="mono">X-CrowdEase-Signature: sha256=&lt;hmac&gt;</code>
                    </div>
                    <div id="error-secret" class="field-error"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('crud-modal')" class="btn btn-ghost">Batal</button>
                <button type="submit" id="submit-btn" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
const API_BASE = '/api/v1/admin/webhooks';
let items = [];
let editingId = null;

const EVENT_LABELS = {
    'density.alert':    'Padat ≥85%',
    'density.critical': 'Kritis ≥95%',
    '*':                'Semua',
};

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
        const events = (it.events ?? ['density.alert'])
            .map(ev => `<span class="pill pill-info" style="font-size:11px">${escapeHtml(EVENT_LABELS[ev] ?? ev)}</span>`)
            .join(' ');

        const statusPill = it.is_active
            ? `<span class="pill pill-low"><span class="pill-dot" style="background:var(--c-low)"></span>Aktif</span>`
            : `<span class="pill pill-neutral"><span class="pill-dot" style="background:var(--ink-300)"></span>Nonaktif</span>`;

        const urlShort = it.url.length > 44 ? it.url.slice(0, 44) + '…' : it.url;

        return `
            <tr>
                <td><span style="font-weight:600">${escapeHtml(it.name)}</span></td>
                <td>
                    <a href="${escapeHtml(it.url)}" target="_blank" rel="noopener"
                       class="mono" style="font-size:12px;color:var(--brand);text-decoration:none"
                       title="${escapeHtml(it.url)}">${escapeHtml(urlShort)}</a>
                </td>
                <td><div class="flex flex-wrap" style="gap:4px">${events}</div></td>
                <td>${statusPill}</td>
                <td style="color:var(--ink-400);font-size:12.5px">${it.last_triggered_at ?? '—'}</td>
                <td>
                    <div class="flex" style="gap:4px">
                        <button onclick="sendTest(${it.id})" class="icon-btn" title="Kirim test payload">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                        </button>
                        <button onclick="openEdit(${it.id})" class="icon-btn" title="Edit">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>
                        <button onclick="confirmDelete(${it.id})" class="icon-btn danger" title="Hapus">
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

// ─── Test button ─────────────────────────────────────────────────────
async function sendTest(id) {
    const it = items.find(x => x.id === id);
    if (!it) return;
    showToast('Mengirim test payload...', 'info');
    try {
        const res  = await fetchAuth(`${API_BASE}/${id}/test`, { method: 'POST' });
        const json = await res.json();
        if (json.success) {
            showToast('✓ Test berhasil dikirim ke ' + it.url.slice(0, 40) + '…', 'success');
            await loadList();
        } else {
            showToast('✗ ' + (json.error?.message || 'Gagal'), 'error');
        }
    } catch (e) {
        showToast('✗ Tidak bisa terhubung ke server', 'error');
    }
}

function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = type === 'success' ? '#16A34A'
                       : type === 'error'   ? '#DC2626'
                       : '#1D4ED8';
    t.style.color = '#fff';
    t.classList.remove('hidden');
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.add('hidden'), 4000);
}

// ─── Modal Create/Edit ────────────────────────────────────────────────
function setCheckedEvents(events) {
    document.querySelectorAll('.event-check').forEach(cb => {
        cb.checked = events.includes(cb.value);
    });
    // Default: density.alert
    if (!events.length) {
        const el = document.querySelector('.event-check[value="density.alert"]');
        if (el) el.checked = true;
    }
}

function getCheckedEvents() {
    return [...document.querySelectorAll('.event-check:checked')].map(cb => cb.value);
}

function generateSecret() {
    const arr = new Uint8Array(16);
    crypto.getRandomValues(arr);
    document.getElementById('form-secret').value = [...arr].map(b => b.toString(16).padStart(2,'0')).join('');
}

function openCreate() {
    editingId = null;
    document.getElementById('modal-title').textContent = 'Tambah Webhook';
    document.getElementById('form-name').value = '';
    document.getElementById('form-url').value = '';
    document.getElementById('form-secret').value = '';
    setCheckedEvents(['density.alert']);
    clearFormErrors();
    openModal('crud-modal');
    setTimeout(() => document.getElementById('form-name').focus(), 50);
}

function openEdit(id) {
    const it = items.find(x => x.id === id);
    if (!it) return;
    editingId = id;
    document.getElementById('modal-title').textContent = 'Edit Webhook';
    document.getElementById('form-name').value = it.name;
    document.getElementById('form-url').value = it.url;
    document.getElementById('form-secret').value = '';  // Tidak expose secret
    setCheckedEvents(it.events ?? ['density.alert']);
    clearFormErrors();
    openModal('crud-modal');
}

async function submitForm(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    btn.disabled = true; btn.textContent = 'Menyimpan...';
    clearFormErrors();

    const events = getCheckedEvents();
    if (!events.length) {
        document.getElementById('error-events').textContent = 'Pilih minimal satu event.';
        btn.disabled = false; btn.textContent = 'Simpan';
        return;
    }

    const body = {
        name:   document.getElementById('form-name').value.trim(),
        url:    document.getElementById('form-url').value.trim(),
        events,
        secret: document.getElementById('form-secret').value.trim() || undefined,
    };
    if (!body.secret) delete body.secret;

    const url    = editingId ? `${API_BASE}/${editingId}` : API_BASE;
    const method = editingId ? 'PUT' : 'POST';

    try {
        const res  = await fetchAuth(url, { method, body: JSON.stringify(body) });
        const json = await res.json();
        if (!json.success) {
            if (res.status === 422 && json.error?.details) showValidationErrors(json.error.details);
            else alert(json.error?.message || 'Terjadi kesalahan');
            return;
        }
        closeModal('crud-modal');
        await loadList();
    } catch (e) { alert('Tidak bisa terhubung ke server.'); }
    finally { btn.disabled = false; btn.textContent = 'Simpan'; }
}

// ─── Delete ──────────────────────────────────────────────────────────
function confirmDelete(id) {
    const it = items.find(x => x.id === id);
    if (!it) return;
    showConfirm(`Hapus webhook "${it.name}"?`,
        'Endpoint ini tidak akan menerima notifikasi lagi.',
        () => doDelete(id)
    );
}

async function doDelete(id) {
    try {
        const res  = await fetchAuth(`${API_BASE}/${id}`, { method: 'DELETE' });
        const json = await res.json();
        if (!json.success) { alert(json.error?.message); return; }
        await loadList();
    } catch (e) { alert('Tidak bisa terhubung ke server.'); }
}

loadList();
</script>
@endpush
