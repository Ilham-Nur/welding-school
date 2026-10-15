@php
    $feedbackBag = session('errors') ?? $errors;
    $feedbackErrors = $feedbackBag->getBag('default')->messages();
@endphp
<script type="application/json" data-feedback-config>{!! \Illuminate\Support\Js::encode(['errors' => $feedbackErrors, 'form' => old('_feedback_form')]) !!}</script>
@foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $flash => $tone)
    @if(session($flash))<span hidden data-flash-toast data-toast="{{ session($flash) }}" data-toast-type="{{ $tone }}"></span>@endif
@endforeach
@if($feedbackBag->any())
    <section class="ui-feedback-summary" data-feedback-summary role="alert" tabindex="-1" aria-label="Isian yang perlu diperbaiki">
        <strong>Data belum tersimpan</strong><p>Perbaiki isian berikut, lalu coba kembali. File mungkin perlu dipilih ulang setelah halaman dimuat ulang.</p>
        <ul>@foreach($feedbackErrors as $field => $messages)@foreach($messages as $message)<li data-feedback-item="{{ $field }}">{{ $message }}</li>@endforeach @endforeach</ul>
    </section>
    <span hidden data-flash-toast data-toast="Data belum tersimpan. Periksa bagian yang ditandai pada formulir." data-toast-type="danger"></span>
@endif
<noscript>
    @foreach(['success', 'warning', 'error'] as $flash)@if(session($flash))<p class="ui-feedback-noscript">{{ session($flash) }}</p>@endif @endforeach
</noscript>
