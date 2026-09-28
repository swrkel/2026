@include('sw::partials.tab_styles')

{{--
    Daily Cash Status.

    Balance In Hand for ONE shift, chosen from those still open. Once closed the
    shift no longer appears here - its figures have been agreed.

        Cash Collection
      + Customer Payment - Cash
      - Cash Expenses
      - Cash Purchases
      - Cash Deposit
      = Balance In Hand
--}}

<section class="content">

    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('sw::lang.choose_a_shift')])

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('sw_cs_location', __('purchase.business_location') . ':') !!}
                        {!! Form::select('sw_cs_location', $business_locations ?? [], $default_location ?? null, [
                            'class' => 'form-control select2', 'id' => 'sw_dcs_location', 'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('sw_dcs_shift', __('sw::lang.shift_no') . ':') !!}
                        <select id="sw_dcs_shift" class="form-control" style="width:100%">
                            <option value="">{{ __('messages.please_select') }}</option>
                        </select>
                        <span class="help-block" style="margin-bottom:0">
                            @lang('sw::lang.open_shifts_only')
                        </span>
                    </div>
                </div>

            @endcomponent
        </div>
    </div>

    {{-- The panel is fetched when a shift is chosen, so the figures are always
         read at the moment they are looked at rather than when the tab was
         first opened. --}}
    <div id="sw_dcs_panel">
        <div class="text-center text-muted" style="padding:40px">
            <i class="fa fa-calculator" style="font-size:28px;display:block;margin-bottom:10px"></i>
            @lang('sw::lang.choose_a_shift_to_see')
        </div>
    </div>

</section>

@push('css')
<style>
.sw-cs-line {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; border-bottom: 1px solid #eef1f6;
    cursor: pointer;
}
.sw-cs-line:hover { background: #f7f9fc; }
.sw-cs-line .sw-cs-sign { width: 18px; font-weight: 700; color: #8a94a6; font-size: 16px; }
.sw-cs-line .sw-cs-label { flex: 1; font-size: 15px; }
.sw-cs-line .sw-cs-count { color: #8a94a6; font-size: 12px; }
.sw-cs-line .sw-cs-amount { font-size: 16px; font-weight: 600; min-width: 130px; text-align: right; }
.sw-cs-line .sw-cs-caret { width: 16px; color: #8a94a6; }

.sw-cs-total {
    display: flex; align-items: center; gap: 12px;
    padding: 16px; background: #eef5ff; border-top: 2px solid #7ea6dd;
}
.sw-cs-total .sw-cs-label { flex: 1; font-size: 17px; font-weight: 700; }
.sw-cs-total .sw-cs-amount { font-size: 20px; font-weight: 700; min-width: 130px; text-align: right; }
.sw-cs-negative { color: #b94a48; }

.sw-cs-detail { background: #fbfcfe; border-bottom: 1px solid #eef1f6; }
.sw-cs-detail table { margin: 0; font-size: 13px; }
.sw-cs-detail .sw-cs-empty { padding: 14px 16px 14px 46px; color: #8a94a6; font-size: 13px; }
</style>
@endpush

@push('javascript')
<script>
$(function () {

    function swDcsLoadShifts() {
        var $shift = $('#sw_dcs_shift');
        var locationId = $('#sw_dcs_location').val();

        $shift.prop('disabled', true)
              .html('<option value="">{{ __('messages.please_select') }}</option>');
        $('#sw_dcs_panel').html('');

        if (!locationId) {
            $shift.prop('disabled', false)
                  .html('<option value="">{{ __('sw::lang.choose_a_location_first') }}</option>');
            return;
        }

        $.get('{{ route('sw.cash-status.shifts', [], false) }}', { location_id: locationId }, function (rows) {
            var html = '<option value="">{{ __('messages.please_select') }}</option>';

            if (!rows.length) {
                html = '<option value="">{{ __('sw::lang.no_open_shift_at_location') }}</option>';
            } else {
                $.each(rows, function (i, r) {
                    html += '<option value="' + r.id + '">'
                          + $('<div>').text(r.label).html() + '</option>';
                });
            }

            $shift.html(html).prop('disabled', false);
        }).fail(function () {
            $shift.prop('disabled', false)
                  .html('<option value="">{{ __('sw::lang.could_not_load_status') }}</option>');
        });
    }

    function swDcsLoadPanel() {
        var shiftId = $('#sw_dcs_shift').val();
        var $panel = $('#sw_dcs_panel');

        if (!shiftId) {
            $panel.html('<div class="text-center text-muted" style="padding:40px">'
                + '{{ __('sw::lang.choose_a_shift_to_see') }}</div>');
            return;
        }

        $panel.html('<div class="text-center text-muted" style="padding:40px">'
            + '<i class="fa fa-spinner fa-spin"></i> {{ __('sw::lang.loading') }}</div>');

        $.get('{{ route('sw.cash-status.show', [], false) }}', { sw_shift_id: shiftId }, function (html) {
            $panel.html(html);
        }).fail(function () {
            $panel.html('<div class="alert alert-danger">'
                + '{{ __('sw::lang.could_not_load_status') }}</div>');
        });
    }

    $('#sw_dcs_location').on('change', swDcsLoadShifts);
    $('#sw_dcs_shift').on('change', swDcsLoadPanel);

    // Each line expands to the entries behind it. A total on its own says how
    // much; the entries say what, which is what anyone reconciling needs.
    $(document).on('click', '.sw-cs-line', function () {
        var key = $(this).data('section');
        var $detail = $('#sw_cs_detail_' + key);

        $detail.slideToggle(120);
        $(this).find('.sw-cs-caret i')
               .toggleClass('fa-chevron-right fa-chevron-down');
    });

    // A checkbox must not also expand the section under it.
    $(document).on('click', '.sw-cs-print-check', function (e) {
        e.stopPropagation();
    });

    swDcsLoadShifts();

});
</script>
@endpush
