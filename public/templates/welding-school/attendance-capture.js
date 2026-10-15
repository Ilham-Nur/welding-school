(() => {
  const root = document.querySelector('[data-selfie-attendance]');
  if (!root) return;
  const video = root.querySelector('[data-camera]');
  const preview = root.querySelector('[data-preview]');
  const placeholder = root.querySelector('[data-camera-placeholder]');
  const message = root.querySelector('[data-capture-message]');
  const start = root.querySelector('[data-camera-start]');
  const capture = root.querySelector('[data-camera-capture]');
  const retake = root.querySelector('[data-camera-retake]');
  const submit = root.querySelector('[data-camera-submit]');
  const buttons = [start, capture, retake, submit];
  let stream = null;
  let evidence = null;
  let previewUrl = null;
  let expiryTimer = null;
  let disposed = false;
  let busy = false;
  let generation = 0;

  function say(text, error = false) {
    message.textContent = text;
    message.dataset.error = String(error);
    if (error) window.AppToast?.show(text, 'danger');
  }

  function stopCamera() {
    stream?.getTracks().forEach((track) => track.stop());
    stream = null;
    video.srcObject = null;
  }

  function reset() {
    generation += 1;
    stopCamera();
    evidence = null;
    clearTimeout(expiryTimer);
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    previewUrl = null;
    preview.removeAttribute('src');
    video.hidden = preview.hidden = capture.hidden = retake.hidden = submit.hidden = true;
    placeholder.hidden = start.hidden = false;
  }

  function supported() {
    if (!window.isSecureContext) throw new Error('Buka halaman melalui HTTPS untuk menggunakan kamera dan lokasi.');
    if (!navigator.mediaDevices?.getUserMedia || !navigator.geolocation) throw new Error('Browser belum mendukung kamera atau lokasi. Gunakan browser terbaru di ponsel.');
  }

  function position() {
    return new Promise((resolve, reject) => navigator.geolocation.getCurrentPosition(resolve, (error) => {
      reject(new Error(error.code === 1
        ? 'Izin lokasi ditolak. Izinkan lokasi presisi pada pengaturan browser/perangkat.'
        : 'Lokasi tidak ditemukan. Aktifkan GPS, coba di area terbuka, lalu ulangi.'));
    }, { enableHighAccuracy: true, maximumAge: 0, timeout: 20000 }));
  }

  function validateLocation(location) {
    const point = location.coords;
    const rad = (value) => value * Math.PI / 180;
    const a = Math.sin(rad(point.latitude - Number(root.dataset.latitude)) / 2) ** 2
      + Math.cos(rad(Number(root.dataset.latitude))) * Math.cos(rad(point.latitude))
      * Math.sin(rad(point.longitude - Number(root.dataset.longitude)) / 2) ** 2;
    const distance = 6371000 * 2 * Math.asin(Math.sqrt(Math.min(1, Math.max(0, a))));
    if (!Number.isFinite(point.accuracy) || point.accuracy > Number(root.dataset.maxAccuracy)) {
      throw new Error(`Lokasi belum cukup akurat (±${Math.ceil(point.accuracy)} meter). Aktifkan lokasi presisi dan coba lagi.`);
    }
    if (distance + point.accuracy > Number(root.dataset.radius)) {
      throw new Error(`Anda belum terdeteksi di dalam area absensi (jarak ${Math.ceil(distance)} meter, akurasi ±${Math.ceil(point.accuracy)} meter). Dekati titik pusat sesi.`);
    }
    return distance;
  }

  async function request(url, body) {
    const response = await fetch(url, {
      method: 'POST', credentials: 'same-origin', body,
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok || response.redirected) {
      throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Permintaan gagal. Periksa koneksi dan sesi login, kemudian coba lagi.');
    }
    return result;
  }

  async function work(callback) {
    if (busy || disposed) return;
    busy = true;
    buttons.forEach((button) => { button.disabled = true; });
    try { await callback(); } catch (error) {
      if (!disposed) say(error.message || 'Pengambilan selfie gagal. Coba lagi.', true);
    } finally {
      busy = false;
      buttons.forEach((button) => { button.disabled = false; });
      if (!evidence) submit.disabled = true;
    }
  }

  async function openCamera() {
    reset();
    const current = generation;
    supported();
    say('Membuka kamera depan dan memeriksa lokasi…');
    try {
      const media = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 960 } }, audio: false });
      if (disposed || document.hidden || current !== generation) { media.getTracks().forEach((track) => track.stop()); return; }
      stream = media;
      video.srcObject = stream;
      video.hidden = false;
      placeholder.hidden = true;
      await video.play();
      if (disposed || document.hidden || current !== generation) return;
      const location = await position();
      if (disposed || document.hidden || current !== generation) return;
      const distance = validateLocation(location);
      start.hidden = true;
      capture.hidden = false;
      say(`Lokasi sesuai: jarak ${Math.ceil(distance)} meter, akurasi ±${Math.ceil(location.coords.accuracy)} meter. Pastikan wajah jelas, lalu ambil selfie.`);
    } catch (error) {
      reset();
      throw new Error(error.name === 'NotAllowedError' ? 'Izin kamera ditolak. Izinkan kamera pada pengaturan browser.' : error.message);
    }
  }

  start.addEventListener('click', () => work(openCamera));
  retake.addEventListener('click', () => work(openCamera));
  capture.addEventListener('click', () => work(async () => {
    const current = generation;
    if (!stream || !video.videoWidth || !video.videoHeight || !stream.getVideoTracks().some((track) => track.readyState === 'live')) {
      reset();
      throw new Error('Kamera belum siap. Buka kamera kembali.');
    }
    say('Memeriksa ulang lokasi sebelum foto…');
    const challengeBody = new FormData();
    challengeBody.set('kind', root.dataset.kind);
    const challenge = await request(root.dataset.challengeUrl, challengeBody);
    if (disposed || document.hidden || current !== generation) return;
    const location = await position();
    if (disposed || document.hidden || current !== generation) return;
    validateLocation(location);
    const scale = Math.min(1, 1280 / Math.max(video.videoWidth, video.videoHeight));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(video.videoWidth * scale);
    canvas.height = Math.round(video.videoHeight * scale);
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
    if (disposed || document.hidden || current !== generation) return;
    if (!blob) throw new Error('Foto tidak dapat diambil. Coba lagi.');
    evidence = { blob, token: challenge.token, location, capturedAt: Date.now() };
    stopCamera();
    previewUrl = URL.createObjectURL(blob);
    preview.src = previewUrl;
    preview.hidden = retake.hidden = submit.hidden = false;
    video.hidden = capture.hidden = true;
    say('Periksa selfie Anda lalu kirim dalam 30 detik. Foto ulang jika wajah belum jelas.');
    expiryTimer = setTimeout(() => {
      evidence = null;
      submit.disabled = true;
      say('Bukti lokasi sudah kedaluwarsa. Tekan Foto ulang untuk mengambil selfie dan lokasi baru.', true);
    }, 30000);
  }));

  submit.addEventListener('click', () => work(async () => {
    if (!evidence || Date.now() - evidence.location.timestamp > 30000) {
      evidence = null;
      throw new Error('Lokasi sudah kedaluwarsa. Ambil foto ulang.');
    }
    say('Mengirim selfie dan lokasi…');
    const body = new FormData();
    body.set('kind', root.dataset.kind);
    body.set('token', evidence.token);
    body.set('latitude', evidence.location.coords.latitude);
    body.set('longitude', evidence.location.coords.longitude);
    body.set('accuracy', evidence.location.coords.accuracy);
    body.set('location_timestamp', evidence.location.timestamp);
    body.set('photo', evidence.blob, 'selfie.jpg');
    const result = await request(root.dataset.submitUrl, body);
    say(result.message);
    try { window.sessionStorage?.setItem('participant-feedback-success', result.message); } catch (_) { /* Optional flash only. */ }
    reset();
    window.location.reload();
  }));

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) { reset(); say('Kamera ditutup. Buka kembali untuk mengambil selfie baru.'); }
  });
  window.addEventListener('pagehide', () => { disposed = true; reset(); });
  window.addEventListener('pageshow', () => { disposed = false; });
})();
