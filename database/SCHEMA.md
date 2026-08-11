# Struktur Database Alur Pendaftaran Peserta

## Alur utama

1. Pengguna membuat akun atau login menggunakan Google.
2. Pengguna memilih program dan, jika tersedia, batch pelatihan.
3. Pengguna melengkapi profil, mengunggah dokumen, lalu mengirim pendaftaran.
4. Admin memeriksa data dan dokumen.
5. Pendaftaran yang disetujui memperoleh invoice.
6. Pengguna membayar melalui payment gateway.
7. Webhook terverifikasi mengubah invoice menjadi lunas dan membuat enrollment.
8. Dashboard peserta mengambil data dari enrollment aktif.

## Relasi tabel

```mermaid
erDiagram
    USERS ||--o{ SOCIAL_ACCOUNTS : login
    USERS ||--o| PARTICIPANT_PROFILES : memiliki
    USERS ||--o{ TRAINING_APPLICATIONS : mengajukan
    TRAINING_PROGRAMS ||--o{ TRAINING_BATCHES : memiliki
    TRAINING_PROGRAMS ||--o{ TRAINING_APPLICATIONS : dipilih
    TRAINING_BATCHES ||--o{ TRAINING_APPLICATIONS : dipilih
    TRAINING_APPLICATIONS ||--o{ APPLICATION_DOCUMENTS : melampirkan
    TRAINING_APPLICATIONS ||--o{ APPLICATION_STATUS_HISTORIES : mencatat
    TRAINING_APPLICATIONS ||--o| INVOICES : ditagihkan
    INVOICES ||--o{ PAYMENTS : dicoba
    TRAINING_APPLICATIONS ||--o| ENROLLMENTS : menghasilkan
    USERS ||--o{ ENROLLMENTS : mengikuti
    USERS ||--o{ ACTIVITIES : menulis
```

## Tanggung jawab tabel

| Tabel | Fungsi |
| --- | --- |
| `users` | Akun, role, status, dan waktu login terakhir |
| `social_accounts` | Identitas Google OAuth; akun Gmail tidak menyimpan password lokal |
| `participant_profiles` | Data diri terbaru milik pengguna |
| `training_programs` | Master program pelatihan |
| `training_batches` | Jadwal, periode pendaftaran, dan kapasitas kelas |
| `training_applications` | Pendaftaran serta hasil verifikasi admin |
| `application_documents` | Metadata berkas persyaratan dan hasil pemeriksaannya |
| `application_status_histories` | Audit perubahan status pendaftaran |
| `invoices` | Tagihan resmi setelah pendaftaran disetujui |
| `payments` | Setiap percobaan transaksi pada payment gateway |
| `payment_webhooks` | Payload callback gateway untuk validasi dan idempotensi |
| `enrollments` | Hak akses peserta ke dashboard program/batch |
| `activities` | Konten aktivitas publik, metadata foto, status/jadwal publikasi, unggulan, dan jumlah dilihat |
| `assets` | Inventaris alat, identitas permanen, foto, kondisi, jadwal inspeksi, dan data kalibrasi |

## Status yang disarankan

- `training_applications`: `draft`, `submitted`, `under_review`, `revision_required`, `approved`, `rejected`, `cancelled`
- `application_documents`: `pending`, `approved`, `rejected`
- `invoices`: `unpaid`, `paid`, `expired`, `cancelled`, `refunded`
- `payments`: `pending`, `paid`, `failed`, `expired`, `cancelled`, `refunded`
- `enrollments`: `active`, `completed`, `cancelled`, `suspended`
- `activities`: `draft`, `published`, `archived`

## Struktur aktivitas publik

| Kolom | Fungsi tampilan |
| --- | --- |
| `title`, `slug` | Judul kartu/detail dan identitas aktivitas |
| `category` | Label dan filter kategori |
| `excerpt` | Ringkasan pada kartu aktivitas |
| `content` | Isi paragraf pada halaman detail |
| `image_path`, `image_alt`, `image_position` | Foto utama, aksesibilitas, dan kompatibilitas posisi; komposisi baru disimpan dalam rasio 16:9 melalui Cropper.js |
| `status`, `published_at` | Draft, terjadwal, terbit, atau arsip |
| `is_featured` | Sorotan utama halaman aktivitas; hanya satu aktivitas aktif pada satu waktu |
| `view_count` | Dasar urutan aktivitas terpopuler |
| `author_id` | Admin yang membuat aktivitas |

## Aturan transaksi penting

- Invoice hanya dibuat untuk pendaftaran berstatus `approved`.
- Status lunas hanya boleh berasal dari webhook yang tanda tangannya valid, bukan dari redirect browser.
- Proses webhook harus memakai `gateway + event_id` sebagai kunci idempotensi.
- Perubahan invoice menjadi `paid` dan pembuatan enrollment harus dilakukan dalam satu transaksi database.
- `personal_data_snapshot` menyimpan salinan data saat formulir dikirim agar hasil verifikasi tetap dapat diaudit walaupun profil pengguna diperbarui.
- File dokumen disimpan di object storage/private storage; database hanya menyimpan metadata dan lokasi file.
- Foto aktivitas disimpan di public storage; database menyimpan lokasi dan metadata tampilannya.
- Foto aset disimpan di public storage dalam komposisi 4:3; database menyimpan lokasi filenya.
- File sertifikat kalibrasi aset disimpan di private storage; database menyimpan lokasi, nama asli, tipe, dan ukuran file.
- Dashboard peserta hanya dapat dibuka bila tersedia enrollment berstatus `active`.
