import Cropper from 'cropperjs';

const form = document.querySelector('[data-employee-form]');
const fileInput = document.querySelector('[data-employee-photo-input]');
const originalInput = document.querySelector('[data-employee-original-photo-input]');
const preview = document.querySelector('[data-employee-photo-preview]');
const placeholder = document.querySelector('[data-employee-photo-placeholder]');
const openButton = document.querySelector('[data-employee-photo-crop-open]');
const modal = document.getElementById('employee-photo-editor');
const cropperContainer = document.querySelector('[data-employee-photo-cropper]');
const applyButton = document.querySelector('[data-employee-photo-crop-apply]');
const cancelButton = document.querySelector('[data-employee-photo-crop-cancel]');
const resetButton = document.querySelector('[data-employee-photo-crop-reset]');
const zoomInButton = document.querySelector('[data-employee-photo-crop-zoom-in]');
const zoomOutButton = document.querySelector('[data-employee-photo-crop-zoom-out]');
const status = document.querySelector('[data-employee-photo-crop-status]');

const cropperTemplate = `
    <cropper-canvas background>
        <cropper-image translatable scalable initial-center-size="cover"></cropper-image>
        <cropper-handle action="move" plain></cropper-handle>
        <cropper-selection
            initial-coverage="1"
            aspect-ratio="1"
            initial-aspect-ratio="1"
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

function setInputFile(inputElement, file) {
    if (!inputElement) return;
    const transfer = new DataTransfer();
    transfer.items.add(file);
    inputElement.files = transfer.files;
}

function updateFileLabel(file) {
    const label = fileInput?.closest('[data-file-drop]')?.querySelector('[data-file-label]');
    if (label) label.textContent = `${file.name} (${Math.max(1, Math.round(file.size / 1024))} KB)`;
}

function showSelectedFile(file) {
    if (!file || !file.type.startsWith('image/')) {
        setStatus('File yang dipilih bukan foto yang didukung.', 'error');
        return;
    }

    if (originalInput) {
        setInputFile(originalInput, file);
    }

    updateFileLabel(file);
    revokeObjectUrl();
    objectUrl = URL.createObjectURL(file);
    sourceUrl = objectUrl;
    if (preview) {
        preview.src = sourceUrl;
        preview.style.display = 'block';
    }
    if (placeholder) placeholder.style.display = 'none';
    if (openButton) {
        openButton.disabled = false;
        openButton.click();
    }
}

async function initializeCropper() {
    if (!sourceUrl || !cropperContainer) return;

    cropperContainer.replaceChildren();
    if (applyButton) applyButton.disabled = true;
    setStatus('Menyiapkan foto karyawan...');

    const image = new Image();
    image.src = sourceUrl;
    image.alt = 'Foto karyawan yang sedang diatur';

    cropper = new Cropper(image, {
        container: cropperContainer,
        template: cropperTemplate,
    });

    const cropperImage = cropper.getCropperImage();

    try {
        await cropperImage?.$ready();
        cropperImage?.$center('cover');
        if (applyButton) applyButton.disabled = false;
        setStatus('Geser dan atur posisi foto agar wajah/profil karyawan terlihat pas.', 'ready');
    } catch {
        cropper = null;
        setStatus('Foto tidak dapat dibuka. Silakan pilih foto lain.', 'error');
    }
}

function imageFileName() {
    const originalName = originalInput?.files?.[0]?.name || fileInput?.files?.[0]?.name || 'foto-karyawan.jpg';
    const baseName = originalName.replace(/\.[^.]+$/, '').replace(/[^a-z0-9_-]+/gi, '-');
    return `${baseName || 'foto-karyawan'}-1x1.jpg`;
}

function canvasToBlob(canvas) {
    return new Promise((resolve, reject) => {
        canvas.toBlob(
            (blob) => blob ? resolve(blob) : reject(new Error('Hasil foto tidak tersedia.')),
            'image/jpeg',
            0.92,
        );
    });
}

if (form && fileInput && openButton && modal) {
    openButton.addEventListener('click', () => {
        window.setTimeout(initializeCropper, 0);
    });

    fileInput.addEventListener('change', () => {
        showSelectedFile(fileInput.files?.[0]);
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
        setStatus('Menerapkan posisi foto...');

        try {
            const canvas = await selection.$toCanvas({
                width: 1000,
                height: 1000,
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

            setInputFile(fileInput, file);
            updateFileLabel(file);
            revokeObjectUrl();
            objectUrl = URL.createObjectURL(file);
            sourceUrl = objectUrl;
            if (preview) {
                preview.src = sourceUrl;
                preview.style.display = 'block';
            }
            if (placeholder) placeholder.style.display = 'none';
            setStatus('Posisi foto karyawan berhasil disesuaikan.', 'success');
            cancelButton?.click();
        } catch {
            applyButton.disabled = false;
            setStatus('Posisi belum dapat diterapkan. Silakan coba kembali.', 'error');
        }
    });

    window.addEventListener('beforeunload', revokeObjectUrl, { once: true });
}
