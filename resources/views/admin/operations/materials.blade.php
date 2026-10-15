@php
    $hasBatchMaterials = $materials->isNotEmpty();
@endphp
<section class="admin-panel">
<header class="admin-panel__header"><div><h2>Materi batch & sesi</h2><p>Menyusun program belum otomatis membagikan materi. Pilih sesi Terbit dan pastikan ada file berakses Peserta.</p></div>
@if($editable && auth()->user()->can('operations.materials') && !$hasBatchMaterials)<form method="POST" action="{{ route('admin.operations.materials.import', $batch) }}">@csrf<button class="button button--outline admin-button">Ambil susunan program</button></form>@endif</header>
<div class="admin-panel__body">
@if($hasBatchMaterials)<p class="tm-notice">Materi program sudah masuk ke batch. Gunakan Atur materi untuk memilih sesi dan publikasi. Materi tambahan dapat dipilih dari library.</p>@endif
<div class="tm-notice"><strong>{{ $materialReadiness['linked'] }} materi terhubung ke sesi terbit</strong><p>{{ $materialReadiness['missing'] }} dari {{ $materialReadiness['sessions'] }} sesi terbit belum memiliki materi. Publikasi kepada peserta dapat dilakukan bertahap.</p><a href="{{ route('admin.operations.show', [$batch, 'tab' => 'schedule']) }}">Periksa jadwal dan status sesi →</a></div></div>
<x-ui.table class="admin-table-wrap tm-table"><thead><tr><th>Urutan / Materi</th><th>Sesi</th><th>Metode / Durasi</th><th>Publikasi peserta & langkah berikutnya</th><th>Aksi</th></tr></thead><tbody>
@forelse($materials as $item)
@php
    $publicationChecks = app(\App\Services\Training\MaterialAccess::class)->publicationChecks($item, $item->session);
    $missingChecks = collect($publicationChecks)->where('done', false);
    $shared = $item->participant_visible && $missingChecks->isEmpty();
@endphp
<tr><td><strong>{{ $item->position }}. {{ $item->revision->title }}</strong><small>{{ $item->revision->material->code }} · Rev. {{ $item->revision->number }}</small>@if($item->revision->status === 'archived')<small>Revisi arsip yang tersimpan pada batch</small>@endif</td>
<td>{{ $item->session?->title ?? 'Belum dipilih' }}<small>{{ $item->session ? \App\Models\BatchSession::STATUSES[$item->session->status] : 'Pilih sesi melalui Atur materi' }}</small></td>
<td>{{ \App\Models\TrainingMaterialRevision::METHODS[$item->revision->method] }}<small>{{ $item->revision->duration_minutes }} menit</small></td>
<td><span class="tm-badge {{ $shared ? 'tm-badge--published' : '' }}">{{ $shared ? 'Dibagikan' : ($missingChecks->isEmpty() ? 'Siap dibagikan' : 'Syarat belum lengkap') }}</span>
@if($missingChecks->isNotEmpty())<ul class="ui-prerequisite-issues">@foreach($missingChecks as $check)<li>{{ $check['message'] }}</li>@endforeach</ul>@else<small>{{ $shared ? 'File peserta sudah dapat diakses peserta aktif lunas.' : 'Pilih Bagikan file peserta melalui Atur materi.' }}</small>@endif</td>
<td class="tm-batch-actions-cell">
    <div class="tm-batch-actions">
        <a class="button button--outline admin-button" href="{{ route('admin.operations.materials.show', [$batch, $item]) }}">Buka materi</a>
        @if($editable && auth()->user()->can('operations.materials'))
            <a class="button button--outline admin-button" href="{{ route('admin.operations.materials.edit', [$batch, $item]) }}">Atur materi</a>
        @endif
        @if(auth()->user()->can('training-materials.view') && $missingChecks->contains('tab', 'library'))
            <a class="button button--outline admin-button" href="{{ route('admin.training-materials.show', $item->revision) }}">Periksa file & revisi</a>
        @endif
    </div>
</td></tr>
@empty<tr><td colspan="5">Belum ada materi. Ambil susunan program atau pilih materi dari library di bawah.</td></tr>@endforelse
</tbody></x-ui.table>
@if($editable && auth()->user()->can('operations.materials'))<form class="admin-panel__body tm-filters" method="POST" action="{{ route('admin.operations.materials.store', $batch) }}">@csrf<label class="admin-field"><span>Tambah materi dari library</span><select name="training_material_revision_id" required><option value="">Pilih materi terbit yang ingin ditambahkan</option>@foreach($availableMaterials as $revision)<option value="{{ $revision->id }}">{{ $revision->material->code }} · {{ $revision->title }} · Rev. {{ $revision->number }}</option>@endforeach</select></label><button class="button button--outline admin-button">Tambahkan</button></form>@endif
</section>
