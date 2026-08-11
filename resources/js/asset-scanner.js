import {Html5Qrcode, Html5QrcodeSupportedFormats} from 'html5-qrcode';

const root = document.querySelector('[data-asset-scanner]');
const dialog = document.querySelector('[data-asset-scan-dialog]');

if (root && dialog instanceof HTMLDialogElement) {
    const readerId = 'asset-qr-reader';
    const openButtons = document.querySelectorAll('[data-open-asset-scanner]');
    const closeButtons = dialog.querySelectorAll('[data-close-asset-scanner]');
    const startButton = root.querySelector('[data-scanner-start]');
    const stopButton = root.querySelector('[data-scanner-stop]');
    const fileInput = root.querySelector('[data-scanner-file]');
    const status = root.querySelector('[data-scanner-status]');
    const placeholder = root.querySelector('[data-scanner-placeholder]');
    const target = root.querySelector('[data-scanner-target]');
    const manualForm = root.querySelector('[data-scanner-manual]');
    const manualInput = manualForm.querySelector('input[name="asset_code"]');
    const lookupError = root.querySelector('[data-scanner-lookup-error]');
    const scanner = new Html5Qrcode(readerId, {
        formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
        verbose: false,
    });
    let running = false;
    let resolving = false;

    const setStatus = (message, type = '') => {
        status.textContent = message;
        status.dataset.type = type;
    };

    const setRunning = (active) => {
        running = active;
        startButton.hidden = active;
        stopButton.hidden = !active;
        placeholder.hidden = active;
        target.hidden = !active;
    };

    const showLookupError = (message = '') => {
        lookupError.textContent = message;
        lookupError.hidden = message === '';
    };

    const stopScanner = async (showMessage = true) => {
        if (!running) return;
        await scanner.stop();
        setRunning(false);
        if (showMessage) setStatus('Kamera dimatikan. Anda dapat memulai pemindaian kembali.');
    };

    const closeScanner = async () => {
        await stopScanner(false);
        if (dialog.open) dialog.close();
    };

    const openScanner = () => {
        resolving = false;
        setRunning(false);
        showLookupError();
        setStatus('Siap memindai QR aset.');
        if (!dialog.open) dialog.showModal();
    };

    const inspectionUrlFor = (publicId) => root.dataset.inspectionUrl.replace('ASSET_PUBLIC_ID', publicId);

    const findScannedAsset = (value) => {
        const text = value.trim();
        const assetCode = text.match(/^AWA-[A-Z]{3}-\d{3,}$/i)?.[0];
        if (assetCode) return {type: 'asset_code', value: assetCode.toUpperCase()};

        try {
            const parsed = new URL(text);
            const match = parsed.pathname.match(/\/assets\/(?:verify|inspect)\/([0-9a-f-]{36})\/?$/i);
            if (match) return {type: 'inspection_url', value: inspectionUrlFor(match[1])};
        } catch {
            const publicId = text.match(/^[0-9a-f]{8}-[0-9a-f-]{27}$/i)?.[0];
            if (publicId) return {type: 'inspection_url', value: inspectionUrlFor(publicId)};
        }

        return null;
    };

    const lookupAssetCode = async (assetCode) => {
        const lookupUrl = new URL(root.dataset.lookupUrl, window.location.origin);
        lookupUrl.searchParams.set('asset_code', assetCode.toUpperCase());
        showLookupError();
        setStatus('Mencari Asset ID.');

        const response = await fetch(lookupUrl, {
            credentials: 'same-origin',
            headers: {Accept: 'application/json'},
        });
        const data = await response.json();

        if (!response.ok) {
            const message = data.message || Object.values(data.errors || {})[0]?.[0] || 'Asset ID tidak dapat ditemukan.';
            showLookupError(message);
            setStatus('Aset tidak ditemukan.', 'error');
            return null;
        }

        return data.inspection_url;
    };

    const handleResult = async (decodedText) => {
        if (resolving) return;
        const result = findScannedAsset(decodedText);

        if (!result) {
            setStatus('QR tidak dikenali sebagai label aset Alpha Welding Academy.', 'error');
            return;
        }

        resolving = true;

        try {
            const destination = result.type === 'asset_code'
                ? await lookupAssetCode(result.value)
                : result.value;

            if (!destination) {
                resolving = false;
                return;
            }

            setStatus('Aset ditemukan. Membuka checklist inspeksi.', 'success');
            if (running) await scanner.stop();
            window.location.assign(destination);
        } catch {
            resolving = false;
            setStatus('Terjadi kendala saat membuka data aset. Silakan coba kembali.', 'error');
        }
    };

    openButtons.forEach((button) => button.addEventListener('click', openScanner));
    closeButtons.forEach((button) => button.addEventListener('click', () => void closeScanner()));

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) void closeScanner();
    });
    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        void closeScanner();
    });

    startButton.addEventListener('click', async () => {
        startButton.disabled = true;
        setStatus('Meminta izin kamera.');

        try {
            await scanner.start(
                {facingMode: 'environment'},
                {
                    fps: 10,
                    qrbox: (width, height) => {
                        const size = Math.floor(Math.min(width, height) * 0.68);
                        return {width: size, height: size};
                    },
                },
                (decodedText) => void handleResult(decodedText),
                () => {},
            );
            setRunning(true);
            setStatus('Kamera aktif. Arahkan ke QR pada label aset.', 'success');
        } catch {
            setRunning(false);
            setStatus('Kamera tidak dapat dibuka. Periksa izin kamera atau gunakan foto dan Asset ID.', 'error');
        } finally {
            startButton.disabled = false;
        }
    });

    stopButton.addEventListener('click', () => void stopScanner());

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files?.[0];
        if (!file) return;

        try {
            if (running) await stopScanner(false);
            setStatus('Membaca QR dari foto.');
            const decodedText = await scanner.scanFile(file, true);
            await handleResult(decodedText);
        } catch {
            setStatus('QR tidak ditemukan pada foto. Coba ambil gambar yang lebih jelas.', 'error');
        } finally {
            fileInput.value = '';
        }
    });

    manualForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const assetCode = manualInput.value.trim();
        if (!assetCode || resolving) return;
        resolving = true;

        try {
            const destination = await lookupAssetCode(assetCode);
            if (destination) {
                setStatus('Aset ditemukan. Membuka checklist inspeksi.', 'success');
                window.location.assign(destination);
                return;
            }
        } catch {
            showLookupError('Terjadi kendala saat mencari Asset ID. Silakan coba kembali.');
            setStatus('Pencarian aset gagal.', 'error');
        }

        resolving = false;
    });

    if (new URLSearchParams(window.location.search).get('scan') === '1') {
        openScanner();
        const cleanUrl = new URL(window.location.href);
        cleanUrl.searchParams.delete('scan');
        window.history.replaceState({}, '', cleanUrl);
    }

    window.addEventListener('pagehide', () => {
        if (running) void scanner.stop();
    });
}
