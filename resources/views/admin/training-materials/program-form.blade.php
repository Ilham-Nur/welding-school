@extends('layouts.admin')
@section('title','Edit susunan materi')
@section('heading','Quality Documents · Training')
@include('admin.training-materials.style')
@section('content')
<section class="admin-page-heading"><div><h1>Edit susunan materi</h1><p>{{ $program->title }}</p></div><a class="button button--outline admin-button" href="{{ route('admin.training-materials.program',$program) }}">Kembali</a></section>
@include('admin.training-materials.errors')
<section class="admin-panel"><div class="admin-panel__body tm-stack">
<form method="POST" action="{{ route('admin.training-materials.program.update',[$program,$item]) }}" class="tm-stack">@csrf @method('PUT')@include('admin.training-materials.plan-fields')<button class="button button--outline admin-button">Simpan</button></form><form method="POST" action="{{ route('admin.training-materials.program.remove',[$program,$item]) }}">@csrf @method('DELETE')<button class="button button--outline admin-button">Lepas dari program</button></form></div></section>
@endsection
