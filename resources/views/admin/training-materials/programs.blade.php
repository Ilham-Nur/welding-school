@extends('layouts.admin')
@section('title','Susunan materi program')
@section('heading','Quality Documents · Training')
@include('admin.training-materials.style')
@section('content')
<section class="admin-page-heading"><div><h1>Susunan materi program</h1><p>Atur urutan, metode, dan durasi sebagai acuan materi untuk batch.</p></div><a class="button button--outline admin-button" href="{{ route('admin.training-materials.index') }}">Kembali ke library</a></section>
<section class="admin-panel"><x-ui.table class="admin-table-wrap tm-table"><thead><tr><th>Program</th><th>Status</th><th>Jumlah materi</th><th>Aksi</th></tr></thead><tbody>@forelse($programs as $program)<tr><td><strong>{{ $program->title }}</strong><small>{{ $program->code }}</small></td><td><x-admin.status-badge :status="$program->status" /></td><td>{{ $program->material_plan_count }}</td><td><a class="button button--outline admin-button" href="{{ route('admin.training-materials.program',$program) }}">Buka susunan</a></td></tr>@empty<tr><td colspan="4">Belum ada program pelatihan.</td></tr>@endforelse</tbody></x-ui.table></section>
@endsection
