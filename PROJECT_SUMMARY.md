# Alpha Academy Welding School Management System

## Ringkasan Proyek

Alpha Academy Welding School Management System adalah platform berbasis web yang menangani perjalanan peserta mulai dari menemukan program, mendaftar, membayar, mengikuti pelatihan, menjalani penilaian, hingga menerima sertifikat yang dapat diverifikasi.

Fondasi pelatihan tetap menjadi inti produk. Dalam proyeksi jangka panjang, perjalanan pengguna tidak berhenti setelah lulus: peserta menjadi alumni dengan profil kompetensi dan karier, sedangkan perusahaan atau recruiter dapat menemukan kandidat yang relevan secara aman dan terarah.

## Visi Akhir Produk (Proyeksi Pengembangan)

Visi akhir Alpha Academy adalah membangun ekosistem berkelanjutan:

`Calon peserta → Peserta → Alumni → Peluang kerja / Recruiter`

Pengembangan platform alumni dan recruiter merupakan arah produk setelah layanan inti pelatihan stabil. Bagian ini adalah proyeksi pengembangan ke depan, bukan pernyataan bahwa seluruh fiturnya sudah tersedia pada sistem produksi.

Ruang lingkup visi tersebut meliputi:

- Profil alumni untuk profesi Welder, Welding Inspector, Fitter, dan profesi teknis lain yang relevan.
- Dukungan lebih dari satu keahlian dan posisi atau kualifikasi pada setiap alumni, misalnya Welder dengan posisi 2G, 3G, dan 6G.
- Informasi status bekerja, ketersediaan, pengalaman, CV, dan riwayat karier alumni.
- Akun recruiter atau perusahaan yang melalui proses verifikasi.
- Pencarian, filter, shortlist, dan pengajuan permintaan kandidat berdasarkan profesi, kompetensi, lokasi, dan kesiapan kerja.
- Perlindungan data alumni melalui persetujuan akses dan pembatasan informasi kontak.
- Pencatatan hasil rekrutmen agar Alpha Academy dapat mengevaluasi dampak pelatihan terhadap karier alumni.

## Tujuan Produk

- Mempermudah calon peserta menemukan dan mendaftar program welding.
- Menangani pendaftaran dan pembayaran secara terintegrasi.
- Membantu admin mengelola program, batch, peserta, dan sertifikat.
- Membantu instruktur mengelola kehadiran, tugas, logbook, dan penilaian.
- Memberikan dashboard pembelajaran kepada peserta.
- Menyediakan verifikasi sertifikat secara publik.
- Menyimpan riwayat pelatihan dan kompetensi peserta.
- Menjaga hubungan dengan peserta setelah lulus melalui profil alumni dan perkembangan karier.
- Mempermudah recruiter menemukan alumni yang sesuai berdasarkan profesi, kompetensi, kualifikasi, domisili, dan status bekerja.
- Menggunakan data hasil kerja alumni sebagai bahan evaluasi kualitas program pelatihan.

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

### Portal Alumni (Fase Lanjutan)

Direncanakan sebagai ruang bagi alumni untuk memperbarui profesi, keahlian, posisi atau kualifikasi, pengalaman kerja, status bekerja, ketersediaan, dan persetujuan penggunaan data untuk kebutuhan rekrutmen.

### Portal Recruiter (Fase Lanjutan)

Direncanakan untuk perusahaan yang sudah terverifikasi agar dapat mencari alumni, menyimpan shortlist, mengajukan permintaan kandidat, dan mencatat perkembangan proses rekrutmen tanpa membuka data pribadi alumni secara bebas.

### Android

Direncanakan untuk peserta setelah API dan proses bisnis utama stabil. Fitur utamanya meliputi materi, jadwal, tugas, kehadiran, logbook, nilai, notifikasi, dan sertifikat.

## Peran Pengguna

### Pengunjung

Pengguna tanpa akun yang dapat melihat konten publik, program, aktivitas, dan verifikasi sertifikat.

### Calon Peserta

Pengguna yang sudah membuat akun tetapi belum menyelesaikan pembayaran. Calon peserta dapat mengisi pendaftaran, upload dokumen, memilih batch, melihat invoice, dan melakukan pembayaran.

### Peserta

Pengguna yang sudah membayar dan resmi masuk ke batch. Peserta dapat mengakses materi, jadwal, tugas, logbook, nilai, progres, dan sertifikat.

### Alumni (Fase Lanjutan)

Peserta yang telah lulus dan memiliki profil kompetensi serta karier. Alumni dapat mengatur status bekerja, ketersediaan, pengalaman, CV, dan persetujuan akses recruiter.

### Recruiter atau Perusahaan (Fase Lanjutan)

Perwakilan perusahaan yang telah diverifikasi. Recruiter dapat mencari alumni sesuai kebutuhan, membuat shortlist, dan mengajukan permintaan kandidat melalui alur yang tercatat.

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

- Direktori alumni produksi yang terhubung ke database.
- Portal alumni untuk pembaruan kompetensi, CV, status bekerja, dan persetujuan data.
- Verifikasi akun recruiter atau perusahaan.
- Shortlist, permintaan kandidat, dan alur rekrutmen.
- Lowongan kerja.
- Penempatan kerja lengkap.
- Keuangan dan akuntansi lengkap.
- Inventaris dan consumable.
- Pengelolaan ISO.
- Tender.
- Chat real-time.
- Mode offline Android.
- Multi-cabang.

## Roadmap Pengembangan Setelah MVP

### Fase 1 — Fondasi Pelatihan

- Menstabilkan website publik, akun peserta, pendaftaran, pembayaran, operasional pelatihan, penilaian, dan sertifikat.
- Menyelesaikan integrasi data agar alur peserta dari pendaftaran sampai kelulusan tercatat dengan konsisten.

### Fase 2 — Pengalaman Alumni

- Mengubah peserta yang lulus menjadi profil alumni.
- Menyediakan profil untuk Welder, Welding Inspector, Fitter, dan profesi teknis lain.
- Menyimpan beberapa keahlian serta posisi atau kualifikasi dalam satu profil.
- Memberikan kontrol kepada alumni atas status bekerja, ketersediaan, CV, dan persetujuan akses data.

### Fase 3 — Akses Recruiter

- Menyediakan registrasi dan verifikasi perusahaan.
- Menyediakan pencarian multi-profesi dan multi-keahlian, shortlist, serta permintaan kandidat.
- Membatasi data pribadi sampai alumni memberikan persetujuan atau proses rekrutmen memenuhi aturan.
- Mencatat tahapan rekrutmen secara transparan bagi Alpha Academy, alumni, dan recruiter.

### Fase 4 — Ekosistem Karier

- Menambahkan lowongan kerja dan pencocokan kandidat.
- Memantau penempatan dan perkembangan karier alumni.
- Menggunakan data hasil kerja untuk meningkatkan kurikulum dan menunjukkan dampak pelatihan kepada mitra industri.

### Yang Sudah Diprototipekan

- Daftar alumni publik dengan 20 data dummy hardcode: 10 Welder, 5 Welding Inspector, dan 5 Fitter.
- Filter profesi dan keahlian yang dapat memilih lebih dari satu opsi.
- Dukungan beberapa posisi atau kualifikasi pada satu alumni.
- Pagination dengan maksimal 10 alumni per halaman.
- Tampilan detail alumni dan pintu masuk akun recruiter.

Prototype tersebut masih menggunakan data frontend dan belum menjadi portal alumni atau recruiter yang terhubung ke database produksi.

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

Pengembangan alumni dan recruiter juga akan dilakukan secara vertical slice setelah fondasi pelatihan stabil:

`Profil alumni → Pencarian recruiter → Verifikasi perusahaan → Permintaan kandidat → Persetujuan alumni → Proses rekrutmen`

Dengan urutan tersebut, prototype daftar alumni yang tersedia sekarang menjadi referensi pengalaman pengguna, sedangkan database, keamanan data, verifikasi perusahaan, persetujuan alumni, dan proses rekrutmen dibangun bertahap pada fase lanjutan.

## Referensi Flow

Flow sistem dapat dilihat dan direvisi pada FigJam:

[Welding School System Flow](https://www.figma.com/board/kDzkbQR9wyMluxSbE8HTDv)

## Status Proyek

- Analisis sistem: selesai untuk alur utama MVP.
- Website publik dan company profile: tersedia.
- Frontend pendaftaran, pembayaran, dashboard peserta, admin, alumni, dan recruiter: tersedia sebagai implementasi atau prototype sesuai tahapnya.
- Fondasi backend Laravel untuk autentikasi, profil, program, batch, pendaftaran, invoice, dan webhook pembayaran: sudah diimplementasikan dan diuji.
- Enrollment setelah pembayaran berhasil: sudah diimplementasikan.
- Daftar alumni: prototype frontend dengan 20 data dummy, filter multi-profesi, filter multi-keahlian, dan pagination 10 data per halaman.
- Portal alumni produksi: belum diimplementasikan; masih menjadi proyeksi fase lanjutan.
- Portal recruiter produksi, verifikasi perusahaan, persetujuan alumni, dan alur rekrutmen: belum diimplementasikan; masih menjadi proyeksi fase lanjutan.
- Modul operasional pelatihan lengkap, sertifikasi penuh, refund, dan permission lanjutan: masih dikembangkan bertahap.
- Android: belum dimulai.
