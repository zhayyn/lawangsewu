const http = require('http');
const { URL } = require('url');
const { scrapeAnnouncements } = require('./services/pengumumanService');

function getEnv(name, fallback) {
  const value = process.env[name];
  return value && value.trim() !== '' ? value : fallback;
}

const host = getEnv('NODE_HOST', '127.0.0.1');
const port = Number.parseInt(getEnv('NODE_PORT', '8787'), 10);
const appName = getEnv('NODE_APP_NAME', 'Lawangsewu Node Gateway');
const corsOrigin = getEnv('NODE_CORS_ORIGIN', '*');

function writeJson(res, statusCode, payload) {
  res.writeHead(statusCode, {
    'Content-Type': 'application/json; charset=utf-8',
    'Access-Control-Allow-Origin': corsOrigin,
    'Access-Control-Allow-Methods': 'GET,OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type',
    'Cache-Control': 'no-store',
  });
  res.end(JSON.stringify(payload));
}

const server = http.createServer(async (req, res) => {
  if (req.method === 'OPTIONS') {
    writeJson(res, 204, {});
    return;
  }

  const currentUrl = new URL(req.url, `http://${host}:${port}`);
  const pathname = currentUrl.pathname;

  if (pathname === '/health') {
    writeJson(res, 200, { ok: true, app: appName, time: new Date().toISOString() });
    return;
  }

  if (req.method === 'GET' && pathname === '/api/v1/pengumuman') {
    const source = currentUrl.searchParams.get('source') || 'all';
    const limit = currentUrl.searchParams.get('limit') || '10';

    try {
      const data = await scrapeAnnouncements({ source, limit });
      writeJson(res, 200, data);
    } catch (error) {
      writeJson(res, 502, {
        ok: false,
        message: 'Gagal mengambil data pengumuman dari sumber eksternal.',
        detail: error.message,
      });
    }
    return;
  }

  if (req.method === 'GET' && pathname === '/api/v1/pengumuman/ma') {
    try {
      const limit = currentUrl.searchParams.get('limit') || '10';
      const data = await scrapeAnnouncements({ source: 'ma', limit });
      writeJson(res, 200, data);
    } catch (error) {
      writeJson(res, 502, {
        ok: false,
        message: 'Gagal mengambil pengumuman Mahkamah Agung.',
        detail: error.message,
      });
    }
    return;
  }

  if (req.method === 'GET' && pathname === '/api/v1/pengumuman/badilag') {
    try {
      const limit = currentUrl.searchParams.get('limit') || '10';
      const data = await scrapeAnnouncements({ source: 'badilag', limit });
      writeJson(res, 200, data);
    } catch (error) {
      writeJson(res, 502, {
        ok: false,
        message: 'Gagal mengambil pengumuman Badilag.',
        detail: error.message,
      });
    }
    return;
  }

  writeJson(res, 200, {
    ok: true,
    app: appName,
    message: 'Node workspace ready (isolated).',
    endpoints: ['/health', '/api/v1/pengumuman', '/api/v1/pengumuman/ma', '/api/v1/pengumuman/badilag']
  });
});

server.listen(port, host, () => {
  console.log(`[${appName}] listening on http://${host}:${port}`);
});
