@php($lead = $lead ?? null)
<div class="row">
    <div class="col-md-12"><h4 class="leads-new-side-title"><i class="fa fa-info-circle"></i> {{ __('leadsnew::messages.lead_information') }}</h4><hr></div>
    <div class="col-md-3 form-group">
        <label>{{ __('leadsnew::messages.lead_no') }}</label>
        <input name="lead_no" value="{{ old('lead_no', $lead->lead_no ?? $leadNo ?? '') }}" class="form-control" readonly>
    </div>
    <div class="col-md-3 form-group">
        <label>{{ __('leadsnew::messages.name') }} <span class="text-danger">*</span></label>
        <input name="name" value="{{ old('name', $lead->name ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-3 form-group">
        <label>{{ __('leadsnew::messages.mobile') }}</label>
        <input name="mobile" value="{{ old('mobile', $lead->mobile ?? '') }}" class="form-control">
    </div>
    <div class="col-md-3 form-group">
        <label>{{ __('leadsnew::messages.email') }}</label>
        <input type="email" name="email" value="{{ old('email', $lead->email ?? '') }}" class="form-control">
    </div>
    <div class="col-md-12"><h4 class="leads-new-side-title"><i class="fa fa-tags"></i> {{ __('leadsnew::messages.classification') }}</h4><hr></div>
    <div class="col-md-3 form-group">
        <label>{{ __('leadsnew::messages.source') }}</label>
        <input name="source" value="{{ old('source', $lead->source ?? '') }}" class="form-control">
    </div>
    <div class="col-md-3 form-group">
        <label>{{ __('leadsnew::messages.status') }}</label>
        @php($status = old('status', $lead->status ?? 'New'))
        <select name="status" class="form-control">
            @foreach(['New','In Progress','Converted','Lost'] as $option)
                <option value="{{ $option }}" {{ $status == $option ? 'selected' : '' }}>{{ $option }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 form-group">
        <label>{{ __('leadsnew::messages.priority') }}</label>
        @php($priority = old('priority', $lead->priority ?? 'Medium'))
        <select name="priority" class="form-control">
            @foreach(['Low','Medium','High'] as $option)
                <option value="{{ $option }}" {{ $priority == $option ? 'selected' : '' }}>{{ $option }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 form-group">
        <label>{{ __('leadsnew::messages.date') }}</label>
        <input type="date" name="transaction_date" value="{{ old('transaction_date', isset($lead) && $lead && $lead->transaction_date ? $lead->transaction_date->format('Y-m-d') : now()->format('Y-m-d')) }}" class="form-control">
    </div>
    <div class="col-md-12 form-group">
        <label>{{ __('leadsnew::messages.notes') }}</label>
        <textarea name="note" class="form-control">{{ old('note', $lead->note ?? '') }}</textarea>
    </div>
</div>
