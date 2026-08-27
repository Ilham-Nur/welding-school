<div
    id="global-loading"
    class="ui-loading"
    aria-hidden="true"
    data-default-title="Sedang memproses"
    data-default-message="Mohon tunggu sebentar."
    hidden
>
    <div class="ui-loading__backdrop" aria-hidden="true"></div>
    <section class="ui-loading__panel" role="status" aria-live="polite" aria-atomic="true">
        <div class="ui-loading__brand" aria-hidden="true">
            <span class="ui-loading__spinner"></span>
            <img src="{{ asset(config('branding.logo')) }}" alt="">
        </div>
        <div class="ui-loading__copy">
            <strong data-loading-title>Sedang memproses</strong>
            <p data-loading-message>Mohon tunggu sebentar.</p>
        </div>
        <div
            class="ui-loading__progress"
            data-loading-progress
            role="progressbar"
            aria-label="Progres proses"
            aria-valuemin="0"
            aria-valuemax="100"
            hidden
        >
            <span data-loading-progress-bar></span>
        </div>
        <small data-loading-long-message hidden>Proses membutuhkan waktu lebih lama dari biasanya. Mohon tetap di halaman ini.</small>
    </section>
</div>
