<div class="reo-section-head">
    <div>
        <h2>Prefix &amp; Starting Nos</h2>
        <p>The prefix is optional. The starting number saved here will be the first receipt number used, then it will auto-increment.</p>
    </div>
</div>

<form method="post" action="{{ route('reports-other.cash-receipt.numbering.store') }}" class="reo-form reo-narrow-form">
    @csrf
    <div class="reo-field-grid">
        <div>
            <label class="reo-label" for="prefix">Prefix <span class="reo-optional">Optional</span></label>
            <input class="reo-input" type="text" id="prefix" name="prefix" maxlength="30" value="{{ old('prefix', optional($numbering)->prefix) }}" placeholder="Example: CR-">
        </div>
        <div>
            <label class="reo-label" for="starting_number">Starting Number <span class="required">*</span></label>
            <input class="reo-input" type="number" min="1" step="1" id="starting_number" name="starting_number" required value="{{ old('starting_number', optional($numbering)->next_number ?? 1) }}">
        </div>
    </div>

    @if($numbering)
        <div class="reo-preview">
            <span>Next receipt number preview</span>
            <strong>{{ ($numbering->prefix ?? '').$numbering->next_number }}</strong>
        </div>
    @endif

    <div class="reo-actions">
        <button class="reo-btn reo-btn-primary" type="submit">Save Numbering</button>
    </div>
</form>
