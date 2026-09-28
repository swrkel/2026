{{--
 | Optional SW Shift selector for Finance Cash/Card Deposit.
 |
 | IMPORTANT: this partial is owned by Finance and has no Blade namespace,
 | controller-class, model-class or route dependency on the SW module.
 | The previous @includeIf('sw::partials.shift_field') could still throw
 | "No hint path defined for [sw]" when the SW provider was not registered;
 | both Cash Deposit and Card Deposit use the same deposit view, so both forms
 | then failed to open with HTTP 500.
 |
 | If the active tenant has SW enabled/tables, open shifts are read directly
 | from the tenant DB. Otherwise this renders nothing and Finance continues.
--}}
@php
    $__financeSwEnabled = false;
    $__financeSwLocations = collect();
    $__financeSwShiftsByLocation = [];
    $__financeSwBusinessId = (int) (session('business.id') ?: session('user.business_id') ?: 0);
    $__financeSwSelected = old('sw_shift_no') ?: null;
    $__financeSwUid = 'finance_sw_shift_' . uniqid();

    try {
        $__financeConnection = \Illuminate\Support\Facades\DB::connection();
        $__financeSchema = $__financeConnection->getSchemaBuilder();

        $__financeSwEnabled = $__financeSwBusinessId > 0
            && $__financeSchema->hasTable('sw_shifts')
            && $__financeSchema->hasTable('business_locations');

        // Respect Manage Side Bar when the utility exists, but never allow an
        // optional integration check to break the host Finance form.
        if ($__financeSwEnabled && class_exists(\App\Utils\SidebarPermissionUtil::class)) {
            try {
                $__financeSwEnabled = \App\Utils\SidebarPermissionUtil::isVisibleInSidebar(
                    'sw',
                    $__financeSwBusinessId
                );
            } catch (\Throwable $e) {
                // Table presence is enough as a compatibility fallback.
                $__financeSwEnabled = true;
            }
        }

        if ($__financeSwEnabled) {
            $__financeSwLocations = $__financeConnection->table('business_locations')
                ->where('business_id', $__financeSwBusinessId)
                ->orderBy('name')
                ->pluck('name', 'id');

            $__financeShiftColumns = ['id', 'business_id', 'location_id', 'status'];
            foreach (['sw_shift_no', 'shift_date', 'shift_name'] as $__financeColumn) {
                if ($__financeSchema->hasColumn('sw_shifts', $__financeColumn)) {
                    $__financeShiftColumns[] = $__financeColumn;
                }
            }

            if (in_array('sw_shift_no', $__financeShiftColumns, true)) {
                $__financeSwRows = $__financeConnection->table('sw_shifts')
                    ->where('business_id', $__financeSwBusinessId)
                    ->where('status', 0)
                    ->orderByDesc('id')
                    ->limit(200)
                    ->get($__financeShiftColumns);

                foreach ($__financeSwRows as $__financeShift) {
                    $__financeLocationId = (string) ($__financeShift->location_id ?? '');
                    if ($__financeLocationId === '') {
                        continue;
                    }

                    $__financeLabel = (string) ($__financeShift->sw_shift_no ?? '');
                    if (!empty($__financeShift->shift_date ?? null)) {
                        $__financeLabel .= ' · ' . substr((string) $__financeShift->shift_date, 0, 10);
                    }
                    if (!empty($__financeShift->shift_name ?? null)) {
                        $__financeLabel .= ' · ' . (string) $__financeShift->shift_name;
                    }

                    $__financeSwShiftsByLocation[$__financeLocationId][] = [
                        'no' => (string) ($__financeShift->sw_shift_no ?? ''),
                        'label' => $__financeLabel,
                    ];
                }
            }
        }
    } catch (\Throwable $e) {
        $__financeSwEnabled = false;
        $__financeSwLocations = collect();
        $__financeSwShiftsByLocation = [];
        try {
            \Illuminate\Support\Facades\Log::warning('Finance deposit: optional SW shift field skipped', [
                'business_id' => $__financeSwBusinessId,
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $ignored) {
        }
    }
@endphp

@if($__financeSwEnabled && $__financeSwLocations->isNotEmpty())
    <div class="row finance-sw-shift-field" id="{{ $__financeSwUid }}">
        @if($__financeSwLocations->count() > 1)
            <div class="col-sm-4">
                <div class="form-group">
                    <label>Location</label>
                    <select class="form-control finance-sw-shift-location" style="width:100%">
                        <option value="">@lang('messages.please_select')</option>
                        @foreach($__financeSwLocations as $__financeLocationId => $__financeLocationName)
                            <option value="{{ $__financeLocationId }}">{{ $__financeLocationName }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @else
            <input type="hidden"
                   class="finance-sw-shift-location"
                   value="{{ $__financeSwLocations->keys()->first() }}">
        @endif

        <div class="col-sm-4">
            <div class="form-group">
                <label>SW Shift No <small class="text-muted">- Optional</small></label>
                <select name="sw_shift_no" class="form-control finance-sw-shift-select" style="width:100%">
                    <option value="">Not linked to a shift</option>
                    @if($__financeSwSelected)
                        <option value="{{ $__financeSwSelected }}" selected>{{ $__financeSwSelected }}</option>
                    @endif
                </select>
            </div>
        </div>
    </div>

    <script>
    (function ($) {
        'use strict';

        var $wrap = $('#{{ $__financeSwUid }}');
        if (!$wrap.length) return;

        var shiftsByLocation = @json($__financeSwShiftsByLocation);
        var selectedShift = @json($__financeSwSelected);

        function fillFinanceSwShifts(keepSelected) {
            var locationId = String($wrap.find('.finance-sw-shift-location').val() || '');
            var $select = $wrap.find('.finance-sw-shift-select');
            var current = keepSelected ? String($select.val() || selectedShift || '') : '';
            var rows = shiftsByLocation[locationId] || [];
            var html = '<option value="">Not linked to a shift</option>';
            var found = false;

            $.each(rows, function (_, row) {
                var no = String(row.no || '');
                if (!no) return;
                if (no === current) found = true;
                html += '<option value="' + $('<div>').text(no).html() + '"'
                    + (no === current ? ' selected' : '') + '>'
                    + $('<div>').text(row.label || no).html() + '</option>';
            });

            if (current && !found) {
                html += '<option value="' + $('<div>').text(current).html() + '" selected>'
                    + $('<div>').text(current).html() + ' (Closed/previous)</option>';
            }

            $select.html(html);

            if ($.fn.select2 && !$select.hasClass('select2-hidden-accessible')) {
                $select.select2({
                    width: '100%',
                    dropdownParent: $wrap.closest('.modal')
                });
            }
        }

        $wrap.on('change', '.finance-sw-shift-location', function () {
            fillFinanceSwShifts(false);
        });

        if ($.fn.select2) {
            var $location = $wrap.find('.finance-sw-shift-location');
            if ($location.is('select') && !$location.hasClass('select2-hidden-accessible')) {
                $location.select2({
                    width: '100%',
                    dropdownParent: $wrap.closest('.modal')
                });
            }
        }

        fillFinanceSwShifts(true);
    })(jQuery);
    </script>
@endif
