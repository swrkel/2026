@php
    $searchName = $searchName ?? 'q';
    $searchPlaceholder = $searchPlaceholder ?? __('leadsnew::messages.search');
    $createUrl = $createUrl ?? null;
    $createLabel = $createLabel ?? __('leadsnew::messages.add_lead');
@endphp
<div class="ln-toolbar">
    <form method="get" class="ln-search">
        <input type="text" name="{{ $searchName }}" value="{{ request($searchName) }}" class="form-control" placeholder="{{ $searchPlaceholder }}">
        @if(!empty($showDateRange))
            <input type="text" name="date_range" value="{{ request('date_range') }}" class="form-control" placeholder="{{ __('leadsnew::messages.date_range') }}">
        @endif
        <button class="btn btn-default"><i class="fa fa-search"></i> {{ __('leadsnew::messages.search') }}</button>
        @if(request()->has($searchName) || request()->has('date_range'))
            <a href="{{ url()->current() }}" class="btn btn-link">{{ __('leadsnew::messages.clear') }}</a>
        @endif
    </form>
    <div class="ln-toolbar-actions">
        <button type="button" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> {{ __('leadsnew::messages.excel') }}</button>
        <button type="button" class="btn btn-info btn-sm"><i class="fa fa-file-text-o"></i> {{ __('leadsnew::messages.csv') }}</button>
        <button type="button" class="btn btn-danger btn-sm"><i class="fa fa-file-pdf-o"></i> {{ __('leadsnew::messages.pdf') }}</button>
        <button type="button" class="btn btn-default btn-sm" onclick="window.print()"><i class="fa fa-print"></i> {{ __('leadsnew::messages.print') }}</button>
        <button type="button" class="btn btn-primary btn-sm"><i class="fa fa-columns"></i> {{ __('leadsnew::messages.column_visibility') }}</button>
        @if($createUrl)
            <a href="{{ $createUrl }}" class="btn ln-btn-primary btn-sm"><i class="fa fa-plus"></i> {{ $createLabel }}</a>
        @endif
    </div>
</div>
