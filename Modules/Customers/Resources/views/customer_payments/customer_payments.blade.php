<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_payment_date_range', __('lang_v1.date_range').':') !!}
                    <input type="text" name="customer_payment_date_range" id="customer_payment_date_range" class="form-control" style="width: 100%;" readonly />
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <!--{!! Form::label('customer_payment_customer_id', __('lang_v1.customer').':') !!}-->
                    <!--{!! Form::select('customer_payment_customer_id', $customers, null, ['class' => 'form-control-->
                    <!--select2', 'style' => 'width: 100%;', 'required', 'placeholder' => __('lang_v1.all')]); !!}-->
                    {!! Form::label('customer_payment_customer_id', __('petro::lang.customer').':') !!}
                    {!! Form::select('customer_payment_customer_id', $customers, null, ['class' => 'form-control
                    select2', 'style' => 'width: 100%;', 'placeholder' => __('lang_v1.all')]); !!}
                    <!--{!! Form::label('customer_payment_bulk_customer_id', __('petro::lang.customer').':') !!}-->
                    <!--{!! Form::select('customer_payment_bulk_customer_id', $customers, null, ['class' => 'form-control-->
                    <!--select2', 'style' => 'width: 100%;', 'required', 'placeholder' => __('lang_v1.all')]); !!}-->
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_payment_location_id', __('lang_v1.location').'123:') !!}
                    {!! Form::select('customer_payment_location_id', $business_locations, null, ['class' => 'form-control
                    select2', 'style' => 'width: 100%;', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('shift_number', 'Shift Number') !!}
                    {!! Form::select('shift_number', $dailyCashShiftNumbers, null, ['class' => 'form-control
                    select2', 'style' => 'width: 100%;', 'placeholder' => 'Shift Number']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_payment_method', __('lang_v1.payment_method').':') !!}
                    {!! Form::select('customer_payment_method', $payment_types, null, ['class' => 'form-control
                    select2', 'style' => 'width: 100%;', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('paid_in_type', __('lang_v1.paid_in_type').':') !!}
                    {!! Form::select('paid_in_type', ['customer_bulk' => "Customer Bulk Page",'customer_page' => 'Customer Page', 'all_sale_page' => 'All Sale Page', 'settlement' => 'Settlement'],null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('lang_v1.all')]); !!}

                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_amount', __('lang_v1.amount').':') !!}
                    {!! Form::select('customer_amount', [], null, ['class' => 'form-control
                    select2', 'style' => 'width: 100%;', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_cheque_no', __('lang_v1.cheque_no').':') !!}
                    {!! Form::select('customer_cheque_no', [], null, ['class' => 'form-control
                    select2', 'style' => 'width: 100%;', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
        </div>
    </div>

    @endcomponent

    <br>
    <br>
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
            {{--
                LA-1185: the table is wrapped so it can scroll sideways.

                It had no .table-responsive at all. With fifteen columns it is
                wider than the box on a normal screen, so the right-hand columns
                (Cheque and beyond) were simply cut off with no way to reach them
                - the missing bottom scroll bar in the report.

                The overflow rules are set here rather than left to Bootstrap
                because the theme's .box carries overflow:hidden, which clips a
                child that overflows it. Giving THIS element its own scroll
                context means the table no longer overflows the box at all, so
                there is nothing left for the box to clip.
            --}}
            <div class="table-responsive customer-payments-scroll"
                 style="overflow-x: auto !important; overflow-y: visible !important; width: 100%; -webkit-overflow-scrolling: touch;">
            <table class="table table-bordered table-striped" id="customer_payments_table" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="notexport">@lang('messages.action')</th>
                        <th>@lang('lang_v1.date' )</th>
                        <th>@lang('lang_v1.system_date' )</th>
                        <th>@lang('lang_v1.location' )</th>
                        <th>@lang('lang_v1.payment_ref_no' )</th>
                        <th>@lang('lang_v1.customer' )</th>
                        <th>@lang('lang_v1.interest' )</th>
                        <th>@lang('lang_v1.amount' )</th>
                        <th>@lang('lang_v1.payment_method' )</th>
                        <th>@lang('lang_v1.paid_in_type' )</th>
                        <th>@lang('lang_v1.cheque_deposit_transfer_date')</th>
                        <th>@lang('contact.user')</th>
                        {{-- Modified by Engr. Alex -- task 7889 --}}
                        <th>@lang('lang_v1.note')</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                    <tr class="bg-gray font-17 text-center footer-total">
                        <td colspan="5"></td>
                        <td><strong>@lang('sale.total'):</strong></td>
                        <td><span class="display_currency" id="footer_interest" data-currency_symbol="true"></span></td>
                        <td><span class="display_currency" id="footer_total" data-currency_symbol="true"></span></td>
                        {{-- Modified by Engr. Alex -- task 7889 --}}
                        <td colspan="5"></td>
                    </tr>
                </tfoot>
            </table>
            </div>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->

{{--
    LA-1185: the Actions menu, and a scroll bar you can actually see.
--}}
<style>
    /*
     * An overlay scroll bar stays invisible until something is scrolled, so
     * there was no sign that more table existed to the right. This keeps the
     * track drawn.
     */
    .customer-payments-scroll { scrollbar-width: thin; padding-bottom: 2px; }

    .customer-payments-scroll::-webkit-scrollbar { height: 10px; -webkit-appearance: none; }
    .customer-payments-scroll::-webkit-scrollbar-track { background: #eef2f7; border-radius: 999px; }
    .customer-payments-scroll::-webkit-scrollbar-thumb { background: #b6c4d6; border-radius: 999px; }
    .customer-payments-scroll::-webkit-scrollbar-thumb:hover { background: #94a6bd; }

    /*
     * A menu that has been moved to <body> is positioned from JavaScript.
     * Fixed positioning takes it out of every scrolling and overflow context,
     * which is the whole point of moving it.
     */
    body > .customer-payments-action-menu-detached {
        position: fixed !important;
        z-index: 2147483000 !important;
        display: block !important;
        margin: 0 !important;
        max-height: 80vh !important;
        overflow-y: auto !important;
        min-width: 200px !important;
        background: #fff !important;
        border: 1px solid #dfe6e9 !important;
        border-radius: 10px !important;
        box-shadow: 0 12px 32px rgba(0, 0, 0, .22) !important;
        padding: 6px !important;
        list-style: none !important;
    }

    body > .customer-payments-action-menu-detached > li { display: block !important; float: none !important; }

    body > .customer-payments-action-menu-detached > li > a {
        display: block !important;
        padding: 8px 14px !important;
        border-radius: 8px !important;
        color: #34495e !important;
        white-space: nowrap !important;
        text-decoration: none !important;
    }

    body > .customer-payments-action-menu-detached > li > a:hover { background: #f4f7fb !important; }
</style>

<script>
(function ($) {
    'use strict';

    if (window.__customerPaymentsActionMenuLoaded) { return; }
    window.__customerPaymentsActionMenuLoaded = true;

    var DETACHED = 'customer-payments-action-menu-detached';
    var TABLE = '#customer_payments_table';

    /*
     * The menu is generated in full - it opened as an empty white box because it
     * was being CLIPPED, not because it was empty. It sits inside the box, which
     * the theme gives overflow:hidden, and inside the scroll wrapper added above.
     * Anything of it falling outside either is cut away.
     *
     * While it is open it is moved to <body> and positioned with position:fixed
     * against the button's own screen coordinates, so no ancestor can clip it,
     * then returned to its exact place the moment it closes - DataTables owns
     * that markup and rebuilds it on every draw.
     *
     * Same approach as the supplier payments menu and the customer statement
     * menu, so the three behave alike.
     */
    function restoreAll() {
        $('body').children('.' + DETACHED).each(function () {
            var $menu = $(this);
            var marker = $menu.data('cpMenuPlaceholder');

            $menu.removeClass(DETACHED).removeAttr('style');

            if (marker && marker.parentNode) {
                marker.parentNode.insertBefore($menu[0], marker);
                marker.parentNode.removeChild(marker);
            }

            $menu.removeData('cpMenuPlaceholder');
        });
    }

    function place($toggle, $menu) {
        var rect = $toggle[0].getBoundingClientRect();
        var w = $menu.outerWidth();
        var h = $menu.outerHeight();
        var margin = 8;
        var vh = window.innerHeight || document.documentElement.clientHeight;
        var vw = window.innerWidth || document.documentElement.clientWidth;
        var top;

        if (rect.bottom + 4 + h <= vh - margin) {
            top = rect.bottom + 4;
        } else if (rect.top - h - 4 >= margin) {
            top = rect.top - h - 4;
        } else {
            top = Math.max(margin, vh - h - margin);
        }

        var left = rect.left;

        if (left + w > vw - margin) { left = Math.max(margin, vw - w - margin); }
        if (left < margin) { left = margin; }

        $menu.css({ top: Math.round(top) + 'px', left: Math.round(left) + 'px' });
    }

    $(document)
        .off('.cpActionMenu')
        .on('shown.bs.dropdown.cpActionMenu', function (e) {
            var $group = $(e.target);

            if (!$group.closest(TABLE).length) { return; }

            restoreAll();

            var $toggle = $group.find('[data-toggle="dropdown"]').first();
            var $menu = $group.children('.dropdown-menu').first();

            if (!$toggle.length || !$menu.length || $menu.parent().is('body')) { return; }

            var marker = document.createComment('cp-menu');
            $menu[0].parentNode.insertBefore(marker, $menu[0]);
            $menu.data('cpMenuPlaceholder', marker);
            $menu.addClass(DETACHED).appendTo(document.body);

            place($toggle, $menu);

            // Measure again once the browser has laid it out, so the detached
            // class's own padding and min-width are included.
            var again = function () { place($toggle, $menu); };

            window.requestAnimationFrame ? window.requestAnimationFrame(again) : window.setTimeout(again, 0);
        })
        .on('hidden.bs.dropdown.cpActionMenu', function (e) {
            if ($(e.target).closest(TABLE).length) { restoreAll(); }
        });

    // A detached menu cannot scroll with a parent it no longer has, and
    // DataTables replaces every row on redraw.
    $(window).off('.cpActionMenu').on('scroll.cpActionMenu resize.cpActionMenu', function () {
        if ($('body').children('.' + DETACHED).length) {
            restoreAll();
            $(TABLE).find('.btn-group.open, .dropdown.open').removeClass('open');
        }
    });

    $(document).on('draw.dt.cpActionMenu destroy.dt.cpActionMenu', TABLE, restoreAll);
})(jQuery);
</script>
