<style>
    :root {
        --pip-primary: #15803d;
        --pip-primary-hover: #166534;
        --pip-primary-light: #dcfce7;
        --pip-text-main: #1e293b;
        --pip-text-muted: #64748b;
        --pip-border: #e2e8f0;
        --pip-bg: #f8fafc;
        --pip-card-bg: #ffffff;
        --pip-danger: #ef4444;
        --pip-danger-light: #fee2e2;
        --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        background-color: transparent;
    }

    .pip-wrapper {
        font-family: var(--font-sans);
        background: var(--pip-card-bg);
        border: 1px solid var(--pip-border);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        width: 100%;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .pip-header {
        background: var(--pip-card-bg);
        padding: 20px 24px;
        border-bottom: 1px solid var(--pip-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .pip-title-area {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .pip-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--pip-primary);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pip-title svg {
        width: 20px;
        height: 20px;
    }

    .pip-subtitle {
        font-size: 0.875rem;
        color: var(--pip-text-muted);
    }

    .pip-controls {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .pip-search {
        padding: 8px 14px;
        border: 1px solid var(--pip-border);
        border-radius: 8px;
        font-size: 0.875rem;
        outline: none;
        transition: all 0.2s;
        width: 250px;
        font-family: inherit;
    }

    .pip-search:focus {
        border-color: var(--pip-primary);
        box-shadow: 0 0 0 3px var(--pip-primary-light);
    }

    .pip-refresh-btn {
        background: var(--pip-bg);
        border: 1px solid var(--pip-border);
        color: var(--pip-text-main);
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .pip-refresh-btn:hover {
        background: var(--pip-border);
    }

    .pip-refresh-btn.loading svg {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        100% { transform: rotate(360deg); }
    }

    .pip-list {
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        max-height: 600px;
        overflow-y: auto;
        background: var(--pip-bg);
    }

    /* Scrollbar styling */
    .pip-list::-webkit-scrollbar {
        width: 6px;
    }
    .pip-list::-webkit-scrollbar-track {
        background: transparent;
    }
    .pip-list::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 10px;
    }

    .pip-card {
        background: var(--pip-card-bg);
        border: 1px solid var(--pip-border);
        border-radius: 12px;
        padding: 16px;
        transition: transform 0.2s, box-shadow 0.2s;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 20px;
    }

    .pip-card:hover {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        transform: translateY(-2px);
        border-color: #cbd5e1;
    }

    .pip-content {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .pip-meta-top {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .pip-badge {
        background: var(--pip-primary-light);
        color: var(--pip-primary);
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 6px;
        letter-spacing: 0.02em;
    }

    .pip-no-perkara {
        font-weight: 700;
        color: var(--pip-text-main);
        font-size: 1rem;
    }

    .pip-date {
        font-size: 0.8125rem;
        color: var(--pip-text-muted);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .pip-date svg {
        width: 14px; height: 14px;
    }

    .pip-main-text {
        font-size: 0.9rem;
        color: var(--pip-text-main);
        line-height: 1.6;
        background: #fdfdfd;
        border-left: 3px solid var(--pip-primary);
        padding: 10px 14px;
        border-radius: 0 8px 8px 0;
    }

    .pip-highlight {
        font-weight: 700;
        color: var(--pip-text-main);
    }

    .pip-danger-text {
        color: var(--pip-danger);
        font-weight: 600;
        background: var(--pip-danger-light);
        padding: 2px 6px;
        border-radius: 4px;
    }

    .pip-footer {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-top: 4px;
        font-size: 0.8125rem;
        color: var(--pip-text-muted);
    }

    .pip-jurusita {
        display: flex;
        align-items: center;
        gap: 6px;
        background: var(--pip-bg);
        padding: 4px 10px;
        border-radius: 6px;
        border: 1px solid var(--pip-border);
    }

    .pip-jurusita svg {
        width: 14px; height: 14px; color: var(--pip-primary);
    }

    .pip-action {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-end;
        gap: 10px;
        min-width: 140px;
        border-left: 1px dashed var(--pip-border);
        padding-left: 20px;
    }

    .pip-status-tayang {
        font-size: 0.75rem;
        color: var(--pip-text-muted);
        text-align: right;
    }

    .pip-status-tayang strong {
        display: block;
        font-size: 1.125rem;
        color: var(--pip-primary);
        font-weight: 800;
    }

    .pip-btn-download {
        background: var(--pip-primary);
        color: white;
        text-decoration: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: background 0.2s;
        white-space: nowrap;
    }

    .pip-btn-download:hover {
        background: var(--pip-primary-hover);
    }

    .pip-btn-download svg {
        width: 16px; height: 16px;
    }

    .pip-empty {
        padding: 40px 20px;
        text-align: center;
        color: var(--pip-text-muted);
        background: white;
        border-radius: 12px;
        border: 1px dashed #cbd5e1;
    }

    .pip-empty svg {
        width: 48px; height: 48px; color: #cbd5e1; margin-bottom: 12px;
    }

    /* Skeleton Loader */
    .skeleton {
        background: #e2e8f0;
        border-radius: 4px;
        position: relative;
        overflow: hidden;
    }
    .skeleton::after {
        content: "";
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        animation: loading 1.5s infinite;
    }
    @keyframes loading {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    @media (max-width: 768px) {
        .pip-card {
            grid-template-columns: 1fr;
            gap: 16px;
        }
        .pip-action {
            border-left: none;
            border-top: 1px dashed var(--pip-border);
            padding-left: 0;
            padding-top: 16px;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
        .pip-status-tayang {
            text-align: left;
        }
        .pip-search {
            width: 100%;
        }
        .pip-controls {
            width: 100%;
        }
        .pip-refresh-btn {
            flex-shrink: 0;
        }
    }
</style>

<div class="pip-wrapper">
    <div class="pip-header">
        <div class="pip-title-area">
            <h3 class="pip-title">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                Pemberitahuan Isi Putusan
            </h3>
            <span class="pip-subtitle">Pengadilan Agama Semarang Kelas IA</span>
        </div>
        <div class="pip-controls">
            <input type="text" id="pipSearch" class="pip-search" placeholder="Cari nomor perkara atau nama...">
            <button id="pipRefresh" class="pip-refresh-btn" title="Muat ulang data">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <div id="pipList" class="pip-list">
        <!-- Render container -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const listContainer = document.getElementById('pipList');
    const searchInput = document.getElementById('pipSearch');
    const refreshBtn = document.getElementById('pipRefresh');

    let pipData = [];

    // Formatter
    const formatDate = (dateStr) => {
        if(!dateStr) return '-';
        const d = new Date(dateStr);
        return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(d);
    };

    // Calculate days passed
    const calcDays = (dateStr) => {
        if(!dateStr) return 0;
        const upload = new Date(dateStr).getTime();
        const now = new Date().getTime();
        return Math.floor((now - upload) / (1000 * 3600 * 24)) + 1;
    };

    const renderSkeletons = () => {
        listContainer.innerHTML = Array(3).fill(0).map(() => `
            <div class="pip-card" style="pointer-events: none;">
                <div class="pip-content">
                    <div class="pip-meta-top">
                        <div class="skeleton" style="width: 60px; height: 24px;"></div>
                        <div class="skeleton" style="width: 150px; height: 20px;"></div>
                        <div class="skeleton" style="width: 100px; height: 20px;"></div>
                    </div>
                    <div class="skeleton" style="width: 100%; height: 80px; margin-top: 10px;"></div>
                </div>
                <div class="pip-action">
                    <div class="skeleton" style="width: 80px; height: 40px;"></div>
                    <div class="skeleton" style="width: 100px; height: 36px; border-radius: 8px;"></div>
                </div>
            </div>
        `).join('');
    };

    const renderEmpty = (message) => {
        listContainer.innerHTML = `
            <div class="pip-empty">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p>${message}</p>
            </div>
        `;
    };

    const renderData = (data) => {
        if(data.length === 0) {
            renderEmpty(searchInput.value ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data Pemberitahuan Isi Putusan yang dipublikasikan.');
            return;
        }

        listContainer.innerHTML = data.map(item => {
            const hariTayang = calcDays(item.tgl_upload || item.created_at);
            const tglUploadFmt = formatDate(item.tgl_upload || item.created_at);
            const tglPutusFmt = formatDate(item.tgl_putusan);
            const hasDoc = !!item.link_file;
            
            // Generate link
            let docLink = '#';
            if (hasDoc) {
                // If it's old legacy webpip
                docLink = item.link_file.includes('/') ? item.link_file : `/webpip/DOC/${item.link_file}`;
            }

            return `
            <div class="pip-card">
                <div class="pip-content">
                    <div class="pip-meta-top">
                        <span class="pip-badge">PIP</span>
                        <span class="pip-no-perkara">${item.nomor_perkara}</span>
                        <span class="pip-date">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                            Dipublikasikan ${tglUploadFmt}
                        </span>
                    </div>

                    <div class="pip-main-text">
                        Telah memberitahukan kepada <span class="pip-highlight">${item.nama_pihak}</span> (Umur ${item.umur||'-'} tahun, Agama ${item.agama||'-'}, Pekerjaan ${item.pekerjaan||'-'}) 
                        yang dahulu beralamat di ${item.alamat || '<tidak diketahui>'}, <span class="pip-danger-text">dan saat ini tidak diketahui alamat dan keberadaannya di seluruh wilayah Republik Indonesia</span>.<br><br>
                        Tentang putusan Pengadilan Agama Semarang, tanggal ${tglPutusFmt} Nomor <strong>${item.nomor_perkara}</strong> dalam perkara ${item.jenis_perkara || '-'}.
                    </div>

                    <div class="pip-footer">
                        <div class="pip-jurusita" title="Jurusita / JSP">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            ${item.jurusita_nama || '-'}
                        </div>
                    </div>
                </div>

                <div class="pip-action">
                    <div class="pip-status-tayang">
                        Lama Tayang
                        <strong>${hariTayang} Hari</strong>
                    </div>
                    ${hasDoc ? `
                        <a href="${docLink}" target="_blank" class="pip-btn-download">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            Lihat Dokumen
                        </a>
                    ` : `
                        <span style="color:var(--pip-danger); font-size:0.875rem; font-style:italic;">Dokumen kosong</span>
                    `}
                </div>
            </div>
            `;
        }).join('');
    };

    const fetchData = async () => {
        renderSkeletons();
        refreshBtn.classList.add('loading');
        try {
            // Karena ini widget embed PHP di Lawangsewu, kita panggil API dummy atau jika sudah ada endpoint PIP
            // Untuk sementara kita pakai mock jika tidak ada endpoint
            // const res = await fetch('/api/pengumuman-pip');
            // const result = await res.json();
            // pipData = result.data;
            
            // Mock data representing DB state
            // Di implementasi aslinya, data bisa di-inject via PHP json_encode langsung dari DB tbl_pip
            await new Promise(r => setTimeout(r, 800)); // Simulate network
            
            pipData = <?php
                // Inject data if connection exists
                try {
                    $db = new PDO("mysql:host=localhost;dbname=paseman_webpip", "paseman_paseman", "nDZkbUBK6FeA96");
                    $stmt = $db->query("SELECT * FROM tbl_pip WHERE aktif=1 ORDER BY id DESC LIMIT 50");
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($data);
                } catch(Exception $e) {
                    echo '[]';
                }
            ?>;

            if(pipData.length === 0) {
                // Dummy fallback for preview
                pipData = [
                    { nomor_perkara: "1234/Pdt.G/2026/PA.Smg", jenis_perkara: "Cerai Gugat", tgl_upload: "2026-05-10", tgl_putusan: "2026-05-08", nama_pihak: "Fulanah binti Fulan", umur: 35, agama: "Islam", pekerjaan: "Wiraswasta", alamat: "Jl. Pemuda No.1", jurusita_nama: "Ahmad Jurusita, S.H.", link_file: "dummy.pdf" },
                    { nomor_perkara: "1122/Pdt.G/2026/PA.Smg", jenis_perkara: "Cerai Talak", tgl_upload: "2026-05-01", tgl_putusan: "2026-04-20", nama_pihak: "Budi bin Slamet", umur: 40, agama: "Islam", pekerjaan: "Karyawan", alamat: "Jl. Pandanaran", jurusita_nama: "Slamet JSP", link_file: "" }
                ];
            }

            renderData(pipData);
        } catch (err) {
            console.error(err);
            renderEmpty('Gagal memuat data. Silakan coba lagi.');
        } finally {
            refreshBtn.classList.remove('loading');
        }
    };

    searchInput.addEventListener('input', (e) => {
        const val = e.target.value.toLowerCase();
        const filtered = pipData.filter(item => 
            (item.nomor_perkara || '').toLowerCase().includes(val) || 
            (item.nama_pihak || '').toLowerCase().includes(val)
        );
        renderData(filtered);
    });

    refreshBtn.addEventListener('click', fetchData);

    // Initial load
    fetchData();
});
</script>
