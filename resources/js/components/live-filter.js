/**
 * Unified Live Auto-Filter Module
 * Menangani filter dropdown instan & live search (debounce + AbortController + @fragment swap)
 * tanpa full page reload dan tanpa menghilangkan fokus kursor ketik.
 */
const activeFilterCleanups = [];

function buildCleanUrl(form, pageUrl = null) {
    const url = new URL(pageUrl || form.getAttribute('action') || window.location.href, window.location.origin);

    if (!pageUrl) {
        const activePerPage = new URLSearchParams(window.location.search).get('per_page');
        url.search = '';
        const formData = new FormData(form);
        for (const [key, value] of formData.entries()) {
            const trimmed = typeof value === 'string' ? value.trim() : value;
            if (trimmed !== '' && trimmed !== null) {
                url.searchParams.set(key, trimmed);
            }
        }
        const includeSelector = form.dataset.liveInclude;
        if (includeSelector) {
            document.querySelectorAll(includeSelector).forEach((el) => {
                if (!el.name) return;
                const val = (el.value || '').trim();
                if (val !== '') {
                    url.searchParams.set(el.name, val);
                } else {
                    url.searchParams.delete(el.name);
                }
            });
        }
        if (activePerPage && !url.searchParams.has('per_page')) {
            url.searchParams.set('per_page', activePerPage);
        }
    }

    return url;
}

function hasActiveUserFilters(form) {
    const fields = form.querySelectorAll('input:not([type="hidden"]), select');
    for (const field of fields) {
        const val = (field.value || '').trim();
        const defaultVal = field.dataset.defaultValue ?? '';
        if (val !== '' && val !== defaultVal) {
            return true;
        }
    }
    const hiddenMarkers = form.querySelectorAll('input[type="hidden"][data-active-filter]');
    return hiddenMarkers.length > 0;
}

export function initLiveFilters() {
    const forms = document.querySelectorAll('form[data-live-filter]');

    forms.forEach((form) => {
        if (form.dataset.liveAttached === 'true') return;
        form.dataset.liveAttached = 'true';

        const targetSelector = form.dataset.liveTarget;
        const targetEl = targetSelector ? document.querySelector(targetSelector) : null;
        if (!targetEl) return;

        const debounceMs = parseInt(form.dataset.liveDebounce || '350', 10);
        const spinnerEl = form.querySelector('[data-live-spinner]');
        const resetBtn = form.querySelector('[data-live-reset]');
        const clearBtns = form.querySelectorAll('[data-live-clear]');
        const countDisplayEl = document.querySelector(form.dataset.liveCountTarget || '[data-live-count-display]');

        let debounceTimer = null;
        let abortController = null;

        const syncControlsUi = () => {
            const active = hasActiveUserFilters(form);
            if (resetBtn) {
                resetBtn.classList.toggle('hidden', !active);
            }
            clearBtns.forEach((btn) => {
                const inputSelector = btn.dataset.liveClear;
                const input = inputSelector ? form.querySelector(inputSelector) : form.querySelector('input[type="text"], input[type="search"]');
                const hasVal = input && input.value.trim().length > 0;
                btn.classList.toggle('hidden', !hasVal);
            });
        };

        const setLoading = (isLoading) => {
            if (spinnerEl) {
                spinnerEl.classList.toggle('hidden', !isLoading);
            }
            targetEl.style.transition = 'opacity 150ms ease, transform 150ms ease';
            targetEl.style.opacity = isLoading ? '0.55' : '1';
        };

        const executeFetch = async (pageUrl = null) => {
            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();

            const cleanUrl = buildCleanUrl(form, pageUrl);
            const fetchUrl = new URL(cleanUrl.toString());
            fetchUrl.searchParams.set('_live', '1');

            setLoading(true);

            try {
                const response = await fetch(fetchUrl.toString(), {
                    signal: abortController.signal,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Live-Filter': '1',
                        'Accept': 'text/html',
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const html = await response.text();
                targetEl.innerHTML = html;

                if (window.Alpine) {
                    window.Alpine.initTree(targetEl);
                }

                // Sync browser URL without reload
                window.history.replaceState({}, '', cleanUrl.toString());

                // Optional total count & bulk checkbox IDs sync
                const metaSource = targetEl.querySelector('[data-live-meta]') || targetEl.firstElementChild;
                if (metaSource) {
                    if (countDisplayEl && metaSource.dataset.liveCount !== undefined) {
                        countDisplayEl.textContent = `(${metaSource.dataset.liveCount})`;
                    }

                    let ids = null;
                    let components = null;
                    if (metaSource.dataset.pageIds) {
                        try {
                            ids = JSON.parse(metaSource.dataset.pageIds);
                        } catch (e) {}
                    }
                    if (metaSource.dataset.pageComponents) {
                        try {
                            components = JSON.parse(metaSource.dataset.pageComponents);
                        } catch (e) {}
                    }

                    if (ids !== null || components !== null) {
                        targetEl.dispatchEvent(new CustomEvent('live-filter-updated', {
                            bubbles: true,
                            detail: { ids, components },
                        }));
                    }
                }

                syncControlsUi();
                setLoading(false);
            } catch (err) {
                if (err.name === 'AbortError') {
                    return;
                }
                console.error('Live filter fallback reload:', err);
                window.location.href = cleanUrl.toString();
            }
        };

        const onInput = (e) => {
            const target = e.target;
            if (!target.matches('input[type="text"], input[type="search"]')) return;

            syncControlsUi();
            clearTimeout(debounceTimer);
            const delay = target.value.trim() === '' ? 120 : debounceMs;
            debounceTimer = setTimeout(() => executeFetch(), delay);
        };

        const onChange = (e) => {
            const target = e.target;
            if (!target.matches('select, input[type="date"]')) return;

            syncControlsUi();
            clearTimeout(debounceTimer);
            executeFetch();
        };

        const onSubmit = (e) => {
            e.preventDefault();
            clearTimeout(debounceTimer);
            executeFetch();
        };

        const onClearClick = (e) => {
            const btn = e.target.closest('[data-live-clear]');
            if (!btn) return;
            e.preventDefault();
            const inputSelector = btn.dataset.liveClear;
            const input = inputSelector ? form.querySelector(inputSelector) : form.querySelector('input[type="text"], input[type="search"]');
            if (input) {
                input.value = '';
                input.focus();
            }
            syncControlsUi();
            clearTimeout(debounceTimer);
            executeFetch();
        };

        const onResetClick = (e) => {
            const btn = e.target.closest('[data-live-reset]');
            if (!btn) return;
            if (btn.dataset.liveResetMode === 'navigate') {
                return; // Let full reset clear hidden quick-filter URL flags cleanly
            }
            e.preventDefault();
            form.querySelectorAll('input:not([type="hidden"])').forEach((inp) => {
                inp.value = inp.dataset.defaultValue ?? '';
            });
            form.querySelectorAll('select').forEach((sel) => {
                sel.value = sel.dataset.defaultValue ?? '';
            });
            syncControlsUi();
            clearTimeout(debounceTimer);
            executeFetch();
        };

        const onTargetClick = (e) => {
            const link = e.target.closest('nav[role="navigation"] a, .pagination a, a[href*="page="]');
            if (!link || !link.href) return;
            e.preventDefault();
            executeFetch(link.href);
        };

        const onTargetChange = (e) => {
            const select = e.target.closest('select[data-per-page-select]');
            if (!select) return;
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', select.value);
            url.searchParams.delete('page');
            executeFetch(url.toString());
        };

        targetEl.dataset.livePagination = 'true';

        form.addEventListener('input', onInput);
        form.addEventListener('change', onChange);
        form.addEventListener('submit', onSubmit);
        form.addEventListener('click', onClearClick);
        form.addEventListener('click', onResetClick);
        targetEl.addEventListener('click', onTargetClick);
        targetEl.addEventListener('change', onTargetChange);

        syncControlsUi();

        activeFilterCleanups.push(() => {
            clearTimeout(debounceTimer);
            if (abortController) abortController.abort();
            form.removeEventListener('input', onInput);
            form.removeEventListener('change', onChange);
            form.removeEventListener('submit', onSubmit);
            form.removeEventListener('click', onClearClick);
            form.removeEventListener('click', onResetClick);
            targetEl.removeEventListener('click', onTargetClick);
            targetEl.removeEventListener('change', onTargetChange);
            delete targetEl.dataset.livePagination;
            delete form.dataset.liveAttached;
        });
    });
}

export function cleanupLiveFilters() {
    while (activeFilterCleanups.length > 0) {
        const fn = activeFilterCleanups.pop();
        try {
            fn();
        } catch (e) {}
    }
}
