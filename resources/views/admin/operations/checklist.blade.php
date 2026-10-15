<section class="admin-panel"><header class="admin-panel__header"><div><h2>Checklist kesiapan</h2><p>Kesiapan tim, peserta, lokasi, dan jadwal diperiksa otomatis. Materi yang sudah menggunakan library diperiksa otomatis dari hubungan materi dan sesi. Alat serta tempat dikonfirmasi melalui checklist.</p></div></header>
    <div class="admin-panel__body ops-stack">
        @foreach ($batch->checklistItems as $item)
            <article class="ops-checklist-card"><h3>{{ $item->title }} <span class="ops-required">{{ $item->is_required ? 'Wajib' : 'Opsional' }}</span></h3>
                @if ($item->key === 'materials' && $batch->materials_managed)
                    <p><strong>{{ $materialReadiness['done'] ? 'Siap' : 'Belum lengkap' }}</strong> · {{ $materialReadiness['count'] }} materi · {{ $materialReadiness['missing'] }} sesi terbit belum memiliki materi.</p>
                    <p>Setiap sesi terbit harus memiliki minimal satu materi dengan revisi yang pernah diterbitkan dan file pendukung. Publikasi peserta dapat dilakukan bertahap.</p>
                    <a class="button button--outline admin-button" href="{{ route('admin.operations.show',[$batch,'tab'=>'materials']) }}">Kelola materi sesi</a>
                @elseif ($editable && auth()->user()->can('operations.checklist'))
                    <form method="POST" action="{{ route('admin.operations.checklist.update', [$batch, $item]) }}" class="ops-stack">@csrf @method('PUT')
                        @php($retry = (string) old('_checklist_item_id') === (string) $item->id)
                        <input type="hidden" name="_checklist_item_id" value="{{ $item->id }}">
                        <div class="admin-form-grid">
                            <label class="admin-field"><span>Status</span><select name="status" required>@foreach (\App\Models\BatchChecklistItem::STATUSES as $key => $label)<option value="{{ $key }}" @selected(($retry ? old('status') : $item->status) === $key)>{{ $label }}</option>@endforeach</select></label>
                            <label class="admin-field"><span>PIC</span><select name="assigned_to"><option value="">Belum ditentukan</option>@foreach ($batch->assignments as $assignment)<option value="{{ $assignment->user_id }}" @selected((string) ($retry ? old('assigned_to') : $item->assigned_to) === (string) $assignment->user_id)>{{ $assignment->user->name }}</option>@endforeach</select></label>
                        </div>
                        <label class="admin-field"><span>Catatan pemeriksaan (wajib untuk selesai / bermasalah)</span><textarea name="notes" rows="2" maxlength="2000">{{ $retry ? old('notes') : $item->notes }}</textarea></label>
                        <button class="button button--outline admin-button">Simpan checklist</button>
                    </form>
                @else
                    <p>{{ \App\Models\BatchChecklistItem::STATUSES[$item->status] }} · PIC: {{ $item->assignee?->name ?? 'Belum ditentukan' }}</p><p class="ops-prewrap">{{ $item->notes }}</p>
                @endif
                @if ($item->completed_at && !($item->key === 'materials' && $batch->materials_managed))<p class="ops-muted">Dikonfirmasi {{ $item->completer?->name ?? 'Petugas' }} · {{ $item->completed_at->format('d M Y H:i') }}</p>@endif
            </article>
        @endforeach
    </div>
</section>
