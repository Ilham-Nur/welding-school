@if ($errors->any())
    <section class="admin-panel" role="alert" style="padding: 20px; margin-bottom: 20px">
        <strong>Data belum tersimpan. Periksa isian berikut:</strong>
        @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        <p>Pilih ulang berkas yang akan diunggah; browser tidak menyimpan pilihan file setelah validasi gagal.</p>
    </section>
@endif
