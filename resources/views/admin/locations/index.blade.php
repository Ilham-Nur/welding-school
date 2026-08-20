@extends('layouts.admin')

@section('title', 'Master Lokasi')
@section('eyebrow', 'Master data')
@section('heading', 'Lokasi')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Master lokasi</h1>
            <p>Kelola lokasi dan bagian di dalamnya untuk kebutuhan Asset serta Storage.</p>
        </div>
        @can('locations.manage')
            <a class="button button--primary admin-button" href="{{ route('admin.locations.create') }}">+ Tambah lokasi</a>
        @endcan
    </section>

    <form class="admin-filter" method="GET">
        <label class="admin-field">
            <span>Cari lokasi</span>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Nama lokasi">
        </label>
        <div class="admin-actions">
            <button class="button button--primary admin-button">Terapkan</button>
            <a class="button button--outline admin-button" href="{{ route('admin.locations.index') }}">Reset</a>
        </div>
    </form>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div>
                <h2>Daftar lokasi</h2>
                <p>{{ $locations->total() }} lokasi terdaftar.</p>
            </div>
        </header>

        @if($locations->isEmpty())
            <div class="admin-empty">
                <span><x-ui.icon name="location" /></span>
                <h2>Belum ada lokasi</h2>
                <p>Tambahkan lokasi pertama sebelum meregistrasikan aset atau stok.</p>
            </div>
        @else
            <x-ui.table class="admin-table-wrap">
                <thead><tr><th>Lokasi</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($locations as $location)
                        <tr>
                            <td><strong>{{ $location->name }}</strong></td>
                            <td><x-admin.status-badge :status="$location->is_active ? 'active' : 'inactive'" /></td>
                            <td>
                                <div class="admin-action-group">
                                    <button class="admin-action-button admin-action-button--view" type="button" data-modal-open="location-detail-{{ $location->id }}"><x-ui.icon name="eye" size="14" /> Detail</button>
                                    @can('locations.manage')
                                        <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.locations.edit', $location) }}"><x-ui.icon name="edit" size="14" /> Edit</a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
            <x-ui.pagination :paginator="$locations" />
        @endif
    </section>

    @foreach($locations as $location)
        @php
            $modalHasErrors = (int) old('_location_id') === $location->id
                && ($errors->has('children') || $errors->has('children.*'));
            $partRows = $modalHasErrors ? old('children', ['']) : [''];
            $formId = 'location-parts-form-'.$location->id;
        @endphp

        <x-ui.modal
            :id="'location-detail-'.$location->id"
            :title="$location->name"
            :description="'Kelola bagian yang berada di dalam '.$location->name.'.'"
            size="medium"
        >
            <div class="storage-location-modal-meta">
                <x-admin.status-badge :status="$location->is_active ? 'active' : 'inactive'" />
                @if($location->is_storage)<span>Digunakan sebagai Storage</span>@endif
            </div>

            <section class="storage-location-parts-current">
                <h3>Daftar bagian</h3>
                @if($location->children->isEmpty())
                    <p>Belum ada bagian. Lokasi ini boleh tetap berdiri sendiri.</p>
                @else
                    <div class="storage-location-part-items">
                        @foreach($location->children as $child)
                            <article>
                                <span>{{ $child->name }}</span>
                                <x-admin.status-badge :status="$child->is_active ? 'active' : 'inactive'" />
                                @can('locations.manage')
                                    <a href="{{ route('admin.locations.edit', $child) }}">Edit</a>
                                @endcan
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            @can('locations.manage')
                <section class="storage-location-parts-add">
                    <h3>Tambah bagian</h3>
                    <p>Masukkan satu atau beberapa bagian, lalu simpan semuanya sekaligus.</p>

                    <form id="{{ $formId }}" method="POST" action="{{ route('admin.locations.children.store', $location) }}" data-location-parts-form data-location-modal-id="location-detail-{{ $location->id }}">
                        @csrf
                        <input type="hidden" name="_location_id" value="{{ $location->id }}">

                        @if($modalHasErrors)
                            <p class="ui-field__error" data-location-parts-errors>{{ $errors->first('children') ?: $errors->first('children.*') }}</p>
                        @endif

                        <div class="storage-part-list" data-location-part-list>
                            @foreach($partRows as $index => $part)
                                <div class="storage-part-row" data-location-part-row>
                                    <label class="ui-field admin-field">
                                        <span class="ui-field__label">Nama bagian {{ $loop->iteration }}</span>
                                        <input type="text" name="children[{{ $index }}]" value="{{ $part }}" placeholder="Contoh: Booth {{ $loop->iteration }}" maxlength="120" required>
                                    </label>
                                    <button type="button" data-location-part-remove aria-label="Hapus bagian"><x-ui.icon name="trash" size="16" /></button>
                                </div>
                            @endforeach
                        </div>

                        <button class="button button--outline admin-button storage-add-part-row" type="button" data-location-part-add>+ Tambah baris</button>
                        <template data-location-part-template>
                            <div class="storage-part-row" data-location-part-row>
                                <label class="ui-field admin-field">
                                    <span class="ui-field__label">Nama bagian</span>
                                    <input type="text" name="children[__INDEX__]" placeholder="Contoh: Booth" maxlength="120" required>
                                </label>
                                <button type="button" data-location-part-remove aria-label="Hapus bagian"><x-ui.icon name="trash" size="16" /></button>
                            </div>
                        </template>
                    </form>
                </section>
            @endcan

            <x-slot:footer>
                <button class="button button--outline admin-button" type="button" data-modal-close>Tutup</button>
                @can('locations.manage')
                    <button class="button button--primary admin-button" type="submit" form="{{ $formId }}">Simpan semua bagian</button>
                @endcan
            </x-slot:footer>
        </x-ui.modal>
    @endforeach
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('templates/welding-school/storage.css') }}?v={{ filemtime(public_path('templates/welding-school/storage.css')) }}">
@endpush

@push('scripts')
    @vite('resources/js/locations.js')
@endpush
