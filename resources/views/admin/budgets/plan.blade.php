<div class="rab-batch" data-budget-batch data-plan>
    <header class="rab-plan-heading"><strong data-plan-label>{{ $plan['name'] ?? 'Rencana batch' }}</strong>@if(!$locked)<button type="button" class="button button--outline admin-button" data-remove-plan>Hapus rencana</button>@endif</header>
    <fieldset><input type="hidden" name="batches[{{ $planIndex }}][id]" value="{{ $plan['id'] ?? $planIndex }}">
    <div class="rab-drivers">
        <label>Nama batch<input data-plan-name name="batches[{{ $planIndex }}][name]" value="{{ $plan['name'] ?? '' }}" required maxlength="255" @readonly($locked)></label>
        <label>Rencana mulai<input type="date" name="batches[{{ $planIndex }}][start_date]" value="{{ $plan['start_date'] ?? '' }}" required @readonly($locked)></label>
        <label>Rencana selesai<input type="date" name="batches[{{ $planIndex }}][end_date]" value="{{ $plan['end_date'] ?? '' }}" @readonly($locked)></label>
    </div>
    <div class="rab-drivers">@foreach($drivers as $key => [$label, $default])<label>{{ $label }}<input type="number" name="batches[{{ $planIndex }}][{{ $key }}]" value="{{ $plan[$key] ?? $default }}" min="{{ in_array($key, ['participants','minimum_participants','days']) ? 1 : 0 }}" required></label>@endforeach</div>
    </fieldset>
</div>
