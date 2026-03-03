<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TUGUMUDA - Data Server 10</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1f2937; }
        .wrap { max-width: 920px; margin: 32px auto; background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 6px 20px rgba(0,0,0,.08); }
        h1 { margin-top: 0; font-size: 24px; }
        .row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
        input { flex: 1; min-width: 280px; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; }
        button { padding: 10px 14px; border: 0; border-radius: 8px; background: #0f766e; color: #fff; cursor: pointer; }
        button:hover { opacity: .95; }
        .muted { color: #6b7280; font-size: 13px; margin-bottom: 16px; }
        pre { background: #111827; color: #e5e7eb; padding: 14px; border-radius: 8px; overflow: auto; min-height: 200px; }
        .status { font-weight: 600; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>TUGUMUDA - Data dari Server 10</h1>
        <div class="muted">Default path diset ke endpoint lumpiapasar. Ubah jika endpoint kamu berbeda.</div>

        <div class="row">
            <input id="pathInput" type="text" value="/lumpiapasar/api/monitor" placeholder="Contoh: /lumpiapasar/api/monitor">
            <button id="fetchBtn">Ambil Data</button>
        </div>
        <div class="row">
            <label><input id="autoRefreshToggle" type="checkbox" checked> Auto-refresh 30 detik</label>
        </div>

        <div class="status" id="statusText">Status: siap</div>
        <pre id="result">Klik "Ambil Data" untuk mulai.</pre>
    </div>

    <script>
        const pathInput = document.getElementById('pathInput');
        const fetchBtn = document.getElementById('fetchBtn');
        const autoRefreshToggle = document.getElementById('autoRefreshToggle');
        const result = document.getElementById('result');
        const statusText = document.getElementById('statusText');
        const REFRESH_INTERVAL_MS = 30000;
        let pollTimer = null;
        let isFetching = false;

        async function fetchData() {
            if (isFetching) {
                return;
            }

            isFetching = true;
            const path = pathInput.value.trim() || '/lumpiapasar/api/monitor';
            statusText.textContent = 'Status: mengambil data...';
            result.textContent = 'Loading...';

            try {
                const resp = await fetch('proxy.php?path=' + encodeURIComponent(path));
                const data = await resp.json();
                statusText.textContent = `Status: HTTP ${resp.status}`;
                result.textContent = JSON.stringify(data, null, 2);
            } catch (error) {
                statusText.textContent = 'Status: gagal';
                result.textContent = 'Error: ' + error.message;
            } finally {
                isFetching = false;
            }
        }

        function startPolling() {
            stopPolling();
            pollTimer = setInterval(fetchData, REFRESH_INTERVAL_MS);
        }

        function stopPolling() {
            if (pollTimer !== null) {
                clearInterval(pollTimer);
                pollTimer = null;
            }
        }

        fetchBtn.addEventListener('click', fetchData);
        autoRefreshToggle.addEventListener('change', () => {
            if (autoRefreshToggle.checked) {
                startPolling();
            } else {
                stopPolling();
            }
        });

        fetchData();
        if (autoRefreshToggle.checked) {
            startPolling();
        }
    </script>
</body>
</html>
