<section class="admin-panel"><header class="admin-panel__header"><div><h2>Riwayat aktivitas</h2><p>Penugasan, perubahan jadwal, checklist, dan status operasional.</p></div></header>
    <div class="admin-panel__body">
        @if ($logs->isEmpty())<p class="ops-muted">Belum ada aktivitas operasional.</p>@else
            <ol class="ops-timeline">@foreach ($logs as $log)<li><div class="ops-timeline__dot" aria-hidden="true"></div><div><strong>{{ $log->description }}</strong><p>{{ $log->actor?->name ?? 'Sistem / akun dihapus' }} · {{ $log->created_at->format('d M Y H:i') }}</p>@if ($log->reason)<p class="ops-prewrap">{{ $log->reason }}</p>@endif
                @php($changes = $log->readableChanges($historyUsers, $historyLocations))
                @if (count($changes))<details><summary>Detail perubahan</summary><x-ui.table><thead><tr><th>Informasi</th><th>Sebelum</th><th>Sesudah</th></tr></thead><tbody>@foreach ($changes as $change)<tr><td>{{ $change['label'] }}</td><td class="ops-prewrap">{{ $change['before'] }}</td><td class="ops-prewrap">{{ $change['after'] }}</td></tr>@endforeach</tbody></x-ui.table></details>@endif
            </div></li>@endforeach</ol><x-ui.pagination :paginator="$logs" />
        @endif
    </div>
</section>
