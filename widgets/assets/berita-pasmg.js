(() => {
    const root = document.getElementById('berita-pasmg-root');
    if (!root || root.dataset.loaded === '1') {
        return;
    }
    root.dataset.loaded = '1';

    const wpCatIdBerita = Number(root.dataset.wpCatBerita || '6');
    const wpCatIdPengumuman = Number(root.dataset.wpCatPengumuman || '7');
    const wpCatIdArtikel = Number(root.dataset.wpCatArtikel || '12');

    const wpApiUrl = String(root.dataset.wpApiUrl || 'https://pa-semarang.go.id').replace(/\/$/, '');
    const paFrontendUrl = String(root.dataset.paFrontendUrl || 'https://pa-semarang.go.id').replace(/\/$/, '');
    const thumbLocalFallback = String(root.dataset.thumbFallback || '/widgets/assets/ma-fallback.jpg');

    const THUMB_BADILAG_BUILDING = 'https://marinews.mahkamahagung.go.id/static/2025/12/10/dirjen-badilag-mowEv.jpg';
    const THUMB_PTA_BUILDING = 'https://pta-semarang.go.id/wp-content/uploads/2025/11/gedung-pta-semarang.png';
    const RSS_SOURCE_NAMES = { ma: 'Mahkamah Agung RI', badilag: 'Badilag MA RI', pta: 'PTA Semarang' };
    const RSS_PAGE_SIZE = 3;

    const localApiCandidates = [
        `${window.location.origin}/api/pengumuman-rss`,
        `${window.location.origin}/lawangsewu/api/pengumuman-rss`,
        `${window.location.origin}/widgets/views/php/api/api-pengumuman-rss.php`,
        `${window.location.origin}/lawangsewu/widgets/views/php/api/api-pengumuman-rss.php`
    ];

    function escHtml(text) {
        return String(text || '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[char]));
    }

    function formatDate(input) {
        try {
            return new Date(input).toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            });
        } catch (_) {
            return '-';
        }
    }

    function formatDateCompact(input) {
        try {
            const dt = new Date(input);
            if (Number.isNaN(dt.getTime())) {
                return '-';
            }
            const dd = String(dt.getDate()).padStart(2, '0');
            const mm = String(dt.getMonth() + 1).padStart(2, '0');
            const yy = String(dt.getFullYear());
            return `${dd}-${mm}-${yy}`;
        } catch (_) {
            return '-';
        }
    }

    const LOWERCASE_WORDS = new Set(['dan','di','ke','dari','yang','untuk','pada','dengan','oleh','atau','ini','itu','se','tak','tidak','akan','telah','juga','bagi','atas','dalam','tentang','secara','saat','antara','setelah','sebelum','terhadap','melalui','hingga','sampai','seperti','namun','tetapi','bila','jika','karena','sehingga','agar','supaya','bahwa','maupun']);

    function toTitleCase(text) {
        if (!text) return '';
        return String(text).toLowerCase().replace(/(?:^|(?<=[\.\!\?]\s))\S|\b\S/g, (char, offset, str) => {
            const wordMatch = str.slice(offset).match(/^\S+/);
            const word = wordMatch ? wordMatch[0] : '';
            if (offset === 0) return char.toUpperCase();
            const prevChar = str[offset - 1] || '';
            if (/[\.\!\?]/.test(str.slice(0, offset).trim().slice(-1))) return char.toUpperCase();
            if (LOWERCASE_WORDS.has(word)) return char;
            return char.toUpperCase();
        });
    }

    function applyPaBaseUrl() {
        root.querySelectorAll('[data-pa-path]').forEach((link) => {
            const path = link.getAttribute('data-pa-path');
            if (path) {
                link.setAttribute('href', `${paFrontendUrl}${path}`);
            }
        });
    }

    function trimText(text, maxLen) {
        const raw = String(text || '').trim();
        if (raw.length <= maxLen) {
            return raw;
        }
        return `${raw.slice(0, maxLen).trim()}...`;
    }

    function renderFeaturedItem(item, label) {
        return `
            <article class="featured-hero no-image">
                <div class="featured-body">
                    <div class="meta-line"><span><i class="fas fa-newspaper"></i> ${escHtml(label)}</span><span>${formatDate(item.date)}</span></div>
                    <a class="featured-link" href="${escHtml(item.link)}" target="_blank" rel="noopener">${escHtml(item.title)}</a>
                    <p class="featured-excerpt">${escHtml(trimText(item.description, 170))}</p>
                    <a class="btn-detail" href="${escHtml(item.link)}" target="_blank" rel="noopener">Selengkapnya <i class="fas fa-arrow-right"></i></a>
                </div>
            </article>
        `;
    }

    function renderTextItem(item) {
        return `
            <article class="list-item list-item-compact">
                <a href="${escHtml(item.link)}" target="_blank" rel="noopener">${escHtml(item.title)}</a>
                <div class="list-meta"><i class="far fa-calendar-alt"></i> ${formatDate(item.date)}</div>
            </article>
        `;
    }

    async function loadWpPosts(categoryId, limit = 3) {
        const apiUrl = `${wpApiUrl}/wp-json/wp/v2/posts?categories=${categoryId}&per_page=${limit}&_embed=true`;

        try {
            const response = await fetch(apiUrl);
            if (!response.ok) {
                throw new Error('Network error');
            }
            const posts = await response.json();

            return posts.map((post) => {
                let imageUrl = thumbLocalFallback;
                if (post._embedded && post._embedded['wp:featuredmedia']) {
                    const media = post._embedded['wp:featuredmedia'][0];
                    if (media && media.source_url) {
                        imageUrl = media.source_url;
                    }
                }

                const rawExcerpt = post.excerpt && post.excerpt.rendered ? post.excerpt.rendered : '';
                const cleanExcerpt = rawExcerpt.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

                const rawTitle = post.title && post.title.rendered ? post.title.rendered : '';
                const cleanTitle = rawTitle.replace(/^[\?\s]+/, '').trim();

                const finalLink = String(post.link || '');

                return {
                    link: finalLink,
                    title: cleanTitle,
                    date: post.date,
                    image: imageUrl,
                    description: cleanExcerpt
                };
            });
        } catch (error) {
            console.error(`Gagal memuat WP kategori ${categoryId}:`, error);
            return [];
        }
    }

    async function renderCategoryStack(categoryId, containerId, label) {
        const container = document.getElementById(containerId);
        if (!container) {
            return;
        }

        const items = await loadWpPosts(categoryId, 3);
        if (!items.length) {
            container.innerHTML = `<div class=\"rss-loader\">${escHtml(label)} belum tersedia.</div>`;
            return;
        }

        const [featured, ...rest] = items;
        const textItems = rest.slice(0, 2).map((item) => renderTextItem(item)).join('');
        container.innerHTML = `
            ${renderFeaturedItem(featured, label)}
            ${textItems}
        `;
    }

    function isPengumumanItem(item) {
        const text = `${item && item.title ? item.title : ''} ${item && item.url ? item.url : ''}`.toLowerCase();
        return /(pengumuman|pemberitahuan|edaran|seleksi|hasil|jadwal|undangan)/.test(text);
    }

    async function loadLocalSource(source) {
        let payload = null;
        for (const base of localApiCandidates) {
            try {
                const apiUrl = `${base}?source=${encodeURIComponent(source)}&limit=20&t=${Date.now()}`;
                const response = await fetch(apiUrl, { cache: 'no-store' });
                if (!response.ok) {
                    continue;
                }
                const candidate = await response.json();
                if (candidate && candidate.ok && Array.isArray(candidate.items)) {
                    payload = candidate;
                    break;
                }
            } catch (_) {
                // Coba kandidat endpoint berikutnya.
            }
        }

        if (!payload) {
            return [];
        }

        const sourceLabel = payload.sourceName || RSS_SOURCE_NAMES[source] || source.toUpperCase();
        const filteredItems = payload.items.filter(isPengumumanItem).slice(0, 15);

        return filteredItems.map((item) => ({
            link: item.url,
            title: item.title,
            date: item.date || payload.fetchedAt,
            source: sourceLabel,
            thumb: source === 'badilag' ? THUMB_BADILAG_BUILDING : (source === 'pta' ? THUMB_PTA_BUILDING : thumbLocalFallback)
        }));
    }

    async function renderExternalRss(source) {
        const container = document.getElementById('rss-container');
        if (!container) {
            return;
        }
        container.innerHTML = '<div class="rss-loader"><i class="fas fa-spinner fa-spin"></i> Memuat feed...</div>';

        try {
            const items = await loadLocalSource(source);

            if (!items.length) {
                container.innerHTML = '<div class="rss-loader">Feed belum tersedia.</div>';
                return;
            }

            const totalPages = Math.max(1, Math.ceil(items.length / RSS_PAGE_SIZE));
            let currentPage = 1;

            const renderPage = (pageNumber) => {
                const page = Math.min(Math.max(pageNumber, 1), totalPages);
                currentPage = page;
                const startIndex = (page - 1) * RSS_PAGE_SIZE;
                const visibleItems = items.slice(startIndex, startIndex + RSS_PAGE_SIZE);

                const renderBulletItems = (list) => list.map((item) => `
                    <li class="rss-bullet-item">
                        <div class="rss-item-row">
                            <img class="rss-thumb" src="${escHtml(item.thumb)}" alt="${escHtml(item.source)}" loading="lazy" onerror="this.onerror=null;this.src='${thumbLocalFallback}'">
                            <div>
                                <a href="${escHtml(item.link)}" target="_blank" rel="noopener">${escHtml(toTitleCase(item.title))}</a>
                                <div class="rss-source">Sumber : ${escHtml(item.source)} | Tanggal : ${formatDateCompact(item.date)} | Oleh ${escHtml(item.author || 'ruhan')}</div>
                            </div>
                        </div>
                    </li>
                `).join('');

                const pageButtons = Array.from({ length: totalPages }, (_, idx) => {
                    const number = idx + 1;
                    const activeClass = number === currentPage ? 'active' : '';
                    return `<button class="rss-page-btn ${activeClass}" type="button" data-page="${number}">${number}</button>`;
                }).join('');

                const nextDisabled = currentPage >= totalPages ? 'disabled' : '';
                const nextPage = Math.min(currentPage + 1, totalPages);

                container.innerHTML = `
                    <div class="rss-feed-shell">
                        <ul class="rss-bullet-list">${renderBulletItems(visibleItems)}</ul>
                        <div class="rss-pagination" aria-label="Pagination berita">
                            ${pageButtons}
                            <button class="rss-page-btn rss-next-btn" type="button" data-page="${nextPage}" ${nextDisabled}>Berikutnya</button>
                        </div>
                    </div>
                    <div class="rss-bottom-accent" aria-hidden="true"></div>
                `;
            };

            container.onclick = (event) => {
                const button = event.target.closest('[data-page]');
                if (!button || button.disabled) {
                    return;
                }
                const targetPage = Number(button.getAttribute('data-page') || '1');
                renderPage(targetPage);
            };

            renderPage(1);
        } catch (_) {
            container.innerHTML = '<div class="rss-loader">Gagal memuat RSS feed dari server.</div>';
        }
    }

    function setupTabs(rootId, attrName, panePrefix) {
        const tabRoot = document.getElementById(rootId);
        if (!tabRoot) {
            return;
        }

        tabRoot.addEventListener('click', (event) => {
            const button = event.target.closest(`[${attrName}]`);
            if (!button) {
                return;
            }
            const key = button.getAttribute(attrName);
            tabRoot.querySelectorAll(`[${attrName}]`).forEach((btn) => btn.classList.toggle('active', btn === button));
            root.querySelectorAll(`${panePrefix}`).forEach((pane) => pane.classList.remove('active'));
            const targetPane = document.getElementById(key);
            if (targetPane) {
                targetPane.classList.add('active');
            }
        });
    }

    function setupRssTabs() {
        const tabs = document.getElementById('rss-tabs');
        if (!tabs) {
            return;
        }

        tabs.addEventListener('click', (event) => {
            const button = event.target.closest('[data-rss-source]');
            if (!button) {
                return;
            }
            const source = button.getAttribute('data-rss-source');
            tabs.querySelectorAll('[data-rss-source]').forEach((btn) => btn.classList.toggle('active', btn === button));
            renderExternalRss(String(source || 'ma'));
        });
    }

    applyPaBaseUrl();
    setupTabs('pa-tabs', 'data-tab', '.tab-pane');
    setupRssTabs();
    renderCategoryStack(wpCatIdBerita, 'pa-berita-list', 'Berita');
    renderCategoryStack(wpCatIdPengumuman, 'pa-pengumuman-list', 'Pengumuman');
    renderCategoryStack(wpCatIdArtikel, 'pa-artikel-list', 'Artikel');
    renderExternalRss('ma');

    const accentObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            entry.target.classList.toggle('in-view', entry.isIntersecting);
        });
    }, { threshold: 0.3 });

    new MutationObserver(() => {
        root.querySelectorAll('.rss-bottom-accent:not([data-observed])').forEach((el) => {
            el.setAttribute('data-observed', '1');
            accentObserver.observe(el);
        });
    }).observe(root, { childList: true, subtree: true });
})();
