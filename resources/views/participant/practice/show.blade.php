@extends('layouts.participant')
@section('page-title', 'Hasil Aktivitas')
@section('training-section', 'logbook')
@section('title', $task->title)
@push('styles')<link rel="stylesheet" href="{{ asset('templates/welding-school/practice.css') }}?v={{ filemtime(public_path('templates/welding-school/practice.css')) }}">@endpush
@section('content')
<div class="practice-shell">
    <a href="{{ route('participant.practice.index', $batch) }}">← Semua tugas & logbook</a>
    <article class="practice-card">
        <span class="eyebrow">{{ $task->session->starts_at->isToday() ? 'AKTIVITAS HARI INI' : 'AKTIVITAS SESI' }}</span><h1>{{ $task->title }}</h1>
        <p>{{ $task->session->title }} · {{ $task->session->starts_at->locale('id')->translatedFormat('j F Y H:i') }}<br>{{ $task->session->trainer->name }} · {{ $task->session->location->name }}</p>
        <p class="practice-prewrap">{{ $task->instructions }}</p>
        <small>Data peserta dan sesi otomatis. Tidak perlu menulis ulang tanggal, instruktur, atau lokasi.</small>
    </article>
    @if($record?->review_notes)<div class="practice-feedback"><strong>Catatan instruktur</strong><p class="practice-prewrap">{{ $record->review_notes }}</p></div>@endif
    @if($editable)
    <form method="POST" enctype="multipart/form-data" action="{{ route('participant.practice.store', [$batch, $task]) }}" class="practice-card practice-form" data-practice-form data-photo-required="{{ $task->photo_required ? 'true' : 'false' }}">
        @csrf
        <input type="hidden" name="version" value="{{ $record?->version ?? 0 }}">
        <input type="hidden" name="request_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <fieldset aria-describedby="practice-outcome-error"><legend>Hasil aktivitas</legend><div class="practice-choices">
            @foreach(\App\Models\PracticeSubmission::OUTCOMES as $value => $label)<label><input type="radio" name="outcome" value="{{ $value }}" @checked(old('outcome', $record?->outcome) === $value)><span>{{ $label }}</span></label>@endforeach
        </div><p id="practice-outcome-error" data-error="outcome" class="practice-field-error"></p></fieldset>
        <fieldset data-practice-obstacles><legend>Kendala <small>(opsional)</small></legend><div class="practice-choices">
            <label><input type="radio" name="obstacle" value="" @checked(!old('obstacle', $record?->obstacle))><span>Tidak ada</span></label>
            @foreach(\App\Models\PracticeSubmission::OBSTACLES as $value => $label)<label><input type="radio" name="obstacle" value="{{ $value }}" @checked(old('obstacle', $record?->obstacle) === $value)><span>{{ $label }}</span></label>@endforeach
        </div></fieldset>
        <div data-practice-evidence>
            <label class="practice-field"><span>Foto hasil <small data-photo-label>{{ $task->photo_required ? '(diminta untuk pekerjaan yang dilakukan)' : '(opsional)' }}</small></span><span class="practice-file-button">Ambil / pilih foto<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" capture="environment" aria-label="Ambil atau pilih foto hasil pekerjaan" aria-describedby="practice-photo-help practice-photo-error"></span><small id="practice-photo-help">Foto pekerjaan, bukan selfie absensi. JPG, PNG, atau WebP; maksimal 5 MB setelah diperkecil.</small></label>
            <img class="practice-photo" data-practice-preview @if($record?->photo_path) src="{{ route('participant.practice.photo', [$batch, $task]) }}" @else hidden @endif alt="Foto hasil pekerjaan">
            <p id="practice-photo-error" data-error="photo" class="practice-field-error"></p>
        </div>
        <label class="practice-field"><span data-notes-label>Catatan <small>(opsional)</small></span><textarea name="notes" rows="2" maxlength="1000" aria-describedby="practice-notes-error" placeholder="Tulis hanya jika ada yang ingin disampaikan">{{ old('notes', $record?->notes) }}</textarea></label>
        <p id="practice-notes-error" data-error="notes" class="practice-field-error"></p>
        <div class="practice-actions"><button class="button button--primary" type="submit" name="action" value="submit">Kirim hasil aktivitas</button><button class="button button--outline" type="submit" name="action" value="draft" data-practice-save formnovalidate>Simpan draf</button></div>
        <p data-practice-message role="status" aria-live="polite">Perubahan disimpan otomatis saat koneksi tersedia.</p>
        <p class="practice-muted">Satu pengiriman menjadi tugas sekaligus logbook. Jam praktik ditetapkan instruktur setelah pemeriksaan, bukan otomatis dari absensi.</p>
        <noscript>Gunakan Simpan draf sebelum meninggalkan halaman. Penyimpanan otomatis membutuhkan JavaScript.</noscript>
    </form>
    @else
    <article class="practice-card"><h2>{{ $record ? \App\Models\PracticeSubmission::STATUSES[$record->status] : 'Pengisian belum dibuka' }}</h2>
        @if($record)
            <p>Hasil: {{ \App\Models\PracticeSubmission::OUTCOMES[$record->outcome] ?? 'Belum dipilih' }}</p>
            @if($record->obstacle)<p>Kendala: {{ \App\Models\PracticeSubmission::OBSTACLES[$record->obstacle] }}</p>@endif
            @if($record->notes)<p class="practice-prewrap">{{ $record->notes }}</p>@endif
            @if($record->photo_path)<a href="{{ route('participant.practice.photo', [$batch, $task]) }}" target="_blank" rel="noopener"><img class="practice-photo" src="{{ route('participant.practice.photo', [$batch, $task]) }}" alt="Foto hasil pekerjaan"></a>@endif
            <p>{{ $record->verified_minutes !== null ? $record->verified_minutes.' menit praktik terverifikasi' : 'Belum ada durasi praktik terverifikasi.' }}</p>
            @if($record->status === 'accepted' && $record->outcome !== 'completed')<p>Catatan aktivitas diterima; ini tidak berarti pekerjaan sudah selesai atau peserta sudah lulus.</p>@endif
        @else<p>Anda dapat mengisi setelah sesi dimulai. Instruksi dapat dibaca terlebih dahulu.</p>@endif
    </article>
    @endif
    @if($record?->events->isNotEmpty())<details class="practice-card"><summary>Riwayat pengiriman & pemeriksaan</summary><ol>@foreach($record->events as $event)<li>{{ $event->created_at->locale('id')->translatedFormat('j F Y H:i') }} · {{ $event->action === 'hours_invalidated' ? 'Durasi perlu diverifikasi ulang setelah koreksi absensi' : (\App\Models\PracticeSubmission::STATUSES[$event->action] ?? $event->action) }}@if($event->snapshot['review_notes'] ?? null)<p>{{ $event->snapshot['review_notes'] }}</p>@endif</li>@endforeach</ol></details>@endif
</div>
@endsection
@push('scripts')<script src="{{ asset('templates/welding-school/practice.js') }}?v={{ filemtime(public_path('templates/welding-school/practice.js')) }}" defer></script>@endpush
