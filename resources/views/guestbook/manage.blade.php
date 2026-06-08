@extends('guestbook.layout')

@section('content')
<div class="guestbook-manage-wrap">
    <div class="manage-header">
        <h1>Kelola Pendopo</h1>
        <p class="manage-subtitle">Kelola data tamu, hapus entri, dan atur pengaturan sistem</p>
    </div>

    <div class="manage-stats-grid">
        <div class="manage-stat-card">
            <span class="manage-stat-label">Total Tamu</span>
            <strong class="manage-stat-value">{{ number_format($stats['all'] ?? 0) }}</strong>
        </div>
        <div class="manage-stat-card">
            <span class="manage-stat-label">Hari Ini</span>
            <strong class="manage-stat-value">{{ number_format($stats['day'] ?? 0) }}</strong>
        </div>
        <div class="manage-stat-card">
            <span class="manage-stat-label">Minggu Ini</span>
            <strong class="manage-stat-value">{{ number_format($stats['week'] ?? 0) }}</strong>
        </div>
        <div class="manage-stat-card">
            <span class="manage-stat-label">Bulan Ini</span>
            <strong class="manage-stat-value">{{ number_format($stats['month'] ?? 0) }}</strong>
        </div>
        <div class="manage-stat-card">
            <span class="manage-stat-label">Tahun Ini</span>
            <strong class="manage-stat-value">{{ number_format($stats['year'] ?? 0) }}</strong>
        </div>
    </div>

    <div class="manage-container">
        <!-- Entries Management Section -->
        <div class="manage-section manage-entries">
            <div class="section-header">
                <h2>Daftar Data Tamu</h2>
                <div class="section-controls">
                    <input type="text" id="search-entries" class="search-input" placeholder="Cari nama atau instansi...">
                </div>
            </div>

            @if($entries->isNotEmpty())
                <div class="entries-table-wrap">
                    <table class="entries-manage-table">
                        <thead>
                            <tr>
                                <th class="col-checkbox">
                                    <input type="checkbox" id="select-all" aria-label="Pilih semua">
                                </th>
                                <th class="col-no">No</th>
                                <th class="col-name">Nama</th>
                                <th class="col-institution">Instansi</th>
                                <th class="col-purpose">Keperluan</th>
                                <th class="col-date">Tanggal</th>
                                <th class="col-actions">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="entries-tbody">
                            @php $no = ($entries->currentPage() - 1) * $entries->perPage() + 1; @endphp
                            @foreach($entries as $entry)
                                <tr class="entry-row" data-entry-id="{{ $entry->id }}" data-entry-name="{{ $entry->name }}" data-entry-institution="{{ $entry->institution }}" data-delete-url="{{ route('lawangsewu.guestbook.destroy', $entry->id) }}">
                                    <td class="col-checkbox">
                                        <input type="checkbox" class="entry-checkbox" value="{{ $entry->id }}" aria-label="Pilih {{ $entry->name }}">
                                    </td>
                                    <td class="col-no">{{ $no++ }}</td>
                                    <td class="col-name">
                                        <span class="entry-name">{{ $entry->name }}</span>
                                        <span class="entry-position">{{ $entry->position }}</span>
                                    </td>
                                    <td class="col-institution">{{ $entry->institution }}</td>
                                    <td class="col-purpose">{{ Str::limit($entry->purpose, 40) }}</td>
                                    <td class="col-date">{{ $entry->checkin?->format('d M Y H:i') ?? '-' }}</td>
                                    <td class="col-actions">
                                        <div class="action-buttons">
                                            <a href="{{ route('lawangsewu.guestbook.detail', $entry->id) }}" class="btn-action btn-detail" title="Lihat detail">
                                                <span>👁️</span>
                                            </a>
                                            @if(auth()->user()?->isSuperAdmin())
                                                <button type="button" class="btn-action btn-edit" data-entry-id="{{ $entry->id }}" data-entry-name="{{ $entry->name }}" data-entry-institution="{{ $entry->institution }}" title="Edit nama dan instansi">
                                                    <span>✏️</span>
                                                </button>
                                            @endif
                                            <button type="button" class="btn-action btn-delete" data-entry-id="{{ $entry->id }}" title="Hapus entri ini">
                                                <span>🗑️</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Bulk Actions -->
                <div class="bulk-actions-bar" id="bulk-actions" style="display: none;">
                    <div class="bulk-info">
                        <span id="selected-count">0</span> tamu dipilih
                    </div>
                    <div class="bulk-buttons">
                        <button type="button" class="btn btn-danger" id="bulk-delete-btn">
                            Hapus yang Dipilih
                        </button>
                        <button type="button" class="btn btn-secondary" id="bulk-cancel-btn">
                            Batalkan
                        </button>
                    </div>
                </div>

                <!-- Pagination -->
                @if($entries->hasPages())
                    <div class="manage-pagination-wrap">
                        <nav class="manage-pagination" aria-label="Navigasi halaman">
                            <!-- Previous -->
                            @if($entries->onFirstPage())
                                <span class="page-link disabled">← Sblm</span>
                            @else
                                <a href="{{ $entries->previousPageUrl() }}" class="page-link">← Sblm</a>
                            @endif

                            <!-- Page numbers -->
                            @php
                                $current = $entries->currentPage();
                                $total = $entries->lastPage();
                                $window = 2;
                            @endphp

                            @if($total <= 1)
                                <!-- No pagination needed -->
                            @else
                                <!-- First page -->
                                @if($current > $window + 1)
                                    <a href="{{ $entries->url(1) }}" class="page-link">1</a>
                                @endif

                                <!-- Ellipsis -->
                                @if($current > $window + 2)
                                    <span class="page-link disabled">...</span>
                                @endif

                                <!-- Window pages -->
                                @foreach(range(max(1, $current - $window), min($total, $current + $window)) as $page)
                                    @if($page == $current)
                                        <span class="page-link active">{{ $page }}</span>
                                    @else
                                        <a href="{{ $entries->url($page) }}" class="page-link">{{ $page }}</a>
                                    @endif
                                @endforeach

                                <!-- Ellipsis -->
                                @if($current < $total - $window - 1)
                                    <span class="page-link disabled">...</span>
                                @endif

                                <!-- Last page -->
                                @if($current < $total - $window)
                                    <a href="{{ $entries->url($total) }}" class="page-link">{{ $total }}</a>
                                @endif
                            @endif

                            <!-- Next -->
                            @if($entries->hasMorePages())
                                <a href="{{ $entries->nextPageUrl() }}" class="page-link">Brkt →</a>
                            @else
                                <span class="page-link disabled">Brkt →</span>
                            @endif
                        </nav>
                    </div>
                @endif
            @else
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <p>Belum ada data tamu yang terdaftar.</p>
                </div>
            @endif
        </div>

        <!-- Settings Section -->
        <div class="manage-section manage-settings">
            <div class="section-header">
                <h2>Pengaturan Pendopo</h2>
            </div>

            <form id="settings-form" class="settings-form">
                @csrf
                <div class="form-group">
                    <label for="event_name">Nama Acara/Pendopo</label>
                    <input type="text" id="event_name" name="event_name" class="form-control" 
                           value="{{ old('event_name', $settings->event_name) }}" 
                           placeholder="Contoh: Pendopo Pengadilan Agama Semarang" required>
                    <small class="form-text">Nama ini akan ditampilkan di bagian atas halaman pendaftaran</small>
                </div>

                <div class="form-group">
                    <label for="per_page">Entri per Halaman</label>
                    <input type="number" id="per_page" name="per_page" class="form-control" 
                           value="{{ old('per_page', $settings->per_page) }}" 
                           min="5" max="50" required>
                    <small class="form-text">Jumlah data tamu yang ditampilkan per halaman (5-50)</small>
                </div>

                <div class="form-group form-checkbox">
                    <input type="checkbox" id="require_identity_fields" name="require_identity_fields" 
                           class="form-check" value="1" 
                           @checked($settings->require_identity_fields)>
                    <label for="require_identity_fields">Wajibkan Data Identitas Lengkap</label>
                    <small class="form-text">Jika diaktifkan, tamu harus mengisi semua field identitas</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        💾 Simpan Pengaturan
                    </button>
                    <span id="save-status" class="save-status"></span>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="modal" style="display: none;">
    <div class="modal-content modal-confirm">
        <div class="modal-header">
            <h3>Konfirmasi Penghapusan</h3>
        </div>
        <div class="modal-body">
            <p id="delete-message">Data tamu akan dihapus secara permanen. Lanjutkan?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="modal-cancel">Batalkan</button>
            <button type="button" class="btn btn-danger" id="modal-confirm">Hapus</button>
        </div>
    </div>
</div>

<!-- Edit Info Modal -->
<div id="edit-modal" class="modal" style="display: none;">
    <div class="modal-content modal-form">
        <div class="modal-header">
            <h3>Edit Data Tamu</h3>
            <small class="modal-subtitle">Hanya nama dan asal instansi yang dapat diubah</small>
        </div>
        <form id="edit-form" class="modal-form-content">
            @csrf
            <div class="form-group">
                <label for="edit-name">Nama Tamu</label>
                <input type="text" id="edit-name" name="nama" class="form-control" placeholder="Nama lengkap tamu" required maxlength="120">
                <span class="form-error" id="edit-name-error"></span>
            </div>
            <div class="form-group">
                <label for="edit-institution">Asal Instansi</label>
                <input type="text" id="edit-institution" name="instansi" class="form-control" placeholder="Nama instansi atau organisasi" required maxlength="160">
                <span class="form-error" id="edit-institution-error"></span>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="edit-cancel">Batalkan</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<style>
.guestbook-manage-wrap {
    padding: 2rem 1rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #f0f3f7 100%);
    min-height: 100vh;
}

.manage-header {
    margin-bottom: 2rem;
    text-align: center;
}

.manage-header h1 {
    font-size: 2rem;
    color: #1a1a2e;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.manage-subtitle {
    color: #6b7280;
    font-size: 0.95rem;
}

.manage-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
}

.manage-stats-grid {
    max-width: 1200px;
    margin: 0 auto 1.25rem;
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.75rem;
}

.manage-stat-card {
    background: linear-gradient(145deg, #ffffff 0%, #f8fbff 100%);
    border: 1px solid #e5edf8;
    border-radius: 0.9rem;
    padding: 0.9rem 1rem;
    box-shadow: 0 2px 8px rgba(26, 26, 46, 0.06);
}

.manage-stat-label {
    display: block;
    color: #64748b;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 700;
}

.manage-stat-value {
    display: block;
    margin-top: 0.3rem;
    color: #1a1a2e;
    font-size: 1.35rem;
    line-height: 1.1;
}

.manage-section {
    background: white;
    border-radius: 1.2rem;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.section-header h2 {
    font-size: 1.4rem;
    color: #1a1a2e;
    margin: 0;
    font-weight: 600;
}

.section-controls {
    flex: 1;
    min-width: 250px;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.6rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.entries-table-wrap {
    overflow-x: auto;
    margin-bottom: 1.5rem;
    border-radius: 0.8rem;
    border: 1px solid #e5e7eb;
}

.entries-manage-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.entries-manage-table thead {
    background: linear-gradient(90deg, #2d3748 0%, #1a1a2e 100%);
    color: white;
}

.entries-manage-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.entries-manage-table td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.entry-row {
    transition: background-color 0.15s ease;
}

.entry-row:hover {
    background-color: #f9fafb;
}

.entry-row.selected {
    background-color: #eff6ff;
}

.col-checkbox {
    width: 40px;
    text-align: center;
}

.col-no {
    width: 50px;
    text-align: center;
    color: #9ca3af;
    font-size: 0.85rem;
}

.col-name {
    min-width: 150px;
}

.entry-name {
    display: block;
    font-weight: 600;
    color: #1a1a2e;
}

.entry-position {
    display: block;
    font-size: 0.8rem;
    color: #9ca3af;
    margin-top: 0.2rem;
}

.col-institution {
    min-width: 140px;
    color: #6b7280;
}

.col-purpose {
    min-width: 120px;
    color: #6b7280;
}

.col-date {
    min-width: 140px;
    color: #9ca3af;
    font-size: 0.85rem;
}

.col-actions {
    width: 100px;
    text-align: center;
}

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0;
    background: #f3f4f6;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    text-decoration: none;
    color: inherit;
}

.btn-action:hover {
    background: #e5e7eb;
    transform: translateY(-1px);
}

.btn-action.btn-detail:hover {
    background: #dbeafe;
}

.btn-action.btn-delete:hover {
    background: #fee2e2;
}

.bulk-actions-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 0.8rem;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

.bulk-info {
    color: #92400e;
    font-weight: 600;
}

.bulk-buttons {
    display: flex;
    gap: 0.75rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #9ca3af;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.manage-pagination-wrap {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.manage-pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 0.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    color: #4f46e5;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
}

.page-link:hover:not(.disabled):not(.active) {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.page-link.active {
    background: #4f46e5;
    color: white;
    border-color: #4f46e5;
}

.page-link.disabled {
    color: #d1d5db;
    cursor: not-allowed;
    background: #f9fafb;
}

.settings-form {
    max-width: 500px;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #1a1a2e;
    font-weight: 600;
    font-size: 0.95rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.6rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.form-checkbox .form-check {
    width: auto;
    height: auto;
    margin-top: 0.2rem;
    cursor: pointer;
}

.form-checkbox label {
    margin: 0;
    cursor: pointer;
    font-weight: 500;
}

.form-text {
    display: block;
    margin-top: 0.3rem;
    color: #9ca3af;
    font-size: 0.85rem;
}

.form-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.6rem;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: #4f46e5;
    color: white;
}

.btn-primary:hover {
    background: #4338ca;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.btn-secondary {
    background: #e5e7eb;
    color: #1a1a2e;
}

.btn-secondary:hover {
    background: #d1d5db;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.save-status {
    font-size: 0.9rem;
    font-weight: 500;
}

.save-status.success {
    color: #059669;
}

.save-status.error {
    color: #dc2626;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    max-width: 400px;
    width: 90%;
    overflow: hidden;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}

.modal-header h3 {
    margin: 0;
    color: #1a1a2e;
    font-size: 1.2rem;
}

.modal-body {
    padding: 1.5rem;
    color: #6b7280;
    line-height: 1.6;
}

.modal-footer {
    padding: 1.5rem;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.modal-subtitle {
    display: block;
    color: #9ca3af;
    font-size: 0.85rem;
    font-weight: 400;
    margin-top: 0.3rem;
}

.modal-form-content {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.form-error {
    display: block;
    color: #dc2626;
    font-size: 0.85rem;
    margin-top: 0.3rem;
}

.btn-action.btn-edit {
    background: #dbeafe;
}

.btn-action.btn-edit:hover {
    background: #bfdbfe;
}

@media (max-width: 768px) {
    .manage-header h1 {
        font-size: 1.5rem;
    }

    .manage-stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .section-header {
        flex-direction: column;
        align-items: stretch;
    }

    .section-controls {
        min-width: auto;
    }

    .entries-manage-table {
        font-size: 0.8rem;
    }

    .entries-manage-table th,
    .entries-manage-table td {
        padding: 0.75rem 0.5rem;
    }

    .col-checkbox {
        width: 32px;
    }

    .col-no {
        display: none;
    }

    .entry-name {
        font-size: 0.9rem;
    }

    .entry-position {
        display: none;
    }

    .col-institution,
    .col-purpose {
        display: none;
    }

    .col-date {
        min-width: 100px;
        font-size: 0.75rem;
    }

    .col-actions {
        width: 80px;
    }

    .action-buttons {
        gap: 0.25rem;
    }

    .btn-action {
        width: 28px;
        height: 28px;
        font-size: 0.8rem;
    }

    .bulk-actions-bar {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }

    .bulk-buttons {
        justify-content: stretch;
    }

    .bulk-buttons .btn {
        flex: 1;
        justify-content: center;
    }

    .manage-pagination {
        gap: 0.25rem;
    }

    .page-link {
        min-width: 32px;
        height: 32px;
        font-size: 0.75rem;
    }
}

input[type="checkbox"] {
    cursor: pointer;
    width: 18px;
    height: 18px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const selectAllCheckbox = document.getElementById('select-all');
    const entryCheckboxes = document.querySelectorAll('.entry-checkbox');
    const bulkActionsBar = document.getElementById('bulk-actions');
    const selectedCountSpan = document.getElementById('selected-count');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const bulkCancelBtn = document.getElementById('bulk-cancel-btn');
    const deleteModal = document.getElementById('delete-modal');
    const modalMessage = document.getElementById('delete-message');
    const modalConfirmBtn = document.getElementById('modal-confirm');
    const modalCancelBtn = document.getElementById('modal-cancel');
    const editModal = document.getElementById('edit-modal');
    const editForm = document.getElementById('edit-form');
    const editNameInput = document.getElementById('edit-name');
    const editInstitutionInput = document.getElementById('edit-institution');
    const editCancelBtn = document.getElementById('edit-cancel');
    const settingsForm = document.getElementById('settings-form');
    const saveStatus = document.getElementById('save-status');
    const searchInput = document.getElementById('search-entries');

    let pendingDeleteAction = null;
    let pendingEditAction = null;

    // Select all functionality
    selectAllCheckbox?.addEventListener('change', function() {
        entryCheckboxes.forEach(cb => {
            cb.checked = this.checked;
            cb.closest('.entry-row').classList.toggle('selected', this.checked);
        });
        updateBulkActions();
    });

    // Individual checkbox changes
    entryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            this.closest('.entry-row').classList.toggle('selected', this.checked);
            updateSelectAll();
            updateBulkActions();
        });
    });

    // Update "select all" checkbox state
    function updateSelectAll() {
        const allChecked = Array.from(entryCheckboxes).every(cb => cb.checked);
        const someChecked = Array.from(entryCheckboxes).some(cb => cb.checked);
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = someChecked && !allChecked;
    }

    // Update bulk actions visibility
    function updateBulkActions() {
        const selected = Array.from(entryCheckboxes).filter(cb => cb.checked).length;
        selectedCountSpan.textContent = selected;
        bulkActionsBar.style.display = selected > 0 ? 'flex' : 'none';
    }

    // Edit buttons
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const entryId = this.dataset.entryId;
            const entryName = this.dataset.entryName;
            const entryInstitution = this.dataset.entryInstitution;
            
            editNameInput.value = entryName;
            editInstitutionInput.value = entryInstitution;
            clearEditErrors();
            
            pendingEditAction = { id: entryId };
            editModal.style.display = 'flex';
        });
    });

    // Clear edit form errors
    function clearEditErrors() {
        document.getElementById('edit-name-error').textContent = '';
        document.getElementById('edit-institution-error').textContent = '';
    }

    // Individual delete buttons
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const entryId = this.dataset.entryId;
            const row = this.closest('.entry-row');
            const entryName = row.querySelector('.entry-name').textContent;
            
            modalMessage.textContent = `Hapus data "${entryName}"? Tindakan ini tidak dapat dibatalkan.`;
            pendingDeleteAction = { type: 'single', ids: [entryId] };
            deleteModal.style.display = 'flex';
        });
    });

    // Bulk delete
    bulkDeleteBtn?.addEventListener('click', function() {
        const selected = Array.from(entryCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        
        if (selected.length === 0) return;

        modalMessage.textContent = `Hapus ${selected.length} data tamu? Tindakan ini tidak dapat dibatalkan.`;
        pendingDeleteAction = { type: 'bulk', ids: selected };
        deleteModal.style.display = 'flex';
    });

    // Modal actions
    modalConfirmBtn?.addEventListener('click', async function() {
        if (!pendingDeleteAction) return;

        const { type, ids } = pendingDeleteAction;

        try {
            if (type === 'single') {
                const deleteUrl = document.querySelector(`[data-entry-id="${ids[0]}"]`)?.dataset.deleteUrl || `/buku-tamu/kelola/${ids[0]}`;

                const response = await fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Gagal menghapus data');
                
                const data = await response.json();
                if (data.status === 'success') {
                    document.querySelector(`[data-entry-id="${ids[0]}"]`).remove();
                    alert(data.message);
                    location.reload();
                }
            } else {
                const response = await fetch('/buku-tamu/kelola/bulk-delete', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ ids })
                });

                if (!response.ok) throw new Error('Gagal menghapus data');
                
                const data = await response.json();
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload();
                }
            }
        } catch (error) {
            alert('Terjadi kesalahan: ' + error.message);
        } finally {
            deleteModal.style.display = 'none';
            pendingDeleteAction = null;
        }
    });

    [modalCancelBtn, bulkCancelBtn].forEach(btn => {
        btn?.addEventListener('click', function() {
            deleteModal.style.display = 'none';
            pendingDeleteAction = null;
        });
    });

    // Edit form submission
    editForm?.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!pendingEditAction) return;

        const { id } = pendingEditAction;
        const nama = editNameInput.value.trim();
        const instansi = editInstitutionInput.value.trim();

        if (!nama) {
            document.getElementById('edit-name-error').textContent = 'Nama wajib diisi.';
            return;
        }

        if (!instansi) {
            document.getElementById('edit-institution-error').textContent = 'Asal instansi wajib diisi.';
            return;
        }

        try {
            const response = await fetch(`/buku-tamu/kelola/${id}/update-info`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    nama: nama,
                    instansi: instansi
                })
            });

            const data = await response.json();

            if (!response.ok) {
                if (data.errors) {
                    if (data.errors.nama) {
                        document.getElementById('edit-name-error').textContent = data.errors.nama[0];
                    }
                    if (data.errors.instansi) {
                        document.getElementById('edit-institution-error').textContent = data.errors.instansi[0];
                    }
                }
                if (response.status === 403) {
                    alert(data.message || 'Anda tidak memiliki izin untuk mengubah data ini.');
                } else {
                    alert(data.message || 'Gagal mengubah data.');
                }
                return;
            }

            // Update table row
            const row = document.querySelector(`[data-entry-id="${id}"]`);
            if (row) {
                row.querySelector('.entry-name').textContent = data.name;
                row.dataset.entryName = data.name;
                row.querySelector('.col-institution').textContent = data.institution;
                row.dataset.entryInstitution = data.institution;
            }

            alert(data.message);
            editModal.style.display = 'none';
            pendingEditAction = null;
        } catch (error) {
            alert('Terjadi kesalahan: ' + error.message);
        }
    });

    // Edit cancel button
    editCancelBtn?.addEventListener('click', function() {
        editModal.style.display = 'none';
        pendingEditAction = null;
        clearEditErrors();
    });

    // Close modal on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (deleteModal.style.display !== 'none') {
                deleteModal.style.display = 'none';
                pendingDeleteAction = null;
            }
            if (editModal.style.display !== 'none') {
                editModal.style.display = 'none';
                pendingEditAction = null;
                clearEditErrors();
            }
        }
    });

    // Settings form
    settingsForm?.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        data.require_identity_fields = formData.has('require_identity_fields');

        try {
            const response = await fetch('{{ route("lawangsewu.guestbook.settings") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            saveStatus.textContent = result.message;
            saveStatus.classList.add(result.status === 'success' ? 'success' : 'error');
            
            setTimeout(() => {
                saveStatus.textContent = '';
                saveStatus.classList.remove('success', 'error');
            }, 3000);
        } catch (error) {
            saveStatus.textContent = 'Terjadi kesalahan: ' + error.message;
            saveStatus.classList.add('error');
        }
    });

    // Search functionality (client-side)
    searchInput?.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const rows = document.querySelectorAll('.entry-row');
        
        rows.forEach(row => {
            const name = row.dataset.entryName?.toLowerCase() || '';
            const institution = row.dataset.entryInstitution?.toLowerCase() || '';
            const matches = name.includes(query) || institution.includes(query);
            row.style.display = matches ? '' : 'none';
        });
    });
});
</script>
@endsection
