/**
 * Silent Drag & Drop File Upload Enhancer
 * Mengaktifkan drag & drop file langsung pada input[type="file"] dan kontainer [data-drop-zone]
 * tanpa menambah elemen teks atau tampilan khusus, kompatibel dengan Alpine @change & Turbo lifecycle.
 */
const activeDropCleanups = [];

const EXTENSION_MIME_HINTS = {
    image: ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.bmp', '.heic'],
    'application/pdf': ['.pdf'],
    'application/zip': ['.zip'],
};

function matchesAccept(file, acceptAttr) {
    if (!acceptAttr || !acceptAttr.trim()) return true;

    const tokens = acceptAttr
        .split(',')
        .map((t) => t.trim().toLowerCase())
        .filter(Boolean);

    if (tokens.length === 0 || tokens.includes('*/*')) return true;

    const fileType = (file.type || '').toLowerCase();
    const fileName = (file.name || '').toLowerCase();

    return tokens.some((token) => {
        if (token.startsWith('.')) {
            if (token === '.jpg' && fileName.endsWith('.jpeg')) return true;
            if (token === '.jpeg' && fileName.endsWith('.jpg')) return true;
            return fileName.endsWith(token);
        }
        if (token.endsWith('/*')) {
            const major = token.slice(0, -2);
            if (fileType.startsWith(`${major}/`)) return true;
            const hints = EXTENSION_MIME_HINTS[major];
            return Array.isArray(hints) && hints.some((ext) => fileName.endsWith(ext));
        }
        if (fileType === token) return true;
        const mimeHints = EXTENSION_MIME_HINTS[token];
        return Array.isArray(mimeHints) && mimeHints.some((ext) => fileName.endsWith(ext));
    });
}

function attachDropBehavior(zoneEl, inputEl) {
    if (!zoneEl || !inputEl || zoneEl.dataset.dropAttached === 'true') return;
    zoneEl.dataset.dropAttached = 'true';
    inputEl.dataset.dropAttached = 'true';

    let dragDepth = 0;
    const hoverClasses = ['ring-2', 'ring-teal-500/40', 'transition-shadow'];
    const invalidClasses = ['ring-2', 'ring-rose-400/60', 'transition-shadow'];

    const setHoverVisual = (active) => {
        hoverClasses.forEach((cls) => zoneEl.classList.toggle(cls, active));
    };

    const flashInvalidVisual = () => {
        invalidClasses.forEach((cls) => zoneEl.classList.add(cls));
        setTimeout(() => {
            invalidClasses.forEach((cls) => zoneEl.classList.remove(cls));
        }, 650);
    };

    const onDragEnter = (e) => {
        if (!e.dataTransfer || !Array.from(e.dataTransfer.types || []).includes('Files')) return;
        e.preventDefault();
        e.stopPropagation();
        dragDepth += 1;
        setHoverVisual(true);
    };

    const onDragOver = (e) => {
        if (!e.dataTransfer || !Array.from(e.dataTransfer.types || []).includes('Files')) return;
        e.preventDefault();
        e.stopPropagation();
        e.dataTransfer.dropEffect = 'copy';
    };

    const onDragLeave = (e) => {
        if (dragDepth === 0) return;
        e.preventDefault();
        e.stopPropagation();
        dragDepth = Math.max(0, dragDepth - 1);
        if (dragDepth === 0) {
            setHoverVisual(false);
        }
    };

    const onDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        dragDepth = 0;
        setHoverVisual(false);

        const droppedFiles = Array.from(e.dataTransfer?.files || []);
        if (droppedFiles.length === 0) return;

        const acceptAttr = inputEl.getAttribute('accept') || '';
        const validFiles = droppedFiles.filter((file) => matchesAccept(file, acceptAttr));

        if (validFiles.length === 0) {
            flashInvalidVisual();
            return;
        }

        try {
            const dt = new DataTransfer();
            const filesToAssign = inputEl.multiple ? validFiles : [validFiles[0]];
            filesToAssign.forEach((file) => dt.items.add(file));
            inputEl.files = dt.files;
            inputEl.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (err) {
            console.error('Silent file drop failed:', err);
        }
    };

    zoneEl.addEventListener('dragenter', onDragEnter);
    zoneEl.addEventListener('dragover', onDragOver);
    zoneEl.addEventListener('dragleave', onDragLeave);
    zoneEl.addEventListener('drop', onDrop);

    activeDropCleanups.push(() => {
        setHoverVisual(false);
        zoneEl.removeEventListener('dragenter', onDragEnter);
        zoneEl.removeEventListener('dragover', onDragOver);
        zoneEl.removeEventListener('dragleave', onDragLeave);
        zoneEl.removeEventListener('drop', onDrop);
        delete zoneEl.dataset.dropAttached;
        delete inputEl.dataset.dropAttached;
    });
}

export function initSilentFileDrops() {
    // 1. Explicit [data-drop-zone] containers
    document.querySelectorAll('[data-drop-zone]').forEach((zoneEl) => {
        const inputEl = zoneEl.querySelector('input[type="file"]:not([data-no-silent-drop])');
        if (inputEl) {
            attachDropBehavior(zoneEl, inputEl);
        }
    });

    // 2. Fallback for standalone visible file inputs without custom dropzone handlers
    document.querySelectorAll('input[type="file"]:not([data-drop-attached]):not([data-no-silent-drop])').forEach((inputEl) => {
        if (inputEl.classList.contains('hidden') || inputEl.classList.contains('opacity-0')) return;
        const zoneEl = inputEl.parentElement || inputEl;
        attachDropBehavior(zoneEl, inputEl);
    });
}

export function cleanupSilentFileDrops() {
    while (activeDropCleanups.length > 0) {
        const fn = activeDropCleanups.pop();
        try {
            fn();
        } catch (e) {}
    }
}
