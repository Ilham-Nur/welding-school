(() => {
  'use strict';
  const form = document.querySelector('[data-attendance-area]');
  if (!form) return;
  const locationButton = form.querySelector('[data-area-location]');
  const mapsButton = form.querySelector('[data-area-maps-resolve]');
  const mapsInput = form.querySelector('[data-area-maps-input]');
  const confirm = form.querySelector('[data-area-confirm]');
  const preview = form.querySelector('[data-area-preview]');
  const previewText = form.querySelector('[data-area-preview-text]');
  const mapLink = form.querySelector('[data-area-map-link]');
  const message = form.querySelector('[data-area-message]');
  const latitude = form.elements.attendance_latitude;
  const longitude = form.elements.attendance_longitude;
  const radius = form.elements.attendance_radius_meters;
  let revision = 0;
  let busy = false;
  let appliedInput = '';

  function notice(text, error = false) {
    message.dataset.error = String(error);
    message.textContent = text;
    if (error) window.AppToast?.show(text, 'danger');
  }
  function updatePreview() {
    const lat = Number(latitude.value);
    const lng = Number(longitude.value);
    const meters = Number(radius.value);
    const valid = latitude.value.trim() !== '' && longitude.value.trim() !== ''
      && Number.isFinite(lat) && Math.abs(lat) <= 90 && Number.isFinite(lng) && Math.abs(lng) <= 180
      && Number.isInteger(meters) && meters >= 25 && meters <= 1000;
    preview.hidden = !valid;
    if (!valid) { mapLink.removeAttribute('href'); return; }
    previewText.textContent = 'Titik pusat: ' + lat.toFixed(7) + ', ' + lng.toFixed(7) + ' · Radius: ' + meters + ' meter';
    mapLink.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(lat.toFixed(7) + ',' + lng.toFixed(7));
  }
  function changed(clearMaps = false) {
    revision += 1;
    confirm.checked = false;
    if (clearMaps) { mapsInput.value = ''; appliedInput = ''; }
    updatePreview();
  }
  function working(value) {
    busy = value;
    locationButton.disabled = value;
    mapsButton.disabled = value;
  }
  [latitude, longitude].forEach((field) => field.addEventListener('input', () => changed(true)));
  radius.addEventListener('input', () => changed());
  mapsInput.addEventListener('input', () => changed());

  mapsButton.addEventListener('click', async () => {
    if (busy) return;
    const value = mapsInput.value.trim();
    if (!value) { notice('Tempel link pin Google Maps atau koordinat latitude, longitude terlebih dahulu.', true); return; }
    changed();
    const requestedRevision = revision;
    working(true);
    notice('Membaca titik Google Maps…');
    try {
      const response = await fetch(form.dataset.resolveUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ maps_location: value }),
      });
      if (response.redirected) throw new Error('Sesi login berakhir. Muat ulang halaman dan login kembali.');
      const data = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || (response.status === 429 ? 'Terlalu banyak percobaan. Tunggu satu menit lalu coba kembali.' : data.message || 'Link belum dapat dibaca. Coba salin koordinat pin dari Google Maps.'));
      if (requestedRevision !== revision) { notice('Input berubah saat pembacaan. Hasil lama tidak diterapkan; baca titik kembali.', true); return; }
      if (!Number.isFinite(data.latitude) || Math.abs(data.latitude) > 90 || !Number.isFinite(data.longitude) || Math.abs(data.longitude) > 180) throw new Error('Koordinat hasil pembacaan tidak valid.');
      latitude.value = data.latitude.toFixed(7);
      longitude.value = data.longitude.toFixed(7);
      appliedInput = value;
      updatePreview();
      notice('Titik terbaca, belum disimpan. Periksa pin di Google Maps dan radius, lalu centang konfirmasi sebelum Simpan area.');
    } catch (error) { notice(error.message || 'Link belum dapat dibaca. Isi koordinat secara manual.', true); }
    finally { working(false); }
  });

  locationButton.addEventListener('click', () => {
    if (busy) return;
    if (!window.isSecureContext || !navigator.geolocation) { notice('Lokasi perangkat memerlukan HTTPS (atau localhost) dan dukungan browser.', true); return; }
    changed();
    const requestedRevision = revision;
    working(true);
    notice('Mencari lokasi presisi…');
    navigator.geolocation.getCurrentPosition((position) => {
      working(false);
      if (requestedRevision !== revision) { notice('Input berubah saat mencari lokasi. Lokasi lama tidak diterapkan.', true); return; }
      if (position.coords.accuracy > 50) { notice('Akurasi ±' + Math.ceil(position.coords.accuracy) + ' meter belum cukup. Coba di area terbuka atau isi koordinat pusat lokasi yang sudah diketahui.', true); return; }
      latitude.value = position.coords.latitude.toFixed(7);
      longitude.value = position.coords.longitude.toFixed(7);
      mapsInput.value = '';
      appliedInput = '';
      updatePreview();
      notice('Titik perangkat diambil (akurasi ±' + Math.ceil(position.coords.accuracy) + ' meter). Periksa titik dan radius, lalu centang konfirmasi sebelum menyimpan.');
    }, (error) => {
      working(false);
      notice(error.code === 1 ? 'Izin lokasi ditolak. Izinkan lokasi pada pengaturan browser.' : 'Lokasi tidak ditemukan. Aktifkan lokasi presisi lalu coba lagi.', true);
    }, { enableHighAccuracy: true, maximumAge: 0, timeout: 20000 });
  });
  form.addEventListener('submit', (event) => {
    if (busy || (mapsInput.value.trim() && mapsInput.value.trim() !== appliedInput) || !confirm.checked) {
      event.preventDefault();
      notice(busy ? 'Tunggu sampai pembacaan titik selesai.' : 'Baca link terbaru dan konfirmasi titik serta radius sebelum menyimpan. Untuk input manual, kosongkan kolom link Google Maps.', true);
    }
  });
  updatePreview();
})();
