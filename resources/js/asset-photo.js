import Cropper from 'cropperjs';

const form = document.querySelector('[data-asset-form]');
const fileInput = document.querySelector('[data-asset-photo-input]');
const cameraInput = document.querySelector('[data-asset-camera-input]');
const preview = document.querySelector('[data-asset-photo-preview]');
const placeholder = document.querySelector('[data-asset-photo-placeholder]');
const openButton = document.querySelector('[data-asset-photo-crop-open]');
const modal = document.getElementById('asset-photo-editor');
const cropperContainer = document.querySelector('[data-asset-photo-cropper]');
const applyButton = document.querySelector('[data-asset-photo-crop-apply]');
const cancelButton = document.querySelector('[data-asset-photo-crop-cancel]');
const resetButton = document.querySelector('[data-asset-photo-crop-reset]');
const zoomInButton = document.querySelector('[data-asset-photo-crop-zoom-in]');
const zoomOutButton = document.querySelector('[data-asset-photo-crop-zoom-out]');
const status = document.querySelector('[data-asset-photo-crop-status]');

const cropperTemplate = `
    <cropper-canvas background>
        <cropper-image translatable scalable initial-center-size="cover"></cropper-image>
        <cropper-handle action="move" plain></cropper-handle>
        <cropper-selection
            initial-coverage="1"
            aspect-ratio="1.3333333333"
            initial-aspect-ratio="1.3333333333"
            outlined
            style="pointer-events:none"
        >
            <cropper-grid role="grid" bordered covered></cropper-grid>
            <cropper-crosshair centered></cropper-crosshair>
        </cropper-selection>
    </cropper-canvas>
`;

let cropper = null;
let sourceUrl = preview?.getAttribute('src') || '';
let objectUrl = null;

function setStatus(message, tone = '') {
    if (!status) return;
    status.textContent = message;
    status.dataset.tone = tone;
}

function revokeObjectUrl() {
    if (!objectUrl) return;
    URL.revokeObjectURL(objectUrl);
    objectUrl = null;
}

function setInputFile(file) {
    const transfer = new DataTransfer();
    transfer.items.add(file);
    fileInput.files = transfer.files;
}

function updateFileLabel(file) {
    const label = fileInput.closest('[data-file-drop]')?.querySelector('[data-file-label]');
    if (label) label.textContent = `${file.name} (${Math.max(1, Math.round(file.size / 1024))} KB)`;
}

function showSelectedFile(file, copyToMainInput = false) {
    if (!file || !file.type.startsWith('image/')) {
        setStatus('File yang dipilih bukan foto yang didukung.', 'error');
        return;
    }

    if (copyToMainInput) setInputFile(file);
    updateFileLabel(file);
    revokeObjectUrl();
    objectUrl = URL.createObjectURL(file);
    sourceUrl = objectUrl;
    preview.src = sourceUrl;
    preview.hidden = false;
    if (placeholder) placeholder.hidden = true;
    openButton.disabled = false;
    openButton.click();
}

async function initializeCropper() {
    if (!sourceUrl || !cropperContainer) return;

    cropperContainer.replaceChildren();
    applyButton.disabled = true;
    setStatus('Menyiapkan foto aset...');

    const image = new Image();
    image.src = sourceUrl;
    image.alt = 'Foto aset yang sedang diatur';

    cropper = new Cropper(image, {
        container: cropperContainer,
        template: cropperTemplate,
    });

    const cropperImage = cropper.getCropperImage();

    try {
        await cropperImage?.$ready();
        cropperImage?.$center('cover');
        applyButton.disabled = false;
        setStatus('Geser dan perbesar foto sampai alat terlihat jelas.', 'ready');
    } catch {
        cropper = null;
        setStatus('Foto tidak dapat dibuka. Silakan pilih foto lain.', 'error');
    }
}

function imageFileName() {
    const originalName = fileInput.files?.[0]?.name || 'foto-aset.jpg';
    const baseName = originalName.replace(/\.[^.]+$/, '').replace(/[^a-z0-9_-]+/gi, '-');
    return `${baseName || 'foto-aset'}-4x3.jpg`;
}

function canvasToBlob(canvas) {
    return new Promise((resolve, reject) => {
        canvas.toBlob(
            (blob) => blob ? resolve(blob) : reject(new Error('Hasil foto tidak tersedia.')),
            'image/jpeg',
            0.9,
        );
    });
}

if (form && fileInput && preview && openButton && modal) {
    openButton.addEventListener('click', () => {
        window.setTimeout(initializeCropper, 0);
    });

    fileInput.addEventListener('change', () => {
        showSelectedFile(fileInput.files?.[0]);
    });

    cameraInput?.addEventListener('change', () => {
        showSelectedFile(cameraInput.files?.[0], true);
    });

    resetButton?.addEventListener('click', () => {
        const cropperImage = cropper?.getCropperImage();
        cropperImage?.$resetTransform().$center('cover');
        setStatus('Posisi foto dikembalikan ke tengah.', 'ready');
    });

    zoomInButton?.addEventListener('click', () => {
        cropper?.getCropperImage()?.$zoom(0.1);
    });

    zoomOutButton?.addEventListener('click', () => {
        cropper?.getCropperImage()?.$zoom(-0.1);
    });

    applyButton?.addEventListener('click', async () => {
        const selection = cropper?.getCropperSelection();
        if (!selection) return;

        applyButton.disabled = true;
        setStatus('Menerapkan komposisi foto...');

        try {
            const canvas = await selection.$toCanvas({
                width: 1600,
                height: 1200,
                beforeDraw(context) {
                    context.fillStyle = '#ffffff';
                    context.fillRect(0, 0, context.canvas.width, context.canvas.height);
                },
            });
            const blob = await canvasToBlob(canvas);
            const file = new File([blob], imageFileName(), {
                type: 'image/jpeg',
                lastModified: Date.now(),
            });

            setInputFile(file);
            updateFileLabel(file);
            revokeObjectUrl();
            objectUrl = URL.createObjectURL(file);
            sourceUrl = objectUrl;
            preview.src = sourceUrl;
            setStatus('Komposisi foto aset berhasil diterapkan.', 'success');
            cancelButton?.click();
        } catch {
            applyButton.disabled = false;
            setStatus('Komposisi belum dapat diterapkan. Silakan coba kembali.', 'error');
        }
    });

    window.addEventListener('beforeunload', revokeObjectUrl, { once: true });
}
