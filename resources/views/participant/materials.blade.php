@extends('layouts.participant')
@section('page-title', 'Materi Pelatihan')
@section('training-section', 'materials')
@section('title','Materi · '.$batch->name)
@section('robots','noindex, nofollow')
@push('head')<link rel="stylesheet" href="{{ asset('templates/welding-school/training-materials.css') }}?v={{ filemtime(public_path('templates/welding-school/training-materials.css')) }}">@endpush
@section('content')
<div class="training-content"><h1>Materi pelatihan</h1><p>{{ $batch->trainingProgram->title }} · {{ $batch->name }}</p>
@forelse($items as $item)<article class="tm-material"><span class="tm-badge">{{ $item->session->title }} · {{ $item->session->starts_at->format('d M Y H:i') }}</span><h2>{{ $item->position }}. {{ $item->revision->title }}</h2><p>{{ \App\Models\TrainingMaterialRevision::METHODS[$item->revision->method] }} · {{ $item->revision->duration_minutes }} menit · Revisi {{ $item->revision->number }}</p><h3>Tujuan pembelajaran</h3><p class="tm-prewrap">{{ $item->revision->objectives }}</p>@if($item->revision->topics)<p class="tm-prewrap">{{ $item->revision->topics }}</p>@endif<ul class="tm-files">@foreach($item->revision->files as $file)<li><strong>{{ $file->label }}</strong><span>@if($file->canPreview())<a href="{{ route('participant.materials.file',[$batch,$item,$file,'preview'=>1]) }}" target="_blank" rel="noopener">Preview</a>@endif<a href="{{ route('participant.materials.file',[$batch,$item,$file]) }}">Unduh</a></span></li>@endforeach</ul></article>@empty<div class="tm-notice"><h2>Materi belum dibagikan</h2><p>Materi akan muncul setelah trainer atau penanggung jawab mempublikasikannya untuk batch ini.</p></div>@endforelse</div>
@endsection
