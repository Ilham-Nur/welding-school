import Cropper from 'cropperjs';

const form = document.querySelector('[data-activity-form]');
const fileInput = document.querySelector('[data-activity-image-input]');
const preview = document.querySelector('[data-activity-image-preview]');
const positionInput = document.querySelector('[data-activity-image-position]');
const openButton = document.querySelector('[data-activity-crop-open]');
const modal = document.getElementById('activity-image-focus');
const cropperContainer = document.querySelector('[data-activity-cropper]');
const applyButton = document.querySelector('[data-activity-crop-apply]');
const cancelButton = document.querySelector('[data-activity-crop-cancel]');
const resetButton = document.querySelector('[data-activity-crop-reset]');
const zoomInButton = document.querySelector('[data-activity-crop-zoom-in]');
const zoomOutButton = document.querySelector('[data-activity-crop-zoom-out]');
const status = document.querySelector('[data-activity-crop-status]');

const cropperTemplate = `
    <cropper-canvas background>
        <cropper-image translatable scalable initial-center-size="cover"></cropper-image>
        <cropper-handle action="move" plain></cropper-handle>
        <cropper-selection
            initial-coverage="1"
            aspect-ratio="1.7777777778"
            initial-aspect-ratio="1.7777777778"
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

async function initializeCropper() {
    if (!sourceUrl || !cropperContainer) return;

    cropperContainer.replaceChildren();
    applyButton.disabled = true;
    setStatus('Menyiapkan foto...');

    const image = new Image();
    image.src = sourceUrl;
    image.alt = 'Foto aktivitas yang sedang diatur';

    cropper = new Cropper(image, {
        container: cropperContainer,
        template: cropperTemplate,
    });

    const cropperImage = cropper.getCropperImage();

    try {
        await cropperImage?.$ready();
        cropperImage?.$center('cover');
        applyButton.disabled = false;
        setStatus('Geser foto untuk menentukan bagian yang tetap terlihat.', 'ready');
    } catch {
        cropper = null;
        setStatus('Foto tidak dapat dibuka. Silakan pilih ulang file.', 'error');
    }
}

function imageFileName() {
    const originalName = fileInput.files?.[0]?.name || 'aktivitas.jpg';
    const baseName = originalName.replace(/\.[^.]+$/, '').replace(/[^a-z0-9_-]+/gi, '-');
    return `${baseName || 'aktivitas'}-16x9.jpg`;
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

function setInputFile(file) {
    const transfer = new DataTransfer();
    transfer.items.add(file);
    fileInput.files = transfer.files;
}

if (form && fileInput && preview && positionInput && openButton && modal) {
    openButton.addEventListener('click', () => {
        window.setTimeout(initializeCropper, 0);
    });

    fileInput.addEventListener('change', () => {
        const file = fileInput.files?.[0];
        if (!file) return;

        revokeObjectUrl();
        objectUrl = URL.createObjectURL(file);
        sourceUrl = objectUrl;
        preview.src = sourceUrl;
        preview.hidden = false;
        preview.style.objectPosition = '50% center';
        positionInput.value = '50% center';
        openButton.disabled = false;
        openButton.click();
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
                height: 900,
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
            revokeObjectUrl();
            objectUrl = URL.createObjectURL(file);
            sourceUrl = objectUrl;
            preview.src = sourceUrl;
            preview.style.objectPosition = '50% center';
            positionInput.value = '50% center';
            setStatus('Komposisi 16:9 berhasil diterapkan.', 'success');
            cancelButton?.click();
        } catch {
            applyButton.disabled = false;
            setStatus('Komposisi belum dapat diterapkan. Silakan coba kembali.', 'error');
        }
    });

    window.addEventListener('beforeunload', revokeObjectUrl, { once: true });
}
