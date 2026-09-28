@extends('layouts.app')
@section('title', 'Module Date Defaults')

@section('content')
<section class="content-header">
    <h1>
        Module Date Defaults
        <small>Computer Date or Global Date by module</small>
    </h1>
    <div style="margin-top:10px;">
        <a href="{{ route('business.getBusinessSettings') }}" class="btn btn-default">
            <i class="fa fa-arrow-left"></i> Back to Business Settings
        </a>
    </div>
</section>

<section class="content">
    @if(!\Illuminate\Support\Facades\Schema::hasTable('business_module_date_settings'))
        <div class="alert alert-warning">
            <strong>Database setup required.</strong>
            Run the supplied tenant migration/SQL before saving these settings.
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul style="margin-bottom:0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="box box-primary" style="border-radius:14px; overflow:hidden;">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-calendar"></i> Global Date</h3>
        </div>

        {!! Form::open(['route' => 'business.module-date-defaults.update', 'method' => 'post', 'id' => 'module_date_defaults_form']) !!}
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="global_date">Global Date</label>
                        <input type="date"
                               id="global_date"
                               name="global_date"
                               class="form-control"
                               value="{{ old('global_date', $globalDate) }}">
                        <p class="help-block">
                            Modules set to <strong>Global Date</strong> use this date as the default transaction date on new forms.
                        </p>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="callout callout-info" style="margin-top:0;">
                        <h4>How it works</h4>
                        <p style="margin-bottom:4px;"><strong>Computer Date</strong> uses the date from the user's own computer/browser.</p>
                        <p style="margin-bottom:4px;"><strong>Global Date</strong> uses the Global Date selected here.</p>
                        <p style="margin-bottom:0;">Only new transaction/operation/payment forms are auto-filled. Saved dates on Edit forms are never overwritten.</p>
                    </div>
                </div>
            </div>

            <div class="row" style="margin-bottom:12px;">
                <div class="col-sm-6">
                    <input type="text" id="module_date_search" class="form-control" placeholder="Search modules...">
                </div>
                <div class="col-sm-6 text-right" style="padding-top:7px;">
                    <span class="label label-primary" style="font-size:13px;">{{ count($modules) }} Modules / Areas</span>
                </div>
            </div>

            <div class="table-responsive" style="border:1px solid #e5e9f2; border-radius:12px;">
                <table class="table table-hover" id="module_date_settings_table" style="margin-bottom:0;">
                    <thead>
                        <tr>
                            <th style="width:38%;">Module / Area</th>
                            <th style="width:20%;" class="text-center">Computer Date</th>
                            <th style="width:20%;" class="text-center">Global Date</th>
                            <th style="width:22%;">Effective Default</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($modules as $moduleKey => $moduleLabel)
                            @php
                                $source = old('module_settings.' . $moduleKey, $moduleSettings[$moduleKey] ?? 'computer');
                            @endphp
                            <tr class="module-date-row" data-module-search="{{ strtolower($moduleKey . ' ' . $moduleLabel) }}">
                                <td>
                                    <strong>{{ $moduleLabel }}</strong>
                                    <div class="text-muted" style="font-size:12px;">{{ $moduleKey }}</div>
                                </td>
                                <td class="text-center">
                                    <label style="font-weight:normal; cursor:pointer;">
                                        <input type="radio"
                                               name="module_settings[{{ $moduleKey }}]"
                                               value="computer"
                                               {{ $source !== 'global' ? 'checked' : '' }}>
                                        Computer Date
                                    </label>
                                </td>
                                <td class="text-center">
                                    <label style="font-weight:normal; cursor:pointer;">
                                        <input type="radio"
                                               name="module_settings[{{ $moduleKey }}]"
                                               value="global"
                                               {{ $source === 'global' ? 'checked' : '' }}>
                                        Global Date
                                    </label>
                                </td>
                                <td>
                                    <span class="effective-date-label"
                                          data-source="{{ $source === 'global' ? 'global' : 'computer' }}"></span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="box-footer text-right">
            <button type="submit" class="btn btn-primary btn-lg" {{ !\Illuminate\Support\Facades\Schema::hasTable('business_module_date_settings') ? 'disabled' : '' }}>
                <i class="fa fa-save"></i> Save Date Settings
            </button>
        </div>
        {!! Form::close() !!}
    </div>
</section>
@endsection

@section('javascript')
<script>
(function () {
    function localIsoDate() {
        var now = new Date();
        var y = now.getFullYear();
        var m = String(now.getMonth() + 1).padStart(2, '0');
        var d = String(now.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function refreshEffectiveDates() {
        var globalDate = document.getElementById('global_date').value || 'Not set';
        var computerDate = localIsoDate();
        document.querySelectorAll('#module_date_settings_table tbody tr').forEach(function (row) {
            var checked = row.querySelector('input[type="radio"]:checked');
            var label = row.querySelector('.effective-date-label');
            if (!checked || !label) return;
            if (checked.value === 'global') {
                label.textContent = globalDate;
                label.className = 'effective-date-label label label-success';
            } else {
                label.textContent = computerDate + ' (this computer)';
                label.className = 'effective-date-label label label-info';
            }
        });
    }

    document.getElementById('module_date_search').addEventListener('input', function () {
        var term = this.value.toLowerCase().trim();
        document.querySelectorAll('.module-date-row').forEach(function (row) {
            row.style.display = !term || row.getAttribute('data-module-search').indexOf(term) !== -1 ? '' : 'none';
        });
    });

    document.getElementById('global_date').addEventListener('change', refreshEffectiveDates);
    document.getElementById('module_date_settings_table').addEventListener('change', function (event) {
        if (event.target && event.target.type === 'radio') refreshEffectiveDates();
    });

    refreshEffectiveDates();
})();
</script>
@endsection
