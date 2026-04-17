const axios = require('axios');
const cheerio = require('cheerio');

const DEFAULT_TIMEOUT_MS = Number.parseInt(process.env.SCRAPER_TIMEOUT_MS || '12000', 10);
const DEFAULT_TTL_MS = Number.parseInt(process.env.SCRAPER_CACHE_TTL_MS || '180000', 10);
const DEFAULT_LIMIT = Number.parseInt(process.env.SCRAPER_DEFAULT_LIMIT || '10', 10);
const MAX_LIMIT = Number.parseInt(process.env.SCRAPER_MAX_LIMIT || '30', 10);

const SOURCE_CONFIG = {
  ma: {
    code: 'ma',
    name: 'Mahkamah Agung RI',
    urls: [
      process.env.SCRAPER_MA_URL || 'https://www.mahkamahagung.go.id/id/pengumuman',
      process.env.SCRAPER_MA_FALLBACK_URL || 'https://www.mahkamahagung.go.id/id/berita',
      process.env.SCRAPER_MA_HOME_URL || 'https://www.mahkamahagung.go.id/id',
    ],
  },
  badilag: {
    code: 'badilag',
    name: 'Badilag MA RI',
    urls: [
      process.env.SCRAPER_BADILAG_URL || 'https://badilag.mahkamahagung.go.id/pengumuman-elektronik',
    ],
  },
};

const cache = new Map();

function cleanText(value) {
  return String(value || '').replace(/\s+/g, ' ').trim();
}

function titleFromUrl(urlValue) {
  const url = String(urlValue || '');
  const slug = url.split('/').filter(Boolean).pop() || '';
  if (!slug) {
    return '';
  }
  return cleanText(decodeURIComponent(slug.replace(/[-_]+/g, ' ')));
}

function normalizeUrl(baseUrl, href) {
  if (!href) {
    return null;
  }

  try {
    return new URL(href, baseUrl).toString();
  } catch (_err) {
    return null;
  }
}

function isValidAnnouncementLink(sourceCode, absoluteUrl) {
  if (!absoluteUrl) {
    return false;
  }

  if (sourceCode === 'ma') {
    return absoluteUrl.includes('/id/pengumuman/') || absoluteUrl.includes('/id/berita/');
  }

  if (sourceCode === 'badilag') {
    return absoluteUrl.includes('/pengumuman-elektronik/')
      && !absoluteUrl.endsWith('/pengumuman-elektronik');
  }

  return false;
}

function dedupeByUrl(items) {
  const seen = new Set();
  const output = [];
  for (const item of items) {
    if (!item.url || seen.has(item.url)) {
      continue;
    }
    seen.add(item.url);
    output.push(item);
  }
  return output;
}

function parseAnnouncements(sourceCode, pageUrl, html) {
  const $ = cheerio.load(html);
  const found = [];

  $('a').each((_, node) => {
    const href = $(node).attr('href');
    const absoluteUrl = normalizeUrl(pageUrl, href);
    if (!isValidAnnouncementLink(sourceCode, absoluteUrl)) {
      return;
    }

    const title = cleanText(
      $(node).text()
      || $(node).attr('title')
      || $(node).find('img').attr('alt')
      || titleFromUrl(absoluteUrl)
    );
    if (!title || title.length < 8) {
      return;
    }

    found.push({
      source: sourceCode,
      title,
      url: absoluteUrl,
    });
  });

  return dedupeByUrl(found);
}

async function scrapeSource(sourceCode, limit = DEFAULT_LIMIT) {
  const source = SOURCE_CONFIG[sourceCode];
  if (!source) {
    throw new Error(`Unknown source: ${sourceCode}`);
  }

  const safeLimit = Math.min(Math.max(Number(limit) || DEFAULT_LIMIT, 1), MAX_LIMIT);
  const cacheKey = `${sourceCode}:${safeLimit}`;
  const now = Date.now();
  const cached = cache.get(cacheKey);

  if (cached && cached.expiresAt > now) {
    return {
      ...cached.payload,
      cache: {
        hit: true,
        ttlMs: Math.max(cached.expiresAt - now, 0),
      },
    };
  }

  const collected = [];
  const urlList = Array.isArray(source.urls) ? source.urls : [];

  for (const pageUrl of urlList) {
    if (collected.length >= safeLimit) {
      break;
    }

    const response = await axios.get(pageUrl, {
      timeout: DEFAULT_TIMEOUT_MS,
      headers: {
        'User-Agent': 'Mozilla/5.0 (compatible; LawangsewuScraper/1.0; +https://pa-semarang.go.id)',
        Accept: 'text/html,application/xhtml+xml',
      },
      maxRedirects: 5,
      responseType: 'text',
    });

    const parsed = parseAnnouncements(sourceCode, pageUrl, response.data);
    collected.push(...parsed);
  }

  const items = dedupeByUrl(collected).slice(0, safeLimit);

  const payload = {
    source: source.code,
    sourceName: source.name,
    sourceUrl: urlList[0] || '',
    fetchedAt: new Date().toISOString(),
    total: items.length,
    items,
  };

  cache.set(cacheKey, {
    expiresAt: now + DEFAULT_TTL_MS,
    payload,
  });

  return {
    ...payload,
    cache: {
      hit: false,
      ttlMs: DEFAULT_TTL_MS,
    },
  };
}

async function scrapeAnnouncements({ source = 'all', limit = DEFAULT_LIMIT } = {}) {
  const sourceKey = String(source || 'all').toLowerCase();
  const safeLimit = Math.min(Math.max(Number(limit) || DEFAULT_LIMIT, 1), MAX_LIMIT);

  if (sourceKey === 'ma' || sourceKey === 'badilag') {
    const single = await scrapeSource(sourceKey, safeLimit);
    return {
      ok: true,
      source: sourceKey,
      limit: safeLimit,
      ...single,
    };
  }

  const [ma, badilag] = await Promise.all([
    scrapeSource('ma', safeLimit),
    scrapeSource('badilag', safeLimit),
  ]);

  return {
    ok: true,
    source: 'all',
    limit: safeLimit,
    fetchedAt: new Date().toISOString(),
    total: (ma.total || 0) + (badilag.total || 0),
    bySource: {
      ma,
      badilag,
    },
  };
}

module.exports = {
  scrapeAnnouncements,
};
