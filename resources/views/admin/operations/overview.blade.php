<div class="ops-columns">
    <section class="admin-panel">
        <header class="admin-panel__header"><div><h2>Kesiapan pelatihan</h2><p>Seluruh persyaratan harus terpenuhi sebelum batch dinyatakan siap.</p></div></header>
        <div class="admin-panel__body">
            <ul class="ops-readiness">@foreach ($readiness as $check)<li><span @class(['ops-check', 'is-done' => $check['done']]) aria-label="{{ $check['done'] ? 'Terpenuhi' : 'Belum terpenuhi' }}">{{ $check['done'] ? '✓' : '○' }}</span><span>{{ $check['title'] }}@if(!$check['done'])<small style="display:block;margin-top:5px">{{ $check['next_step'] }} <a href="{{ route('admin.operations.show', [$batch, 'tab' => $check['tab']]) }}">Lengkapi sekarang →</a></small>@endif</span></li>@endforeach</ul>
            <a class="button button--outline admin-button" href="{{ route('admin.operations.show', ['batch' => $batch, 'tab' => 'checklist']) }}">Periksa checklist</a>
        </div>
    </section>
    <section class="admin-panel">
        <header class="admin-panel__header"><div><h2>Tahap operasional</h2><p>Persiapan → Siap Dilaksanakan → Berjalan</p></div></header>
        <div class="admin-panel__body">
            <p class="ops-muted">Ujian, evaluasi, dan penyelesaian akan tersedia pada pengembangan berikutnya.</p>
            @can('operations.status')
                @php
                    $transitions = ['preparation' => ['ready', 'postponed', 'cancelled'], 'ready' => ['running', 'postponed', 'cancelled'], 'running' => ['postponed', 'cancelled'], 'postponed' => ['cancelled']][$batch->operational_status] ?? [];
                    if (auth()->user()->can('operations.reopen') && ! in_array($batch->operational_status, ['preparation', 'completed'], true)) $transitions[] = 'preparation';
                @endphp
                @if (count($transitions))
                    @php
                        $selectedTarget = in_array(old('operational_status'), $transitions, true) ? old('operational_status') : $transitions[0];
                        $confirmPrefix = 'confirm-batch-status-'.$batch->id;
                    @endphp
                    <div class="ops-stack" data-ops-status-control data-confirm-prefix="{{ $confirmPrefix }}">
                        <label class="admin-field"><span>Status tujuan</span><select data-ops-status-target>@foreach ($transitions as $target)<option value="{{ $target }}" @selected($target === $selectedTarget)>{{ \App\Models\TrainingBatch::OPERATIONAL_STATUSES[$target] }}</option>@endforeach</select></label>
                        <p class="ops-muted">Penundaan, pembatalan, atau mulai pelatihan menutup pendaftaran. Pembatalan juga membatalkan sesi; pembayaran peserta tetap tercatat dan perlu ditindaklanjuti.</p>
                        <button type="button" class="button button--primary admin-button" data-modal-open="{{ $confirmPrefix }}-{{ $selectedTarget }}" aria-haspopup="dialog" aria-controls="{{ $confirmPrefix }}-{{ $selectedTarget }}">Ubah status</button>
                    </div>
                    @foreach ($transitions as $target)
                        <x-ui.action-modal :id="$confirmPrefix.'-'.$target" :title="'Ubah status menjadi '.\App\Models\TrainingBatch::OPERATIONAL_STATUSES[$target].'?'" :action="route('admin.operations.status', $batch)" confirm-label="Ya, ubah status" :reason-label="in_array($target, ['cancelled', 'postponed'], true) ? 'Alasan '.($target === 'cancelled' ? 'pembatalan' : 'penundaan') : null" :danger="in_array($target, ['cancelled', 'postponed'], true)" :placeholder="$target === 'cancelled' ? 'Contoh: Batch dibatalkan karena kendala fasilitas. Tim akan menghubungi peserta.' : 'Contoh: Workshop sedang diperbaiki. Jadwal baru akan diinformasikan kepada peserta.'">
                            <input type="hidden" name="operational_status" value="{{ $target }}">
                            <p>Konfirmasikan perubahan status batch <strong>{{ $batch->name }}</strong>.</p>
                            @if ($target === 'ready')<p>Sistem akan memeriksa seluruh persyaratan kesiapan sebelum menyimpan.</p>@endif
                            @if ($target === 'running')<p>Pelatihan dimulai dan pendaftaran ditutup.</p>@endif
                            @if ($target === 'postponed')<p>Pelatihan ditunda dan pendaftaran ditutup. Peserta tetap berada di batch ini.</p>@endif
                            @if ($target === 'cancelled')<p>Pendaftaran ditutup dan semua sesi dibatalkan. Pembayaran tetap tercatat; tidak ada refund otomatis.</p>@endif
                            @if ($target === 'preparation')<p>Kesiapan harus diperiksa ulang. Pendaftaran dan sesi tidak otomatis dibuka kembali.</p>@endif
                        </x-ui.action-modal>
                    @endforeach
                    @push('scripts')
                        <script src="{{ asset('templates/welding-school/operations.js') }}?v={{ filemtime(public_path('templates/welding-school/operations.js')) }}" defer></script>
                    @endpush
                @endif
            @endcan
        </div>
    </section>
</div>
