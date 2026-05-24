@extends('operator.layouts.app')

@section('title', 'Koridor · Operator')
@section('page-title', 'Koridor')
@section('page-subtitle', 'Kelola koridor TransJakarta')

@php $activePage = 'routes'; @endphp

@section('content')

{{-- ─── Toolbar ─────────────────────────────────────────────── --}}
<div class="flex items-center mb-3.5" style="gap: 10px">
    <div class="relative flex items-center" style="flex: 1; max-width: 320px">
        <span class="absolute" style="left: 12px; color: var(--ink-400); pointer-events: none">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"/>
                <path d="m20 20-3.5-3.5"/>
            </svg>
        </span>
        <input id="search" type="text" placeholder="Cari kode atau nama koridor..."
               class="input has-icon" oninput="onSearchInput()">
    </div>
    <div style="flex: 1"></div>
    <button onclick="openCreate()" class="btn btn-primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Tambah Koridor
    </button>
</div>

{{-- ─── Table card ──────────────────────────────────────────── --}}
<div class="bg-white border" style="border-color: var(--ink-100); border-radius: 14px; overflow: hidden">
    {{-- Loading state --}}
    <div id="loading" class="text-center" style="padding: 60px 20px; color: var(--ink-400); font-size: 13.5px">
        <div class="inline-block animate-spin rounded-full border-2 border-current border-r-transparent"
             style="width: 20px; height: 20px; margin-bottom: 10px"></div>
        <div>Memuat data...</div>
    </div>

    {{-- Error state --}}
    <div id="error" class="hidden text-center" style="padding: 40px 20px">
        <p class="font-semibold" style="color: var(--c-high); margin-bottom: 8px" id="error-msg">Gagal memuat</p>
        <button onclick="loadList(1)" class="text-sm underline" style="color: var(--c-high)">Coba lagi</button>
    </div>

    {{-- Empty state --}}
    <div id="empty" class="hidden text-center" style="padding: 60px 20px; color: var(--ink-400); font-size: 13.5px">
        Belum ada koridor. Klik "Tambah Koridor" untuk membuat.
    </div>

    {{-- Table --}}
    <div id="table-wrapper" class="hidden" style="overflow-x: auto">
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Halte</th>
                    <th>Armada</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="table-body"></tbody>
        </table>
    </div>

    {{-- Pagination footer --}}
    <div id="pagination" class="hidden flex items-center justify-between"
         style="padding: 12px 16px; border-top: 1px solid var(--ink-100); font-size: 12px; color: var(--ink-500)">
        <div id="pagination-info">—</div>
        <div class="flex" style="gap: 6px">
            <button id="prev-btn" onclick="loadList(currentPage - 1)" class="btn btn-outline" style="padding: 5px 10px; font-size: 12px">‹</button>
            <button id="next-btn" onclick="loadList(currentPage + 1)" class="btn btn-outline" style="padding: 5px 10px; font-size: 12px">›</button>
        </div>
    </div>
</div>

{{-- ─── Create/Edit Modal ───────────────────────────────────── --}}
<div id="crud-modal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-title">Tambah Koridor</div>
            <button onclick="closeModal('crud-modal')" class="icon-btn" aria-label="Tutup">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form id="crud-form" onsubmit="submitForm(event)">
            <div class="modal-body">
                <div class="field">
                    <label class="label label-required" for="form-code">Kode</label>
                    <input id="form-code" type="text" maxlength="10" class="input" placeholder="K5">
                    <div id="error-code" class="field-error"></div>
                </div>

                <div class="field">
                    <label class="label label-required" for="form-name">Nama</label>
                    <input id="form-name" type="text" maxlength="100" class="input" placeholder="Mis. Blok M — Kota">
                    <div id="error-name" class="field-error"></div>
                </div>

                <div class="field">
                    <label class="label label-required" for="form-color">Warna Penanda</label>
                    <div class="flex items-center" style="gap: 8px; margin-bottom: 8px" id="color-swatches">
                        @foreach (['#E11D2A', '#0EA5E9', '#16A34A', '#9333EA', '#D97706', '#0F172A'] as $c)
                            <button type="button" class="color-swatch"
                                    data-color="{{ $c }}" style="background: {{ $c }}"
                                    onclick="selectColor('{{ $c }}')"></button>
                        @endforeach
                    </div>
                    <input id="form-color" type="text" pattern="^#[0-9A-Fa-f]{6}$" class="input mono"
                           placeholder="#E11D2A" oninput="syncSwatchSelection()">
                    <div id="error-color" class="field-error"></div>
                </div>

                <div class="field">
                    <label class="flex items-center" style="gap: 8px; cursor: pointer; font-size: 14px">
                        <input id="form-is_active" type="checkbox" checked
                               style="width: 16px; height: 16px; accent-color: var(--brand)">
                        <span>Aktif (kalau dimatikan, koridor tidak muncul di app penumpang)</span>
                    </label>
                    <div id="error-is_active" class="field-error"></div>
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
const API_BASE = '/api/v1/admin/routes';
let items = [];
let currentPage = 1;
let lastPage = 1;
let total = 0;
let perPage = 20;
let editingId = null;
let searchTerm = '';
let searchTimer;

function setState(active) {
    ['loading', 'error', 'empty', 'table-wrapper', 'pagination'].forEach(id => {
        document.getElementById(id).classList.add('hidden');
    });
    if (active === 'data') {
        document.getElementById('table-wrapper').classList.remove('hidden');
        document.getElementById('pagination').classList.remove('hidden');
        document.getElementById('pagination').classList.add('flex');
    } else {
        document.getElementById(active)?.classList.remove('hidden');
    }
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
}

// ─── Load list ─────────────────────────────────────────────────────
async function loadList(page = 1) {
    if (page < 1 || (lastPage > 0 && page > lastPage)) return;
    setState('loading');
    try {
        const params = new URLSearchParams({ page, per_page: perPage });
        if (searchTerm) params.set('search', searchTerm);
        const res = await fetchAuth(`${API_BASE}?${params}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.error?.message || 'API gagal');

        items = json.data;
        const pag = json.meta?.pagination || {};
        currentPage = pag.page || 1;
        lastPage = pag.last_page || 1;
        total = pag.total || items.length;
        perPage = pag.per_page || perPage;

        renderTable();
        setState(items.length === 0 ? 'empty' : 'data');
    } catch (e) {
        document.getElementById('error-msg').textContent = e.message;
        setState('error');
    }
}

function renderTable() {
    const tbody = document.getElementById('table-body');
    tbody.innerHTML = items.map(it => {
        const statusPill = it.is_active
            ? `<span class="pill pill-low"><span class="pill-dot" style="background: var(--c-low)"></span>Aktif</span>`
            : `<span class="pill pill-neutral"><span class="pill-dot" style="background: var(--ink-300)"></span>Non-aktif</span>`;
        return `
            <tr>
                <td>
                    <div class="flex items-center" style="gap: 10px">
                        <div class="font-extrabold text-white flex items-center justify-center flex-shrink-0"
                             style="width: 28px; height: 28px; border-radius: 7px; background: ${it.color};
                                    font-size: 11px; letter-spacing: -0.5px;
                                    box-shadow: inset 0 -2px 0 rgba(0,0,0,0.15)">
                            ${escapeHtml(it.code)}
                        </div>
                        <span class="font-bold mono">${escapeHtml(it.code)}</span>
                    </div>
                </td>
                <td><span style="font-weight: 600">${escapeHtml(it.name)}</span></td>
                <td><span class="num">${it.stops_count ?? '?'}</span></td>
                <td><span class="num">${it.vehicles_count ?? '?'}</span></td>
                <td>${statusPill}</td>
                <td>
                    <div class="flex" style="gap: 4px">
                        <button onclick="openEdit(${it.id})" class="icon-btn" title="Edit">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>
                        <button onclick="confirmDeleteItem(${it.id})" class="icon-btn danger" title="Hapus">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                <path d="M10 11v6"/>
                                <path d="M14 11v6"/>
                                <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    document.getElementById('pagination-info').textContent =
        `Menampilkan ${items.length} dari ${total} koridor · halaman ${currentPage} dari ${lastPage}`;
    document.getElementById('prev-btn').disabled = currentPage <= 1;
    document.getElementById('next-btn').disabled = currentPage >= lastPage;
}

// ─── Search (debounced) ────────────────────────────────────────────
function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        searchTerm = document.getElementById('search').value.trim();
        loadList(1);
    }, 350);
}

// ─── Modal Create/Edit ─────────────────────────────────────────────
function openCreate() {
    editingId = null;
    document.getElementById('modal-title').textContent = 'Tambah Koridor';
    document.getElementById('form-code').value = '';
    document.getElementById('form-name').value = '';
    document.getElementById('form-color').value = '#E11D2A';
    document.getElementById('form-is_active').checked = true;
    syncSwatchSelection();
    clearFormErrors();
    openModal('crud-modal');
    setTimeout(() => document.getElementById('form-code').focus(), 50);
}

function openEdit(id) {
    const it = items.find(x => x.id === id);
    if (!it) return;
    editingId = id;
    document.getElementById('modal-title').textContent = `Edit Koridor ${it.code}`;
    document.getElementById('form-code').value = it.code;
    document.getElementById('form-name').value = it.name;
    document.getElementById('form-color').value = it.color;
    document.getElementById('form-is_active').checked = !!it.is_active;
    syncSwatchSelection();
    clearFormErrors();
    openModal('crud-modal');
}

function selectColor(c) {
    document.getElementById('form-color').value = c;
    syncSwatchSelection();
}

function syncSwatchSelection() {
    const current = document.getElementById('form-color').value.toLowerCase();
    document.querySelectorAll('#color-swatches .color-swatch').forEach(s => {
        s.classList.toggle('selected', s.dataset.color.toLowerCase() === current);
    });
}

async function submitForm(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';
    clearFormErrors();

    const body = {
        code:      document.getElementById('form-code').value.trim(),
        name:      document.getElementById('form-name').value.trim(),
        color:     document.getElementById('form-color').value.trim(),
        is_active: document.getElementById('form-is_active').checked,
    };

    const url = editingId ? `${API_BASE}/${editingId}` : API_BASE;
    const method = editingId ? 'PUT' : 'POST';

    try {
        const res = await fetchAuth(url, { method, body: JSON.stringify(body) });
        const json = await res.json();
        if (!json.success) {
            if (res.status === 422 && json.error?.details) {
                showValidationErrors(json.error.details);
            } else {
                alert(json.error?.message || 'Terjadi kesalahan');
            }
            return;
        }
        closeModal('crud-modal');
        await loadList(editingId ? currentPage : 1);
    } catch (e) {
        alert('Tidak bisa terhubung ke server.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan';
    }
}

// ─── Delete ─────────────────────────────────────────────────────────
function confirmDeleteItem(id) {
    const it = items.find(x => x.id === id);
    if (!it) return;
    showConfirm(
        `Hapus koridor ${it.code}?`,
        `Tindakan ini permanen. Pastikan tidak ada halte atau armada yang masih terkait — atau gunakan toggle "Aktif" untuk menonaktifkan tanpa menghapus.`,
        () => doDelete(id, it.code)
    );
}

async function doDelete(id, code) {
    try {
        const res = await fetchAuth(`${API_BASE}/${id}`, { method: 'DELETE' });
        const json = await res.json();
        if (!json.success) {
            // Tampilkan pesan khusus untuk dependency error
            if (json.error?.code === 'HAS_DEPENDENCIES') {
                const d = json.error.details || {};
                alert(
                    `Tidak bisa menghapus koridor ${code}.\n\n` +
                    `Masih ada: ${d.stops_count ?? 0} halte, ${d.vehicles_count ?? 0} armada.\n\n` +
                    (d.hint || '')
                );
            } else {
                alert(json.error?.message || 'Gagal menghapus');
            }
            return;
        }
        // Kalau hapus item terakhir di halaman, mundur satu halaman
        const newPage = (items.length === 1 && currentPage > 1) ? currentPage - 1 : currentPage;
        await loadList(newPage);
    } catch (e) {
        alert('Tidak bisa terhubung ke server.');
    }
}

// ─── Boot ──────────────────────────────────────────────────────────
loadList(1);
</script>
@endpush
