@if ($doc->requirement)
    <small style="display: block">Persyaratan: {{ $doc->requirement->name }}</small>
    <small style="display: block">{{ \App\Models\EmployeePositionRequirement::STATUSES[$doc->requirement->documentStatus($doc)] }}{{ $doc->requirement->employee_position_id !== $employee->position_id ? ' · Jabatan sebelumnya' : '' }}</small>
@endif
@if ($doc->expires_at)<small style="display: block">Berlaku sampai {{ $doc->expires_at->format('d M Y') }}</small>@endif
@if ($doc->verified_at)
    <small style="display: block">Pemeriksa: {{ $doc->verifier?->name ?? 'Pengguna dihapus' }} · {{ $doc->verified_at->format('d M Y H:i') }}</small>
    <small style="display: block">{{ $doc->verification_notes }}</small>
@endif
