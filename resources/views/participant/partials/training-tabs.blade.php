@php
    $activeTrainingSection = trim($__env->yieldContent('training-section'));
    $trainingSections = ['overview' => 'Ringkasan', 'materials' => 'Materi', 'schedule' => 'Jadwal', 'attendance' => 'Kehadiran', 'logbook' => 'Tugas & Logbook', 'competency' => 'Kompetensi', 'certificate' => 'Sertifikat'];
@endphp
<nav class="training-access-tabs" aria-label="Menu {{ $batch->trainingProgram->title }}">
    @foreach($trainingSections as $section => $label)
        @php
            $sectionUrl = match ($section) {
                'materials' => route('participant.materials.index', $batch),
                'attendance' => route('participant.attendance.index', $batch),
                'logbook' => route('participant.practice.index', $batch),
                default => $dashboardUrl('training-detail', ['training_batch' => $batch->id, 'training_section' => $section]),
            };
        @endphp
        <a href="{{ $sectionUrl }}" @class(['is-active' => $activeTrainingSection === $section]) @if($activeTrainingSection === $section) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
