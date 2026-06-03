@extends('operator.layouts.app')

@section('title', 'Armada · Operator')
@section('page-title', 'Armada')
@section('page-subtitle', 'Kelola armada bus')

@php $activePage = 'vehicles'; @endphp

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
        <input id="search" type="text" placeholder="Cari nomor plat..."
               class="input has-icon" oninput="onSearchInput()">
    </div>
    <select id="filter-route" class="select" style="max-width: 180px" onchange="loadList(1)">
        <option value="">Semua koridor</option>
    </select>
    <select id="filter-status" class="select" style="max-width: 140px" onchange="loadList(1)">
        <option value="">Semua status</option>
        <option value="active">Aktif</option>
        <option value="inactive">Non-aktif</option>
        <option value="maintenance">Servis</option>
    </select>
    <div style="flex: 1"></div>
    <button onclick="openCreate()" class="btn btn-primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Tambah Armada
    </button>
</div>

{{-- ─── Table card ──────────────────────────────────────────── --}}
<div class="bg-white border" style="border-color: var(--ink-100); border-radius: 14px; overflow: hidden">
    <div id="loading" class="text-center" style="padding: 60px 20px; color: var(--ink-400); font-size: 13.5px">
        <div class="inline-block animate-spin rounded-full border-2 border-current border-r-transparent"
             style="width: 20px; height: 20px; margin-bottom: 10px"></div>
        <div>Memuat data...</div>
    </div>
    <div id="error" class="hidden text-center" style="padding: 40px 20px">
        <p class="font-semibold" style="color: var(--c-high); margin-bottom: 8px" id="error-msg">Gagal memuat</p>
        <button onclick="loadList(1)" class="text-sm underline" style="color: var(--c-high)">Coba lagi</button>
    </div>
    <div id="empty" class="hidden text-center" style="padding: 60px 20px; color: var(--ink-400); font-size: 13.5px">
        Belum ada armada yang cocok dengan filter.
    </div>

    <div id="table-wrapper" class="hidden" style="overflow-x: auto">
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Plat Nomor</th>
                    <th>Koridor</th>
                    <th>Kapasitas</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="table-body"></tbody>
        </table>
    </div>

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
            <div class="modal-title" id="modal-title">Tambah Armada</div>
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
                    <label class="label label-required" for="form-route_id">Koridor</label>
                    <select id="form-route_id" class="select">
                        <option value="">— Pilih koridor —</option>
                    </select>
                    <div id="error-route_id" class="field-error"></div>
                </div>

                <div class="field">
                    <label class="label label-required" for="form-plate_number">Plat Nomor</label>
                    <input id="form-plate_number" type="text" maxlength="20" class="input mono"
                           placeholder="B 7001 TRN" style="text-transform: uppercase">
                    <div id="error-plate_number" class="field-error"></div>
                </div>

                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 12px">
                    <div class="field">
                        <label class="label label-required" for="form-capacity">Kapasitas</label>
                        <input id="form-capacity" type="number" min="1" max="500" class="input num"
                               placeholder="60">
                        <div id="error-capacity" class="field-error"></div>
                    </div>
                    <div class="field">
                        <label class="label label-required" for="form-status">Status</label>
                        <select id="form-status" class="select">
                            <option value="active">Aktif</option>
                            <option value="inactive">Non-aktif</option>
                            <option value="maintenance">Servis</option>
                        </select>
                        <div id="error-status" class="field-error"></div>
                    </div>
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
const API_BASE = '/api/v1/admin/vehicles';
const ROUTES_API = '/api/v1/admin/routes';
let items = [];
let routes = [];          // semua koridor untuk dropdown
let routesById = {};       // lookup cepat: id -> route object
let currentPage = 1;
let lastPage = 1;
let total = 0;
let perPage = 20;
let editingId = null;
let searchTerm = '';
let searchTimer;

const STATUS_META = {
    active:      { label: 'Aktif',     pill: 'pill-low'  },
    inactive:    { label: 'Non-aktif', pill: 'pill-neutral' },
    maintenance: { label: 'Servis',    pill: 'pill-med'  },
};

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

// ─── Load koridor (untuk dropdown + lookup) ────────────────────────
async function loadRoutes() {
    try {
        const res = await fetchAuth(`${ROUTES_API}?per_page=200`);
        const json = await res.json();
        if (!json.success) return;
        routes = json.data;
        routesById = Object.fromEntries(routes.map(r => [r.id, r]));

        // Populate kedua dropdown
        const filterOpts = '<option value="">Semua koridor</option>' +
            routes.map(r => `<option value="${r.id}">${escapeHtml(r.code)} · ${escapeHtml(r.name)}</option>`).join('');
        document.getElementById('filter-route').innerHTML = filterOpts;

        const formOpts = '<option value="">— Pilih koridor —</option>' +
            routes.map(r => `<option value="${r.id}">${escapeHtml(r.code)} · ${escapeHtml(r.name)}</option>`).join('');
        document.getElementById('form-route_id').innerHTML = formOpts;
    } catch (e) {
        console.warn('Gagal load koridor:', e);
    }
}

// ─── Load list ─────────────────────────────────────────────────────
async function loadList(page = 1) {
    if (page < 1 || (lastPage > 0 && page > lastPage)) return;
    setState('loading');
    try {
        const params = new URLSearchParams({ page, per_page: perPage });
        if (searchTerm) params.set('search', searchTerm);
        const routeFilter = document.getElementById('filter-route').value;
        if (routeFilter) params.set('route_id', routeFilter);
        const statusFilter = document.getElementById('filter-status').value;
        if (statusFilter) params.set('status', statusFilter);

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
        const r = routesById[it.route_id];
        const corridorBadge = r ? `
            <div class="flex items-center" style="gap: 8px">
                <div class="font-extrabold text-white flex items-center justify-center flex-shrink-0"
                     style="width: 24px; height: 24px; border-radius: 6px; background: ${r.color};
                            font-size: 10px; letter-spacing: -0.5px;
                            box-shadow: inset 0 -2px 0 rgba(0,0,0,0.15)">
                    ${escapeHtml(r.code)}
                </div>
                <span style="font-size: 12.5px; color: var(--ink-500)">${escapeHtml(r.name)}</span>
            </div>
        ` : `<span style="color: var(--ink-400); font-size: 12px">—</span>`;

        const status = STATUS_META[it.status] || STATUS_META.inactive;
        const statusPill = `
            <span class="pill ${status.pill}">
                <span class="pill-dot" style="background: currentColor"></span>
                ${status.label}
            </span>
        `;

        return `
            <tr>
                <td><span class="font-bold mono">${escapeHtml(it.plate_number)}</span></td>
                <td>${corridorBadge}</td>
                <td><span class="num">${it.capacity}</span></td>
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
                                <path d="M10 11v6"/><path d="M14 11v6"/>
                                <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    document.getElementById('pagination-info').textContent =
        `Menampilkan ${items.length} dari ${total} armada · halaman ${currentPage} dari ${lastPage}`;
    document.getElementById('prev-btn').disabled = currentPage <= 1;
    document.getElementById('next-btn').disabled = currentPage >= lastPage;
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        searchTerm = document.getElementById('search').value.trim();
        loadList(1);
    }, 350);
}

// ─── Modal ─────────────────────────────────────────────────────────
function openCreate() {
    editingId = null;
    document.getElementById('modal-title').textContent = 'Tambah Armada';
    document.getElementById('form-route_id').value = '';
    document.getElementById('form-plate_number').value = '';
    document.getElementById('form-capacity').value = '60';
    document.getElementById('form-status').value = 'active';
    clearFormErrors();
    openModal('crud-modal');
    setTimeout(() => document.getElementById('form-route_id').focus(), 50);
}

function openEdit(id) {
    const it = items.find(x => x.id === id);
    if (!it) return;
    editingId = id;
    document.getElementById('modal-title').textContent = `Edit Armada ${it.plate_number}`;
    document.getElementById('form-route_id').value = it.route_id;
    document.getElementById('form-plate_number').value = it.plate_number;
    document.getElementById('form-capacity').value = it.capacity;
    document.getElementById('form-status').value = it.status;
    clearFormErrors();
    openModal('crud-modal');
}

async function submitForm(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';
    clearFormErrors();

    const body = {
        route_id:     parseInt(document.getElementById('form-route_id').value, 10),
        plate_number: document.getElementById('form-plate_number').value.trim().toUpperCase(),
        capacity:     parseInt(document.getElementById('form-capacity').value, 10),
        status:       document.getElementById('form-status').value,
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
        `Hapus armada ${it.plate_number}?`,
        `Tindakan ini permanen. Pastikan tidak ada riwayat data sensor — atau set status ke "Non-aktif" untuk menonaktifkan tanpa menghapus.`,
        () => doDelete(id, it.plate_number)
    );
}

async function doDelete(id, plate) {
    try {
        const res = await fetchAuth(`${API_BASE}/${id}`, { method: 'DELETE' });
        const json = await res.json();
        if (!json.success) {
            if (json.error?.code === 'HAS_DEPENDENCIES') {
                const d = json.error.details || {};
                alert(
                    `Tidak bisa menghapus armada ${plate}.\n\n` +
                    `Masih ada: ${d.density_logs_count ?? 0} log sensor, ${d.forecasts_count ?? 0} prediksi.\n\n` +
                    (d.hint || '')
                );
            } else {
                alert(json.error?.message || 'Gagal menghapus');
            }
            return;
        }
        const newPage = (items.length === 1 && currentPage > 1) ? currentPage - 1 : currentPage;
        await loadList(newPage);
    } catch (e) {
        alert('Tidak bisa terhubung ke server.');
    }
}

// ─── Boot ──────────────────────────────────────────────────────────
(async () => {
    await loadRoutes();
    await loadList(1);
})();
</script>
@endpush
