<section class="admin-panel">
    <header class="admin-panel__header"><div><h2>Tim pelaksana</h2><p>Satu Penanggung Jawab, satu Trainer utama, dan beberapa Trainer pendamping. Setiap orang memiliki satu peran dalam batch.</p></div></header>
    @if ($batch->assignments->isEmpty())<div class="admin-empty"><h2>Tim belum ditentukan</h2><p>Tugaskan Penanggung Jawab dan Trainer utama untuk mulai menyusun jadwal.</p></div>@else
        <x-ui.table class="admin-table-wrap"><thead><tr><th>Petugas</th><th>Peran</th><th>Ditugaskan</th>@if ($editable && auth()->user()->can('operations.team'))<th>Tindakan</th>@endif</tr></thead><tbody>
            @foreach ($batch->assignments as $assignment)
                <tr><td><strong>{{ $assignment->user->name }}</strong><small>{{ $assignment->user->email }}{{ $assignment->user->status !== 'active' ? ' · Akun tidak aktif' : '' }}</small></td><td>{{ \App\Models\BatchAssignment::ROLES[$assignment->role] }}</td><td>{{ $assignment->created_at->format('d M Y') }}</td>
                    @if ($editable && auth()->user()->can('operations.team'))<td><button type="button" class="button button--outline admin-button ops-action--danger" data-modal-open="end-assignment-{{ $assignment->id }}" aria-haspopup="dialog" aria-controls="end-assignment-{{ $assignment->id }}">Akhiri penugasan</button></td>@endif
                </tr>
            @endforeach
        </tbody></x-ui.table>
    @endif
</section>
@if ($editable && auth()->user()->can('operations.team'))
    @foreach ($batch->assignments as $assignment)
        <x-ui.action-modal :id="'end-assignment-'.$assignment->id" title="Akhiri penugasan?" :action="route('admin.operations.team.destroy', [$batch, $assignment])" method="DELETE" confirm-label="Ya, akhiri penugasan" reason-label="Alasan mengakhiri penugasan" placeholder="Contoh: Pergantian Trainer. Tugas dialihkan kepada petugas pengganti.">
            <p>Penugasan <strong>{{ $assignment->user->name }}</strong> sebagai {{ \App\Models\BatchAssignment::ROLES[$assignment->role] }} pada batch <strong>{{ $batch->name }}</strong> akan diakhiri.</p>
            <p>Pindahkan atau batalkan sesi yang masih ditangani petugas ini terlebih dahulu.</p>
        </x-ui.action-modal>
    @endforeach
    <section class="admin-panel"><header class="admin-panel__header"><div><h2>Tambah atau ubah penugasan</h2><p>Akun harus aktif, memiliki akses internal, dan izin melihat operasional batch.</p></div></header><div class="admin-panel__body">
        <form method="POST" action="{{ route('admin.operations.team.store', $batch) }}" class="ops-stack">@csrf
            <div class="admin-form-grid">
                <label class="admin-field"><span>Petugas</span><select name="user_id" required><option value="">Pilih akun internal</option>@foreach ($staff as $user)<option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>{{ $user->name }} · {{ $user->email }}</option>@endforeach</select></label>
                <label class="admin-field"><span>Peran dalam batch</span><select name="role" required>@foreach (\App\Models\BatchAssignment::ROLES as $key => $label)<option value="{{ $key }}" @selected(old('role') === $key)>{{ $label }}</option>@endforeach</select></label>
            </div>
            <label class="admin-field"><span>Alasan / catatan penugasan (opsional)</span><textarea name="reason" rows="2" maxlength="1000">{{ old('reason') }}</textarea></label>
            <button class="button button--primary admin-button">Simpan penugasan</button>
        </form>
    </div></section>
@endif
