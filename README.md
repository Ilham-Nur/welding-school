# PT. Alpha Teknik Pratama · Welding School

Fondasi aplikasi pelatihan welding PT. Alpha Teknik Pratama menggunakan Laravel 13, Blade, dan MySQL.
Tampilan statis sebelumnya disimpan sebagai template referensi di
`public/templates/welding-school`, lalu dipakai kembali oleh layout Blade.

## Persiapan lokal

1. Salin `.env.example` menjadi `.env`.
2. Buat database MySQL bernama `welding_school`.
3. Sesuaikan `DB_USERNAME` dan `DB_PASSWORD` pada `.env`.
4. Jalankan `composer install`.
5. Jalankan `php artisan key:generate`.
6. Jalankan `php artisan migrate --seed`.
7. Jalankan `php artisan serve`.

Halaman utama tersedia di `/`, sedangkan katalog komponen Blade tersedia di
`/template/components`.

Sebelum menjalankan seeder untuk pertama kali, tentukan kredensial super-admin
yang unik di `.env`:

```dotenv
ADMIN_NAME="Administrator PT. Alpha Teknik Pratama"
ADMIN_EMAIL=alamat-admin@domain-anda.com
ADMIN_PASSWORD=password-kuat-dan-unik
```

Seeder hanya menggunakan password tersebut ketika akun super-admin belum ada.
Menjalankan seeder kembali tidak akan mereset password admin yang sudah aktif.

## Login dan pendaftaran

Autentikasi dipisahkan berdasarkan jenis pengguna:

- Portal Peserta tersedia di `/login` dan diarahkan ke `/#account`;
- Portal Internal untuk instruktur, admin, storeman, keuangan, dan role staf lain tersedia di `/admin/login`;
- akun internal harus memiliki permission `admin.access`, sedangkan menu dan tindakan setelah login mengikuti permission masing-masing role.

Portal Peserta sudah terhubung ke autentikasi Laravel dan mendukung:

- daftar menggunakan nama, email, dan password;
- login menggunakan email atau username dan password;
- opsi ingat saya;
- reset password melalui tautan sekali pakai yang dikirim ke email;
- logout yang menghapus sesi;
- login atau daftar menggunakan akun Google khusus peserta.

Password minimal 8 karakter dan harus berisi huruf serta angka. Pesan login
yang gagal dibuat umum agar tidak membocorkan apakah sebuah email terdaftar.

### Testing verifikasi email di lokal

Pendaftaran menggunakan email memakai kode OTP 6 digit:

1. Pengguna mendaftar.
2. Kode dikirim melalui mailer Laravel dan berlaku selama 10 menit.
3. Pengguna memasukkan kode pada halaman `/#verification`.
4. Setelah benar, `email_verified_at` diisi dan pengguna masuk ke dashboard.

Konfigurasi lokal menggunakan `MAIL_MAILER=log`, sehingga salinan email dapat
dilihat pada `storage/logs/laravel.log`. Ketika `APP_ENV=local`, kode testing
juga ditampilkan langsung pada halaman verifikasi agar alur dapat diuji tanpa
domain atau server email. Kode testing tersebut tidak ditampilkan ketika
aplikasi menggunakan `APP_ENV=production`.

Kode hanya dapat dicoba lima kali. Pengiriman ulang memiliki jeda 60 detik dan
selalu menggantikan kode sebelumnya.

### Reset password

Tautan **Lupa password?** pada halaman login mengirim tautan reset jika email
terdaftar. Respons aplikasi selalu sama untuk email terdaftar maupun tidak agar
keberadaan akun tidak dapat ditebak. Tautan berlaku 60 menit, hanya dapat
digunakan satu kali, dan seluruh sesi lama dicabut setelah password diperbarui.
Password lama maupun password baru tidak pernah dikirim melalui email.

## Mengaktifkan login Google

1. Buka Google Auth Platform dan buat atau pilih sebuah project.
2. Lengkapi halaman Branding/consent screen.
3. Buat OAuth Client dengan tipe **Web application**.
4. Tambahkan Authorized redirect URI berikut untuk pengembangan lokal:
   `http://localhost:8000/auth/google/callback`.
5. Salin Client ID dan Client Secret ke `.env`:

```dotenv
GOOGLE_CLIENT_ID=client-id-dari-google
GOOGLE_CLIENT_SECRET=client-secret-dari-google
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

6. Pastikan `APP_URL=http://localhost:8000`, lalu jalankan
   `php artisan config:clear`.

Untuk domain produksi, ganti `APP_URL` ke alamat HTTPS produksi dan daftarkan
URI callback produksi yang sama persis di Google Auth Platform.

Login Google di aplikasi ini hanya dipakai untuk identitas dasar pengguna
(nama, email, dan foto profil), bukan untuk membaca inbox Gmail. Aplikasi juga
tidak menyimpan access token atau password Google. Akun Google baru dibuat
sebagai peserta tanpa password lokal; jika alamat email sudah ada, akun Google
akan ditautkan ke akun tersebut.

## Invoice setelah persetujuan

Setelah admin menyetujui pendaftaran, peserta dapat membuka ringkasan biaya,
menyetujui kebijakan pembayaran, lalu membuat invoice. Nominal invoice dihitung
oleh backend dan satu pendaftaran hanya dapat memiliki satu invoice.

Konfigurasi biaya administrasi dan batas pembayaran tersedia di `.env`:

```dotenv
BILLING_ADMINISTRATION_FEE=150000
BILLING_INVOICE_DUE_HOURS=24
```

Pembayaran menggunakan Midtrans Snap Redirect. Untuk pengujian lokal, gunakan
Access Keys dari environment Sandbox:

```dotenv
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SERVER_KEY=SB-Mid-server-...
MIDTRANS_CLIENT_KEY=SB-Mid-client-...
MIDTRANS_NOTIFICATION_URL=
```

`MIDTRANS_SERVER_KEY` hanya boleh disimpan di backend dan tidak boleh dikirim
ke browser atau disimpan di repository. Midtrans tidak dapat mengirim webhook
langsung ke `localhost`. Gunakan URL HTTPS dari tunnel lokal sebagai
`MIDTRANS_NOTIFICATION_URL`, dengan path:

```text
/payments/midtrans/webhook
```

Redirect browser dari Midtrans tidak pernah menandai pembayaran sebagai lunas.
Invoice hanya menjadi lunas setelah webhook memiliki signature yang valid,
nominalnya sesuai, dan referensi transaksinya ditemukan. Webhook pembayaran
yang valid juga membuat enrollment peserta secara otomatis.

## Komponen Blade

Komponen reusable berada di `resources/views/components/ui`:

- alert
- modal
- confirmation
- text input
- file input
- table
- pagination
- toast stack

Pengujian dapat dijalankan dengan `php artisan test`. Lingkungan pengujian
menggunakan SQLite in-memory agar tidak mengubah data MySQL lokal.
