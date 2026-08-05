# Welding School Management System

## Ringkasan Proyek

Welding School Management System adalah platform pelatihan welding yang akan tersedia melalui web dan aplikasi Android. Sistem dirancang untuk menangani perjalanan peserta mulai dari melihat program, mendaftar, membayar, mengikuti pelatihan, menjalani penilaian, hingga menerima sertifikat yang dapat diverifikasi.

Tahap pengerjaan saat ini difokuskan pada frontend web terlebih dahulu. Backend Laravel dan aplikasi Android akan dikembangkan setelah struktur halaman, komponen, dan alur utama frontend lebih stabil.

## Tujuan Produk

- Mempermudah calon peserta menemukan dan mendaftar program welding.
- Menangani pendaftaran dan pembayaran secara terintegrasi.
- Membantu admin mengelola program, batch, peserta, dan sertifikat.
- Membantu instruktur mengelola kehadiran, tugas, logbook, dan penilaian.
- Memberikan dashboard pembelajaran kepada peserta.
- Menyediakan verifikasi sertifikat secara publik.
- Menyimpan riwayat pelatihan dan kompetensi peserta.

## Platform

### Web Publik

Digunakan oleh pengunjung dan calon peserta untuk:

- Melihat informasi Welding School.
- Melihat daftar dan detail program pelatihan.
- Melihat jadwal, harga, persyaratan, fasilitas, dan kuota batch.
- Membuat akun dan melakukan pendaftaran.
- Melakukan pembayaran.
- Melihat aktivitas sekolah.
- Memverifikasi sertifikat.

### Web Internal

Digunakan oleh admin dan instruktur untuk mengelola kegiatan operasional.

### Android

Direncanakan untuk peserta setelah API dan proses bisnis utama stabil. Fitur utamanya meliputi materi, jadwal, tugas, kehadiran, logbook, nilai, notifikasi, dan sertifikat.

## Role MVP

### Pengunjung

Pengguna tanpa akun yang dapat melihat konten publik, program, aktivitas, dan verifikasi sertifikat.

### Calon Peserta

Pengguna yang sudah membuat akun tetapi belum menyelesaikan pembayaran. Calon peserta dapat mengisi pendaftaran, upload dokumen, memilih batch, melihat invoice, dan melakukan pembayaran.

### Peserta

Pengguna yang sudah membayar dan resmi masuk ke batch. Peserta dapat mengakses materi, jadwal, tugas, logbook, nilai, progres, dan sertifikat.

### Instruktur atau Asesor

Mengelola batch yang ditugaskan, kehadiran, tugas, logbook, penilaian kompetensi, feedback, dan remedial.

### Admin

Mengelola program, kurikulum, modul, batch, pendaftaran, pembayaran, peserta, perpindahan batch, refund, kelulusan, dan sertifikat.

### Super Admin

Memiliki akses penuh, termasuk pengelolaan role, permission, admin, dan konfigurasi sistem.

## Role dan Permission

Backend direncanakan menggunakan Laravel dengan Spatie Laravel Permission.

- Role digunakan sebagai kelompok permission.
- Permission menentukan tindakan yang boleh dilakukan.
- Laravel Policy membatasi data berdasarkan kepemilikan atau batch.
- Super Admin memperoleh seluruh akses melalui Laravel Gate.
- Pengaturan role dan permission hanya dapat dilakukan oleh Super Admin.

Contoh permission:

- `program.view`
- `program.manage`
- `application.create`
- `application.review`
- `payment.view-own`
- `payment.manage`
- `batch.transfer-request`
- `batch.transfer-approve`
- `attendance.view-own`
- `attendance.manage`
- `logbook.submit`
- `logbook.review`
- `assessment.manage`
- `certificate.view-own`
- `certificate.issue`
- `certificate.revoke`
- `refund.request`
- `refund.review`

## Alur Utama MVP

### 1. Pendaftaran dan Pembayaran

1. Pengunjung melihat program pelatihan.
2. Pengunjung memilih program dan batch.
3. Pengunjung membuat akun ringan menggunakan nama, email, dan nomor HP.
4. Pengguna mengisi formulir kelayakan dan upload dokumen.
5. Admin memeriksa pendaftaran jika program memerlukan verifikasi.
6. Sistem menampilkan ringkasan biaya dan kebijakan refund.
7. Sistem membuat invoice pembayaran penuh.
8. Kursi pada batch ditahan sementara.
9. Pengguna membayar melalui payment gateway.
10. Backend menerima dan memvalidasi webhook.
11. Pembayaran ditandai lunas.
12. Reservasi batch dikonfirmasi.
13. Enrollment dibuat dan akun berubah menjadi peserta aktif.

### 2. Reservasi dan Perpindahan Batch

- Batch dipilih sebelum invoice dibuat.
- Invoice membuat reservasi kursi sementara.
- Reservasi berakhir ketika invoice kedaluwarsa.
- Pembayaran berhasil mengonfirmasi kursi secara permanen.
- Peserta hanya dapat mengajukan perpindahan batch.
- Admin menyetujui perpindahan jika aturan dan kuota terpenuhi.
- Semua perpindahan disimpan dalam riwayat.
- Batch yang dibatalkan sekolah dapat diganti atau diberikan refund penuh.

### 3. Proses Pelatihan

1. Peserta aktif dimasukkan ke batch.
2. Sistem menampilkan jadwal dan instruktur.
3. Peserta mengikuti orientasi dan safety induction.
4. Peserta mengikuti pre-test.
5. Peserta menjalani siklus teori, tugas, praktik, dan logbook.
6. Instruktur mencatat kehadiran.
7. Instruktur memeriksa tugas dan logbook.
8. Instruktur atau asesor memberikan nilai kompetensi.
9. Peserta yang belum kompeten mengikuti remedial.
10. Sistem memeriksa syarat ujian dan kelulusan.
11. Admin atau asesor mengesahkan kelulusan.

### 4. Sertifikasi

Sertifikasi dibedakan menjadi:

- Sertifikat internal Welding School.
- Sertifikat eksternal dari LSP atau lembaga penerbit.

Sertifikat internal dapat dibuat oleh sistem. Sertifikat eksternal hanya dicatat berdasarkan data dan file resmi yang diterima dari lembaga penerbit.

Setiap sertifikat menyimpan:

- Nomor sertifikat.
- Nama pemegang.
- Program dan proses welding.
- Posisi pengelasan.
- Standar pengujian.
- Material dan rentang ketebalan.
- Tanggal terbit dan berakhir.
- Lembaga penerbit.
- File sertifikat.
- QR verifikasi.
- Status aktif, kedaluwarsa, atau dicabut.

### 5. Pembatalan dan Refund

Status refund:

`Requested → Under Review → Approved/Rejected → Processing → Refunded/Failed`

Usulan awal kebijakan refund:

| Kondisi | Refund |
| --- | ---: |
| Batch dibatalkan Welding School | 100% |
| Peserta membatalkan minimal H-7 | 90% |
| Peserta membatalkan H-6 sampai H-3 | 50% |
| Peserta membatalkan kurang dari H-3 | 0% |
| Pelatihan sudah dimulai | 0% |
| Kondisi medis khusus | Pemeriksaan manual |

Nilai tersebut masih berupa usulan dan harus dapat diatur melalui sistem.

## Kurikulum dan Aturan Dinamis

Struktur kurikulum:

`Program → Versi Kurikulum → Modul → Materi/Tugas/Praktik → Penilaian`

Admin dapat membuat modul baru atau menggunakan ulang modul yang sudah tersedia.

Konfigurasi yang dapat diatur per program:

- Minimum kehadiran.
- Minimum nilai teori.
- Minimum nilai praktik.
- Minimum jam praktik.
- Minimum jumlah logbook.
- Maksimum remedial.
- Biaya remedial.
- Safety induction wajib atau tidak.
- Semua tugas wajib selesai atau tidak.
- Persetujuan asesor wajib atau tidak.
- Pembayaran wajib lunas sebelum ujian atau sertifikat.

Kurikulum dan aturan menggunakan versioning. Ketika batch dibuat, sistem menyimpan snapshot versi kurikulum dan aturan. Perubahan berikutnya hanya berlaku untuk batch baru.

## Scope MVP

### Termasuk MVP

- Website publik.
- Daftar dan detail program.
- Authentication dan profil.
- Pendaftaran dan upload dokumen.
- Program, kurikulum, modul, dan batch.
- Reservasi kuota.
- Invoice pembayaran penuh.
- Payment gateway dan webhook.
- Dashboard peserta.
- Materi dan jadwal.
- Kehadiran.
- Tugas dan logbook.
- Penilaian dan remedial.
- Sertifikat dan verifikasi QR.
- Pembatalan dan refund.
- Role dan permission.
- Laporan dasar.

### Ditunda Setelah MVP

- Direktori welder publik.
- Sistem rekrutmen dan permintaan kandidat.
- Lowongan kerja.
- Penempatan kerja lengkap.
- Keuangan dan akuntansi lengkap.
- Inventaris dan consumable.
- Pengelolaan ISO.
- Tender.
- Chat real-time.
- Mode offline Android.
- Multi-cabang.

## Arah Visual Frontend

Frontend mengikuti gaya prototype yang sudah disiapkan:

- Warna utama navy gelap.
- Warna aksen oranye.
- Latar terang dengan kartu putih.
- Foto welding dan workshop sebagai visual utama.
- Tampilan profesional dan industrial.
- Navigasi publik sederhana.
- Kartu statistik dengan ikon dan warna pendukung.
- Kartu program dengan gambar, durasi, kuota, harga, dan status.
- Dashboard menggunakan sidebar navy dan konten berbasis kartu.
- Desain responsif untuk desktop, tablet, dan mobile.

## Halaman Frontend Prioritas

Tahap frontend pertama:

1. Beranda publik.
2. Daftar program.
3. Detail program.
4. Login dan registrasi.
5. Formulir pendaftaran.
6. Ringkasan pesanan dan pembayaran.
7. Verifikasi sertifikat.

Tahap berikutnya:

1. Dashboard peserta.
2. Materi.
3. Tugas.
4. Jadwal.
5. Kehadiran.
6. Logbook.
7. Kompetensi dan nilai.
8. Sertifikat.
9. Dashboard instruktur.
10. Dashboard admin.

## Strategi Implementasi

Frontend tidak akan diselesaikan seluruhnya menggunakan data dummy sebelum backend dibuat. Pengembangan akan dilakukan dalam vertical slice.

Vertical slice pertama:

`Daftar program → Detail program → Buat akun → Pendaftaran → Pilih batch → Invoice`

Setelah frontend alur pertama stabil, backend dan API dapat dihubungkan tanpa mengubah struktur tampilan secara besar.

## Referensi Flow

Flow sistem dapat dilihat dan direvisi pada FigJam:

[Welding School System Flow](https://www.figma.com/board/kDzkbQR9wyMluxSbE8HTDv)

## Status Proyek

- Analisis sistem: selesai untuk alur utama MVP.
- Flow pendaftaran dan pembayaran: selesai.
- Flow pelatihan: selesai.
- Flow sertifikasi: selesai.
- Flow batch dan refund: selesai.
- Flow kurikulum dan versioning: selesai.
- Role dan permission: rancangan awal selesai.
- Frontend: belum dimulai.
- Backend: belum dimulai.
- Android: belum dimulai.
