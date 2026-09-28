<input id="business_count" type="hidden" value="{{ sizeof($businesses) }}">
@php
    $businessesFormatted = $businesses->mapWithKeys(function ($item) {
        // This is a BUSINESS selector, so the visible label must come from
        // the current tenant's business.name. Do not substitute a location or
        // a same-company-number row from the central database.
        $label = trim((string) ($item->name ?? ''));
        // Use the tenant business primary key as the selector value. Company
        // numbers are still passed to legacy display routes, but they are not
        // trusted as a unique business identity.
        return [(string) $item->id => $label];
    });
    $businessesJson = $businesses->keyBy('id')->toJson();
@endphp

{!! Form::label('pumper_business_id', 'Choose your Business', [
    'style' => 'color: black
                                                                                              !important;',
]) !!}
{!! Form::select('pumper_business_id', $businessesFormatted, null, [
    'class' => 'form-control',
    'style' => 'width: 100%;',
    'id' => 'pumper_business_id',
    'placeholder' => __('lang_v1.please_select'),
]) !!}
@if ($came_from_system)
    <small class="text-muted d-block mt-2" style="color: #666; font-size: 12px;">
        <i class="fa fa-info-circle"></i> Please select your station/company before logging in.
    </small>
@endif
