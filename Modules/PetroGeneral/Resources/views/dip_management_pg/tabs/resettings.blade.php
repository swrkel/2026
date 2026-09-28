{{--
    Petro General / Dip Management / Resettings.

    Was a placeholder that rendered a "separated for maintenance" note and no
    data. It now lists the resettings DipIndexController already passes in, and
    carries an Add button.

    The Add button opens the SAME modal as the legacy Petro General screen -
    route petrogeneral.dip_management.resetting.create, served by
    DipManagementController@addResettingDip. The form is not re-created here, so
    the two screens cannot drift apart: whatever the legacy form does, this does.

    Its behaviour comes from partials/resetting_form_js.blade.php, included by
    this tab's parent page.
--}}
<div class="tab-pane {{ $active_tab == 'resettings' ? 'active' : '' }}" id="pg_dip_resettings">

    @component('components.widget', ['class' => 'box-primary', 'title' => __('petrogeneral::lang.dip_resetting')])

    @slot('tool')
    <div class="box-tools pull-right">
        <button type="button" class="btn btn-primary btn-modal"
                {{-- Plain URL, not route('petrogeneral.dip_management.resetting.create').

                     That route name is declared in Routes/dips.php, but it is not
                     registered on every installation - the deployed server raised
                     "Route [petrogeneral.dip_management.resetting.create] not
                     defined" and returned HTTP 500 for the whole page, because a
                     missing route name throws while the view is being rendered.

                     /petro-general/add-resetting-dip is registered directly in
                     Http/routes.php and is the URL the legacy screen's own Add
                     button resolves to, so this cannot depend on which of the two
                     route files an installation has. --}}
                data-href="{{ url('/petro-general/add-resetting-dip') }}"
                data-container=".pg_dip_modal">
            <i class="fa fa-balance-scale"></i> @lang('petrogeneral::lang.add_resetting')
        </button>
    </div>
    @endslot

    <div class="table-responsive">
        <table class="table table-bordered table-striped" style="width: 100%;">
            <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.add_dip_no')</th>
                    <th>@lang('petrogeneral::lang.date')</th>
                    <th>@lang('petrogeneral::lang.tank')</th>
                    <th>@lang('petrogeneral::lang.current_qty')</th>
                    <th>@lang('petrogeneral::lang.qty_difference')</th>
                    <th>@lang('petrogeneral::lang.new_qty')</th>
                    <th>@lang('petrogeneral::lang.reason')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($resettings as $resetting)
                    <tr>
                        <td>{{ $resetting->meter_reset_form_no ?? '' }}</td>
                        <td>{{ !empty($resetting->transaction_date) ? $resetting->transaction_date : ($resetting->date_and_time ?? '') }}</td>
                        <td>{{ $pgDipTankNames[$resetting->tank_id] ?? $resetting->tank_id }}</td>
                        <td>{{ $resetting->current_qty ?? '' }}</td>
                        <td>{{ $resetting->current_dip_difference ?? '' }}</td>
                        <td>{{ $resetting->reset_new_dip ?? '' }}</td>
                        <td>{{ $resetting->reason ?? '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            @lang('petrogeneral::lang.no_records_found')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @endcomponent
</div>
