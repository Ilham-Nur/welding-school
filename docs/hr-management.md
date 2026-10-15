# HR Management

Dropdown **HR Management** di sidebar berisi **Data Karyawan** dan **Master Jabatan**. Menu otomatis terbuka ketika berada di halaman HR.

## Mengatur persyaratan jabatan

1. Buka Master Jabatan, buat atau edit jabatan beserta departemen dan tanggung jawabnya.
2. Klik **Persyaratan · Lihat / Kelola** pada jabatan.
3. Daftar persyaratan ditampilkan sebagai tabel. Klik **Tambah Persyaratan** untuk membuka modal, lalu isi nama persyaratan, jenis (pendidikan/ijazah, sertifikat, pengalaman kerja, atau dokumen pendukung), dan kriteria yang harus diperiksa. Tombol **Edit** pada setiap baris membuka modal berisi data persyaratan tersebut.
4. Tentukan apakah wajib atau opsional, perlu tanggal kedaluwarsa, serta perlu verifikasi HR.

Contoh: "Ijazah minimal SMK", kategori Pendidikan / Ijazah, wajib, tanpa masa berlaku, perlu verifikasi HR. Kriteria pendidikan dan pengalaman diperiksa oleh HR berdasarkan bukti; sistem tidak membaca atau menilai isi dokumen secara otomatis.

## Melengkapi dokumen karyawan

1. Buka **Tambah Karyawan** atau **Edit Karyawan**, lalu pilih jabatan. Bagian **Dokumen Persyaratan Jabatan** otomatis menampilkan persyaratan jabatan tersebut.
2. Pada setiap persyaratan, pilih **Unggah berkas baru**, **Pakai ijazah Pendidikan Terakhir**, atau dokumen/riwayat pendidikan yang sudah tersimpan. File yang sudah ada digunakan melalui referensi tanpa disalin secara fisik. Ijazah Pendidikan Terakhir yang baru diunggah dapat langsung dipakai pada penyimpanan yang sama.
3. Isi tanggal kedaluwarsa jika persyaratan mewajibkannya. Pilih **Lengkapi nanti** jika bukti belum tersedia; data karyawan tetap dapat disimpan dengan status belum lengkap.
4. Klik **Simpan Data Karyawan** atau **Perbarui Data Karyawan**. Bukti yang membutuhkan verifikasi masuk ke status **Menunggu verifikasi**.
5. Pemeriksa membuka detail karyawan, lalu klik **Periksa** pada kolom **Aksi Verifikasi** di tabel **Kelengkapan Persyaratan Jabatan**. Modal berisi kriteria, masa berlaku, pratinjau PDF/gambar atau tautan unduh untuk format lain, serta pilihan **Setujui / Accept** dan **Tolak / Reject**. Catatan wajib saat menolak.
6. Perbaiki bukti yang ditolak melalui **Edit Karyawan**; alasan penolakan tampil di form. Penggantian bukti kembali menunggu verifikasi. Penyimpanan tanpa perubahan bukti atau masa berlaku mempertahankan hasil pemeriksaan sebelumnya.

Detail karyawan tidak lagi memiliki form unggah. Dokumen tambahan dan riwayat pendidikan ditambahkan melalui form karyawan. Berkas riwayat pendidikan tambahan yang baru dibuat tersedia sebagai pilihan bukti setelah disimpan. Nomor BPJS dan informasi tambahan tetap menjadi data profil, tidak otomatis dianggap sebagai bukti persyaratan.

## Aturan status

- **Belum diunggah**: belum ada dokumen terkait.
- **Menunggu verifikasi**: bukti belum disetujui atau kriteria persyaratan telah berubah.
- **Valid**: bukti disetujui (atau persyaratan tidak membutuhkan pemeriksaan), masa berlaku lengkap, dan belum kedaluwarsa.
- **Ditolak**: hasil pemeriksaan HR menolak bukti untuk versi persyaratan yang berlaku.
- **Kedaluwarsa**: tanggal berlaku telah lewat. Berkas masih berlaku sepanjang tanggal kedaluwarsa, mengikuti zona waktu aplikasi.
- **Masa berlaku belum diisi**: persyaratan kini mewajibkan tanggal kedaluwarsa tetapi dokumen lama belum memilikinya.

Status **Lengkap** berarti semua persyaratan wajib valid. Persyaratan opsional tetap ditampilkan tetapi tidak menghalangi kelengkapan. Jabatan tanpa persyaratan dan karyawan tanpa jabatan yang terhubung ditampilkan terpisah, tidak otomatis dianggap lengkap.

Mengganti bukti atau masa berlaku melalui form edit melepas bukti sebelumnya dari checklist, menyimpan berkas lama sebagai arsip, dan meminta pemeriksaan atas bukti penggantinya. Mengubah kriteria persyaratan meminta verifikasi ulang untuk persyaratan yang membutuhkan pemeriksaan. Mengganti jabatan menampilkan checklist jabatan baru; dokumen jabatan sebelumnya tetap menjadi arsip. Menghapus persyaratan tidak menghapus berkas karyawan.

Bukti yang memakai ijazah Pendidikan Terakhir mengikuti penggantian berkas sumber dan hasil verifikasinya direset; menghapus ijazah sumber menghapus referensi bukti terkait. Menghapus riwayat pendidikan juga melepas bukti yang bersumber dari riwayat tersebut. File bersama tidak dihapus selama masih digunakan dokumen atau data pendidikan lainnya. Modal pemeriksaan mendeteksi perubahan berkas sumber, masa berlaku, atau versi persyaratan sejak halaman dibuka dan meminta pemeriksa memuat ulang.

Hak akses: `employees.view` untuk membaca, `employees.manage` untuk mengelola dan mengunggah, dan **`employees.verify` khusus untuk menerima/menolak bukti**. Hak edit tidak otomatis memberikan hak verifikasi. Migrasi menambahkan izin verifikasi ke role admin dan super-admin tanpa mereset izin role lain. Role pemeriksa lain dapat diberi `admin.access`, `employees.view`, dan `employees.verify` melalui Role Management. Nama pemeriksa, waktu, dan catatan pemeriksaan terakhir ditampilkan pada dokumen.

## Menjalankan lokal

```sh
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
php artisan test --filter='HrRequirementsTest|EmployeeManagementTest'
```

Perubahan ini tidak mengubah storage berkas yang sudah digunakan modul karyawan. Tidak ada seed persyaratan otomatis: HR menentukan kriteria jabatan sesuai kebutuhan organisasi.
