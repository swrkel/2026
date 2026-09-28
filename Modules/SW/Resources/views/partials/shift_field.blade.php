{{--
    The SW Shift No field, for core screens.

    Included on expense, customer payment, cash deposit and purchase forms so a
    cash movement can be attributed to a shift. Those four feed Balance In Hand
    on the Daily Cash Status tab:

        Cash Collection + Customer Payment Cash
      - Cash Expenses - Cash Purchases - Cash Deposit
      = Balance In Hand

    RENDERS NOTHING unless SW is enabled for this business. A business without
    SW has no reason to see a shift dropdown on its expense form, and a field
    that appears for everyone is a field everyone has to learn to ignore.

    OPTIONAL, always. An expense unconnected to a shift is ordinary - most are.
    Requiring it would block every entry made outside shift hours.

    Usage:

        @include('sw::partials.shift_field')
        @include('sw::partials.shift_field', ['selected' => $transaction->sw_shift_no])
        @include('sw::partials.shift_field', ['locationId' => $transaction->location_id])
--}}

@php
    $__sw_enabled = false;

    try {
        $__sw_business_id = (int) (
            session('business.id')
            ?: session('user.business_id')
            ?: (optional(auth()->user())->business_id ?? 0)
        );

        /*
         | IS2263 - check the table on the LIVE tenant connection.
         |
         | This partial is embedded in Finance/Customer/Expense/Purchase pages.
         | On a multi-tenant request the Schema facade can still hold metadata
         | from the connection that existed before tenant.context switched DBs.
         | A direct zero-row query is cheap and cannot return stale metadata.
         */
        $__sw_shift_table_ready = false;
        try {
            \Illuminate\Support\Facades\DB::select('SELECT 1 FROM `sw_shifts` LIMIT 0');
            $__sw_shift_table_ready = true;
        } catch (\Throwable $ignored) {
            $__sw_shift_table_ready = false;
        }

        $__sw_enabled = $__sw_business_id > 0
            && class_exists(\App\Utils\SidebarPermissionUtil::class)
            && \App\Utils\SidebarPermissionUtil::isVisibleInSidebar('sw', $__sw_business_id)
            && $__sw_shift_table_ready;
    } catch (\Throwable $e) {
        // A business without SW simply does not see the field. Never let this
        // check break the form it is embedded in - these are core screens, and
        // an expense form that will not render is far worse than a missing
        // dropdown.
        $__sw_enabled = false;
    }

    $__sw_selected = $selected ?? old('sw_shift_no') ?? null;
    $__sw_location = $locationId ?? null;
    $__sw_uid = 'sw_field_' . uniqid();

    /*
     | Several of these forms ALREADY have a location dropdown - the expense and
     | purchase screens do.
     |
     | Pass its selector as $bindLocation and this field follows it instead of
     | offering a second one. Two location dropdowns on one form is an invitation
     | for them to disagree, and then nobody can say which the entry belongs to.
     |
     |     @include('sw::partials.shift_field', ['bindLocation' => '#location_id'])
    */
    $__sw_bind = $bindLocation ?? null;

    /*
     | IS2263 - Purchase / Add Purchase: make the SW Shift No field 100% wider
     | than the old col-sm-6 build (full col-sm-12 inside this partial).
     |
     | Some installations use a custom purchase route name/path, so do not rely
     | only on the four legacy URL patterns. This partial is only embedded on
     | purchase entry forms, therefore a purchase route/name is a safe signal.
    */
    $__sw_route_name = strtolower((string) optional(request()->route())->getName());
    $__sw_path = strtolower((string) request()->path());
    $__sw_purchase_route_is_entry = str_contains($__sw_route_name, 'purchase')
        && (str_contains($__sw_route_name, 'create') || str_contains($__sw_route_name, 'add'));
    $__sw_purchase_path_is_entry = str_contains($__sw_path, 'purchase')
        && (str_contains($__sw_path, 'create') || str_contains($__sw_path, 'add'));

    $__sw_is_purchase_entry = request()->is('purchases/create*')
        || request()->is('purchase/create*')
        || request()->is('purchases/add*')
        || request()->is('purchase/add*')
        || $__sw_purchase_route_is_entry
        || $__sw_purchase_path_is_entry;

    /*
     | Never let a missing named route crash a host/core form.
     |
     | The current SW routes register sw.shift-options, and the service
     | provider also supplies a compatibility fallback for older route sets.
     | url() is kept as the last-resort endpoint string so Blade can still
     | render even before a stale route cache has been cleared.
    */
    try {
        $__sw_shift_options_url = \Illuminate\Support\Facades\Route::has('sw.shift-options')
            ? route('sw.shift-options')
            : url('/sw/shift-options');
    } catch (\Throwable $e) {
        $__sw_shift_options_url = url('/sw/shift-options');
    }
@endphp

@if ($__sw_enabled)
    @php
        /*
         | Live metadata helper. Do not use Schema::hasColumn() here for the
         | same tenant-switch reason explained above.
        */
        $__sw_live_has_column = static function (string $table, string $column): bool {
            try {
                return \Illuminate\Support\Facades\DB::selectOne(
                    'SELECT 1 AS present FROM information_schema.columns
                     WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1',
                    [$table, $column]
                ) !== null;
            } catch (\Throwable $e) {
                return false;
            }
        };

        $__sw_locations_query = \Illuminate\Support\Facades\DB::table('business_locations')
            ->where('business_id', $__sw_business_id);

        if ($__sw_live_has_column('business_locations', 'is_active')) {
            $__sw_locations_query->where('is_active', 1);
        }

        $__sw_locations = $__sw_locations_query
            ->orderBy('name')
            ->pluck('name', 'id');

        // One location means there is nothing to choose. Selecting it for the
        // user saves a click on every single entry.
        if ($__sw_locations->count() === 1) {
            $__sw_location = $__sw_location ?: $__sw_locations->keys()->first();
        }

        /*
         | IS2263 - render the CURRENT open-shift list on the server as well as
         | refreshing it by AJAX.
         |
         | Finance Cash/Card Deposit forms are AJAX modals. A server-rendered
         | list means a newly opened shift is already present the moment a fresh
         | modal arrives, even if Select2/global modal JavaScript runs later.
         | Closed shifts are excluded here, so a fresh deposit form can never
         | inherit a stale closed option from the previous modal.
        */
        $__sw_initial_shifts = collect();

        try {
            $__sw_initial_query = \Illuminate\Support\Facades\DB::table('sw_shifts')
                ->where('business_id', $__sw_business_id)
                ->whereRaw("LOWER(TRIM(CAST(status AS CHAR))) IN ('0', 'open', 'opened')");

            if ($__sw_live_has_column('sw_shifts', 'closed_at')) {
                $__sw_initial_query->whereNull('closed_at');
            }

            if ($__sw_live_has_column('sw_shifts', 'deleted_at')) {
                $__sw_initial_query->whereNull('deleted_at');
            }

            // If the server already knows the form location, keep the first
            // render as tight as the AJAX feed. Otherwise Finance safely gets
            // the business-wide list and labels identify each location.
            if ((int) $__sw_location > 0) {
                $__sw_initial_query->where('location_id', (int) $__sw_location);
            }

            $__sw_initial_shifts = $__sw_initial_query
                ->orderByDesc('shift_date')
                ->orderByDesc('id')
                ->get(['id', 'sw_shift_no', 'shift_date', 'shift_name', 'location_id']);
        } catch (\Throwable $ignored) {
            // The AJAX endpoint remains the recovery path. Never break a host
            // form merely because this optional initial fill could not run.
            $__sw_initial_shifts = collect();
        }
    @endphp

    <div class="row sw-shift-field{{ $__sw_is_purchase_entry ? ' sw-shift-field--purchase' : '' }}" id="{{ $__sw_uid }}">

        @if ($__sw_bind)
            {{-- Following the form's own location field. --}}
            <input type="hidden" class="sw-shift-location" value="{{ $__sw_location }}"
                data-bind-location="{{ $__sw_bind }}">
        @elseif ($__sw_locations->count() > 1)
            <div class="col-sm-4">
                <div class="form-group">
                    <label>@lang('sw::lang.location')</label>
                    <select class="form-control sw-shift-location" style="width:100%">
                        <option value="">@lang('messages.please_select')</option>
                        @foreach ($__sw_locations as $id => $name)
                            <option value="{{ $id }}" @selected((int) $__sw_location === (int) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @else
            <input type="hidden" class="sw-shift-location" value="{{ $__sw_location }}">
        @endif

        {{-- Keep the compact established width everywhere except Purchase.
             IS2263 specifically requires Purchase / Add Purchase to be 100%
             wider than the previous col-sm-6 field, so it uses the full row. --}}
        @php
            $__sw_shift_col = $__sw_is_purchase_entry
                ? '12'
                : ((! $__sw_bind && $__sw_locations->count() > 1) ? '4' : '3');
        @endphp
        <div class="col-sm-{{ $__sw_shift_col }}"
             @if($__sw_is_purchase_entry) style="width:100%;max-width:100%;float:none;" @endif>
            <div class="form-group">
                <label>
                    @lang('sw::lang.shift_no')
                    <small class="text-muted">&mdash; @lang('sw::lang.optional')</small>
                </label>
                <select name="sw_shift_no" class="form-control sw-shift-select" style="width:100%"
                    data-saved-shift="{{ $__sw_selected }}">
                    <option value="">@lang('sw::lang.not_linked_to_a_shift')</option>
                    @if ($__sw_selected)
                        {{-- The saved value is offered even before the list
                             loads, so an edit form never appears to have lost
                             it. --}}
                        <option value="{{ $__sw_selected }}" selected>{{ $__sw_selected }}</option>
                    @endif

                    @foreach ($__sw_initial_shifts as $__sw_shift)
                        @continue($__sw_selected && (string) $__sw_selected === (string) $__sw_shift->sw_shift_no)
                        @php
                            $__sw_shift_label = (string) $__sw_shift->sw_shift_no;
                            $__sw_shift_location_name = $__sw_locations[(int) $__sw_shift->location_id] ?? null;

                            if ((! $__sw_location || (int) $__sw_shift->location_id !== (int) $__sw_location)
                                && $__sw_shift_location_name) {
                                $__sw_shift_label .= '  ·  ' . $__sw_shift_location_name;
                            }

                            if (! empty($__sw_shift->shift_date)) {
                                $__sw_shift_label .= '  ·  ' . \Carbon\Carbon::parse($__sw_shift->shift_date)->format('d/m/Y');
                            }

                            if (! empty($__sw_shift->shift_name)) {
                                $__sw_shift_label .= '  ·  ' . $__sw_shift->shift_name;
                            }
                        @endphp
                        <option value="{{ $__sw_shift->sw_shift_no }}">{{ $__sw_shift_label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

    </div>

    {{--
        The script is INLINE, not pushed to a stack.

        @push('javascript') sends it to the layout, which renders that stack
        once when the page is built. Several of these forms - the Finance cash
        deposit among them - are fetched by AJAX and injected into a page that
        has already rendered, so the stack never receives it. The field arrived
        with nothing to fill it: location set, one option, no fill ever run.

        Inline, the script travels with the field wherever it goes.

        The guard below replaces @once. Two copies of this partial on one page
        would otherwise bind everything twice, and a fetch that fires twice is
        a fetch that races itself.
    --}}
    <script>
    (function () {
        if (window.__swShiftFieldReady) { return; }
        window.__swShiftFieldReady = true;

    $(function () {
        /*
         | Location first, then that location's OPEN shifts.
         |
         | Delegated and keyed on the wrapper, so this works whether the
         | field is on a page or inside a modal loaded later.
        */
        function swLoadShifts($wrap, keepSelected) {
            var $loc = $wrap.find('.sw-shift-location');
            var $sel = $wrap.find('.sw-shift-select');
            var current = keepSelected ? String($sel.val() || '') : '';
            var savedShift = String($sel.data('saved-shift') || '');
            var locationId = $loc.val();

            // When bound to the form's own location field, read it directly
            // rather than trusting a hidden copy that may not have been set
            // when this ran.
            var bound = $loc.data('bind-location');

            /*
             * IS2259: many ERP pages contain more than one #location_id (the
             * underlying page plus AJAX modals).  $(bound).first() could bind
             * the SW field to the WRONG form, so the selected shift vanished
             * when the list refreshed.  Resolve the location inside this
             * field's own form/modal first and never borrow an unrelated
             * location from another form on the page.
             */
            function localBoundSource() {
                if (!bound) { return $(); }

                var $form = $wrap.closest('form');
                var $src = $form.length ? $form.find(bound).first() : $();

                if (!$src.length) {
                    var $modal = $wrap.closest('.modal');
                    $src = $modal.length ? $modal.find(bound).first() : $();
                }

                if (!$src.length) {
                    var $panel = $wrap.closest('.box, .card, .content, .modal-content');
                    $src = $panel.length ? $panel.find(bound).first() : $();
                }

                return $src;
            }

            if (bound) {
                var $src = localBoundSource();
                if ($src.length) {
                    locationId = $src.val() || '';
                    $loc.val(locationId);
                }
            }

            // Only a VISIBLE location selector should force the user to choose
            // first.  Hidden/host-bound fields with no value may safely request
            // the business-wide open-shift list (Finance modals use this).
            if (!locationId && !bound && $loc.is('select')) {
                $sel.html('<option value="">{{ __('sw::lang.choose_a_location_first') }}</option>');
                return;
            }

            // IS2257: Finance Cash/Card Deposit can embed this partial without a
            // visible location value. The endpoint now supports a business-wide
            // open-shift list when location_id is empty. This keeps Finance from
            // showing an empty Daily Shift No after a shift is opened.

            var shiftOptionsUrl = @json($__sw_shift_options_url);

            // A modal can fire focus/mousedown/select2:opening almost together.
            // Abort its older request so a slower stale response cannot replace
            // the newest open/closed shift state.
            var previousRequest = $wrap.data('sw-shift-xhr');
            if (previousRequest && previousRequest.readyState !== 4) {
                previousRequest.abort();
            }
            var requestSeq = Number($wrap.data('sw-shift-seq') || 0) + 1;
            $wrap.data('sw-shift-seq', requestSeq);

            var request = $.ajax({
                url: shiftOptionsUrl,
                method: 'GET',
                dataType: 'json',
                cache: false,
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache'
                },
                data: {
                    location_id: locationId,
                    selected_shift_no: current,
                    keep_selected: keepSelected ? 1 : 0,
                    _: Date.now()
                }
            });
            $wrap.data('sw-shift-xhr', request);

            request.done(function (rows) {
                if (requestSeq !== Number($wrap.data('sw-shift-seq') || 0)) { return; }

                /*
                 | IS2260 - never overwrite a choice made while this AJAX request
                 | was in flight.  Select2 fires `select2:opening` before the user
                 | chooses an option.  The old code captured the PREVIOUS value in
                 | `current`, then its response arrived after the click and rebuilt
                 | the <select> with that old value.  The freshly selected SW shift
                 | therefore appeared to "disappear" on Customer Payment, Expenses
                 | New and Purchase forms.
                 |
                 | For a normal refresh keep the value that is on the element NOW.
                 | A real location change calls this function with keepSelected=false
                 | and intentionally clears a shift from the old location.
                */
                var liveCurrent = keepSelected ? String($sel.val() || current || '') : '';
                var html = '<option value="">{{ __('sw::lang.not_linked_to_a_shift') }}</option>';

                var found = false;
                $.each(rows || [], function (i, r) {
                    var no = String(r.no || '');
                    if (no === liveCurrent) { found = true; }
                    html += '<option value="' + $('<div>').text(no).html() + '"'
                          + (no === liveCurrent ? ' selected' : '') + '>'
                          + $('<div>').text(r.label).html() + '</option>';
                });

                /*
                 | Preserve a CLOSED shift only when it was already saved on an
                 | existing record. A shift merely selected on a new Finance form
                 | must disappear as soon as it closes, otherwise a new cash/card
                 | deposit could still be posted to a closed shift.
                */
                if (liveCurrent && !found && savedShift && liveCurrent === savedShift) {
                    html += '<option value="' + $('<div>').text(liveCurrent).html() + '" selected>'
                          + $('<div>').text(liveCurrent).html()
                          + ' ({{ __('sw::lang.closed') }})</option>';
                }

                $sel.html(html);
                if (!found && !(savedShift && liveCurrent === savedShift)) {
                    $sel.val('');
                }
                $sel.trigger('change.select2');

                // Mark exactly when this control last received an authoritative
                // OPEN-shift list.  The modal/dropdown handlers use this to avoid
                // unnecessarily racing the user's next click.
                $wrap.data('sw-shift-refreshed-at', Date.now());
            }).fail(function (xhr, status) {
                if (status === 'abort' || requestSeq !== Number($wrap.data('sw-shift-seq') || 0)) {
                    return;
                }

                // The host form must remain usable even if an older deployment
                // has stale route cache or SW is temporarily unavailable.
                var html = '<option value="">{{ __('sw::lang.not_linked_to_a_shift') }}</option>';
                if (current) {
                    html += '<option value="' + $('<div>').text(current).html() + '" selected>'
                          + $('<div>').text(current).html() + '</option>';
                }
                $sel.html(html);
            }).always(function () {
                if ($wrap.data('sw-shift-xhr') === request) {
                    $wrap.removeData('sw-shift-xhr');
                }
            });

            return request;
        }

        $(document).on('change', '.sw-shift-field .sw-shift-location', function () {
            swLoadShifts($(this).closest('.sw-shift-field'), false);
        });

        /*
         | IS2260 - a user's selection wins over an older refresh request.
         | Incrementing the sequence makes a response that started before the
         | selection harmless.  The next deliberate refresh still loads normally.
        */
        $(document).on('change', '.sw-shift-select', function (e) {
            if (e && e.namespace === 'select2') { return; }

            var $wrap = $(this).closest('.sw-shift-field');
            var pending = $wrap.data('sw-shift-xhr');
            if (pending && pending.readyState !== 4) {
                pending.abort();
            }
            $wrap.data('sw-shift-seq', Number($wrap.data('sw-shift-seq') || 0) + 1);
        });

        /*
         | When bound to the form's own location field, follow it.
         |
         | Delegated on document so it works whether that field is a plain
         | select or a select2, and whether it exists yet or arrives with a
         | modal.
        */
        /*
         | Follow the host form's own location field where one was named.
         |
         | Delegated on document and keyed off the hidden input, so it works
         | for fields that arrive later and for select2, which fires change
         | on the original element.
        */
        $(document).on('change', 'select, input', function () {
            var $changed = $(this);

            $('.sw-shift-field .sw-shift-location[data-bind-location]').each(function () {
                var $hidden = $(this);
                var $wrap = $hidden.closest('.sw-shift-field');
                var selector = $hidden.data('bind-location');
                if (!selector) { return; }

                var $form = $wrap.closest('form');
                var $expected = $form.length ? $form.find(selector).first() : $();
                if (!$expected.length) {
                    var $modal = $wrap.closest('.modal');
                    $expected = $modal.length ? $modal.find(selector).first() : $();
                }

                // Ignore a same-id location control belonging to another form.
                if (!$expected.length || $changed[0] !== $expected[0]) { return; }

                $hidden.val($changed.val() || '');
                // A REAL location change must clear a previous location's shift.
                swLoadShifts($wrap, false);
            });
        });

        /*
         | Fill every field, however it arrived.
         |
         | Waiting on shown.bs.modal was not enough. Several of these forms
         | are fetched by AJAX and injected, and a field that appears that
         | way may never see the event - the Finance cash deposit modal
         | showed an empty dropdown for exactly this reason, while the
         | server was returning both shifts perfectly well.
         |
         | So each field marks itself once filled, and a short poll catches
         | any that appear later. Cheap - it only looks for unfilled fields,
         | and does nothing when there are none.
        */
        function swFillPending() {
            $('.sw-shift-field').not('[data-sw-filled]').each(function () {
                var $wrap = $(this);
                $wrap.attr('data-sw-filled', '1');
                swLoadShifts($wrap, true);
            });
        }

        swFillPending();

        // Every modal opening is a fresh view of OPEN shifts. Do not reuse the
        // list from the previous time the Finance Cash/Card Deposit modal opened.
        $(document).on('shown.bs.modal', function (e) {
            $(e.target).find('.sw-shift-field').each(function () {
                var $wrap = $(this);
                $wrap.attr('data-sw-filled', '1');
                swLoadShifts($wrap, true);
            });
        });

        // Refresh immediately before the user opens the dropdown. This covers a
        // shift that was opened/closed in another tab while this modal stayed up.
        //
        // IS2260: Select2 builds its visible results at opening time. Merely
        // starting an async refresh in `select2:opening` meant the user could
        // still see the OLD list for that opening. Hold the opening very briefly
        // until the authoritative OPEN-shift request finishes, then open Select2
        // once with the fresh list.
        $(document).on('select2:opening', '.sw-shift-select', function (e) {
            var $sel = $(this);
            var $wrap = $sel.closest('.sw-shift-field');

            if ($wrap.data('sw-opening-after-refresh')) {
                $wrap.removeData('sw-opening-after-refresh');
                return;
            }

            /*
             | IS2263 - always verify against the live OPEN-shift feed before
             | Select2 opens. Do not reuse even a sub-second-old list: a shift
             | may have been opened/closed in another tab immediately before
             | this click.
            */
            var request = swLoadShifts($wrap, true);
            if (!request || typeof request.always !== 'function') {
                return;
            }

            e.preventDefault();
            request.always(function () {
                // The field/modal may have been removed while the request ran.
                if (!$sel.closest('html').length || !$sel.hasClass('select2-hidden-accessible')) {
                    return;
                }

                $wrap.data('sw-opening-after-refresh', 1);
                $sel.select2('open');
            });
        });

        // Plain (non-Select2) controls still get a best-effort refresh on focus.
        $(document).on('focus', '.sw-shift-select:not(.select2-hidden-accessible)', function () {
            swLoadShifts($(this).closest('.sw-shift-field'), true);
        });

        // Returning to a Finance page after opening/closing a shift elsewhere
        // must refresh visible fields without needing a full page reload.
        $(window).on('focus.swShiftOptions', function () {
            $('.sw-shift-field:visible').each(function () {
                swLoadShifts($(this), true);
            });
        });

        $(document).on('visibilitychange.swShiftOptions', function () {
            if (!document.hidden) {
                $('.sw-shift-field:visible').each(function () {
                    swLoadShifts($(this), true);
                });
            }
        });

        // And anything injected without one.
        setInterval(swFillPending, 700);
    });
    })();
    </script>
@endif
