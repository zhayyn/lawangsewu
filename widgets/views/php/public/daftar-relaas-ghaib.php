<style>
    :root {
        --gh-primary: #0284c7;
        --gh-primary-hover: #0369a1;
        --gh-primary-light: #e0f2fe;
        --gh-text-main: #0f172a;
        --gh-text-muted: #64748b;
        --gh-border: #e2e8f0;
        --gh-bg: #f8fafc;
        --gh-card-bg: #ffffff;
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

    .gh-wrapper {
        font-family: var(--font-sans);
        background: var(--gh-card-bg);
        border: 1px solid var(--gh-border);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        width: 100%;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .gh-header {
        background: var(--gh-card-bg);
        padding: 20px 24px;
        border-bottom: 1px solid var(--gh-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .gh-title-area {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .gh-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--gh-primary);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .gh-title svg {
        width: 20px;
        height: 20px;
    }

    .gh-subtitle {
        font-size: 0.875rem;
        color: var(--gh-text-muted);
    }

    .gh-refresh-btn {
        background: var(--gh-bg);
        border: 1px solid var(--gh-border);
        color: var(--gh-text-main);
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

    .gh-refresh-btn:hover {
        background: var(--gh-border);
    }

    .gh-iframe-container {
        width: 100%;
        height: 800px; /* Default height */
        background: var(--gh-bg);
        position: relative;
    }

    .gh-iframe {
        width: 100%;
        height: 100%;
        border: none;
        display: block;
    }

    .gh-loader {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: var(--gh-bg);
        color: var(--gh-text-muted);
        z-index: 10;
        transition: opacity 0.3s;
    }

    .gh-loader svg {
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        color: var(--gh-primary);
        margin-bottom: 12px;
    }

    @keyframes spin {
        100% { transform: rotate(360deg); }
    }

</style>

<div class="gh-wrapper">
    <div class="gh-header">
        <div class="gh-title-area">
            <h3 class="gh-title">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                Relaas Panggilan Ghaib
            </h3>
            <span class="gh-subtitle">Pengadilan Agama Semarang Kelas IA</span>
        </div>
        <div>
            <button id="ghRefresh" class="gh-refresh-btn" title="Muat ulang halaman ghaib">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <div class="gh-iframe-container">
        <div id="ghLoader" class="gh-loader">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <p>Memuat Relaas Ghaib Infoweb...</p>
        </div>
        <iframe id="ghIframe" class="gh-iframe" src="https://infoweb.pa-semarang.go.id/ghoib.php" onload="document.getElementById('ghLoader').style.display='none'"></iframe>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const refreshBtn = document.getElementById('ghRefresh');
    const iframe = document.getElementById('ghIframe');
    const loader = document.getElementById('ghLoader');

    refreshBtn.addEventListener('click', () => {
        loader.style.display = 'flex';
        iframe.src = iframe.src; // Trigger reload
    });
});
</script>
