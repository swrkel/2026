
{{-- S389 / IS1624: Direct Settlement payment tabs fixed to render one payment section only and preserve added rows without refresh. --}}
<style id="s383-petro-white-button-text-fix">
/* S383: Settlement/Add Payment buttons - default text must be white in all Petro modules. */
.settlement_tabs .nav-tabs > li > a,
.settlement_tabs .nav-tabs > li > a span,
.payment_tabs .nav-tabs > li > a,
.payment_tabs .nav-tabs > li > a span,
.payment_tabs .btn,
.payment_tabs .btn *,
.settlement_tabs .btn,
.settlement_tabs .btn *,
#settlement_save_btn,
#settlement_save_btn *,
#payment_review_btn,
#payment_review_btn *,
.btn_meter_sale_cancel,
.btn_meter_sale_cancel *,
.btn_update_meter_sale,
.btn_update_meter_sale * {
    color: #ffffff !important;
}

/* Only disabled/default grey buttons may keep their normal contrast. */
.settlement_tabs .btn-default,
.settlement_tabs .btn-default *,
.payment_tabs .btn-default,
.payment_tabs .btn-default * {
    color: #333333 !important;
}

/* Keep selected tab readable only where the tab itself intentionally becomes white. */
.settlement_tabs .nav-tabs > li.active > a,
.settlement_tabs .nav-tabs > li.active > a span,
.payment_tabs .nav-tabs > li.active > a,
.payment_tabs .nav-tabs > li.active > a span,
.settlement_tabs .nav-tabs > li > a.active,
.settlement_tabs .nav-tabs > li > a.active span,
.payment_tabs .nav-tabs > li > a.active,
.payment_tabs .nav-tabs > li > a.active span {
    color: #000000 !important;
}
</style>


<style>
/* V2 REUPLOAD: force all settlement/add-payment action buttons to use white font color. */
.btn,
.btn:link,
.btn:visited,
.btn:hover,
.btn:focus,
.btn:active,
button.btn,
a.btn,
input.btn,
.btn span,
.btn i,
.payment_tabs .btn,
.payment_tabs .btn *,
.settlement-tab-button,
.settlement-tab-button *,
.nav-tabs > li > a.btn,
.nav-pills > li > a.btn {
    color: #ffffff !important;
}
.btn-default,
.btn-default:link,
.btn-default:visited,
.btn-default:hover,
.btn-default:focus,
.btn-default:active {
    color: #ffffff !important;
}
</style>
@php
    $is_real_time_shift = false;
    $business_id = session()->get('user.business_id');

    if (!empty($settlement)) {
        $work_shifts = $settlement->work_shift;
        $settlement_work_shift_ids = [];

        if (!empty($work_shifts)) {
            if (is_string($work_shifts)) {
                $decoded = json_decode($work_shifts, true);
                $settlement_work_shift_ids = is_array($decoded) ? $decoded : explode(',', $work_shifts);
            } elseif (is_array($work_shifts)) {
                $settlement_work_shift_ids = $work_shifts;
            }

            $settlement_work_shift_ids = array_values(array_unique(array_filter(array_map('intval', $settlement_work_shift_ids))));
        }

        if (!empty($settlement_work_shift_ids)) {
            $is_real_time_shift = \Modules\PetroDirect\Entities\PumpOperatorPayment::where('business_id', $business_id)
                ->whereIn('shift_id', $settlement_work_shift_ids)
                ->exists();
        }
    }

    $s271_payment_tabs = [
        [
            'id' => 'cash_tab',
            'class' => 'cash_tab',
            'icon' => 'fa-money',
            'label' => empty($package_details['rename_cash_tab']) ? __('petrodirect::lang.cash') : __('petrodirect::lang.sale_amount'),
            'block_key' => 'ns_cash',
            'include' => 'petrodirect::settlement.partials.payment_tabs.cash',
        ],
        [
            'id' => 'cash_deposit_tab',
            'class' => 'cash_deposit_tab',
            'icon' => 'fa-credit-card',
            'label' => __('petrodirect::lang.cash_deposit'),
            'block_key' => 'ns_cash_deposit',
            'include' => 'petrodirect::settlement.partials.payment_tabs.cash_deposit',
        ],
        [
            'id' => 'cards_tab',
            'class' => 'cards_tab',
            'icon' => 'fa-credit-card',
            'label' => __('petrodirect::lang.cards'),
            'block_key' => 'ns_cards',
            'include' => 'petrodirect::settlement.partials.payment_tabs.cards',
        ],
        [
            'id' => 'cheques_tab',
            'class' => 'cheques_tab',
            'icon' => 'fa-pencil',
            'label' => __('petrodirect::lang.cheques'),
            'block_key' => 'ns_cheques',
            'include' => 'petrodirect::settlement.partials.payment_tabs.cheques',
        ],
        [
            'id' => 'expense_tab',
            'class' => 'expense_tab',
            'icon' => 'fa-bell-o',
            'label' => __('petrodirect::lang.expneses'),
            'block_key' => 'ns_expenses',
            'include' => 'petrodirect::settlement.partials.payment_tabs.expense',
        ],
        [
            'id' => 'shortage_tab',
            'class' => 'shortage_tab',
            'icon' => 'fa-thermometer-o',
            'label' => __('petrodirect::lang.shortage'),
            'block_key' => 'ns_shortage',
            'include' => 'petrodirect::settlement.partials.payment_tabs.shortage',
        ],
        [
            'id' => 'excess_tab',
            'class' => 'excess_tab',
            'icon' => 'fa-thermometer-full',
            'label' => __('petrodirect::lang.excess'),
            'block_key' => 'ns_excess',
            'include' => 'petrodirect::settlement.partials.payment_tabs.excess',
        ],
        [
            'id' => 'credit_sales_tab',
            'class' => 'credit_sales_tab',
            'icon' => 'fa-credit-card-alt',
            'label' => __('petrodirect::lang.credit_sales'),
            'block_key' => 'ns_credit_sales',
            'include' => 'petrodirect::settlement.partials.payment_tabs.credit_sales',
        ],
        [
            'id' => 'loan_payments_tab',
            'class' => 'loan_payments_tab',
            'icon' => 'fa-credit-card-alt',
            'label' => __('petrodirect::lang.loan_payments'),
            'block_key' => 'ns_loan_payments',
            'include' => 'petrodirect::settlement.partials.payment_tabs.loan_payments',
        ],
        [
            'id' => 'drawing_payments_tab',
            'class' => 'drawing_payments_tab',
            'icon' => 'fa-credit-card-alt',
            'label' => __('petrodirect::lang.drawing_payments'),
            'block_key' => 'ns_drawing_payments',
            'include' => 'petrodirect::settlement.partials.payment_tabs.owners_drawings',
        ],
        [
            'id' => 'settlement_customer_loans',
            'class' => 'settlement_customer_loans_tab',
            'icon' => 'fa-credit-card-alt',
            'label' => __('petrodirect::lang.customer_loans'),
            'block_key' => 'ns_customer_loans',
            'include' => 'petrodirect::settlement.partials.payment_tabs.customer_loans',
        ],
    ];

    $active_tab_id = 'cash_tab';
@endphp

<style>
    .notice_card {
        color: {{$font_color}} !important;
        font-family: {!! $font_family !!} !important;
        background-color: {{$background_color}} !important;
        font-size: {{$font_size}}px !important;
    }

    .s271-direct-payment-tabs > .nav-tabs {
        display: block !important;
        visibility: visible !important;
        margin-bottom: 15px;
        border-bottom: 1px solid #ddd;
    }

    .s271-direct-payment-tabs > .nav-tabs > li {
        display: inline-block !important;
        float: none !important;
        margin-bottom: 8px;
        pointer-events: auto !important;
    }

    /* LA-1124: these are module-owned controls, not generic Bootstrap/system tabs.
       Keeping them as real buttons prevents global permission/tab listeners from
       cancelling the Direct Settlement Add Payment navigation. */
    .s271-direct-payment-tabs .s271-payment-tab-button {
        display: block;
        padding: 10px 15px;
        border: 0;
        border-radius: 0;
        background: transparent;
        color: inherit;
        cursor: pointer;
        font: inherit;
        line-height: 1.42857143;
        pointer-events: auto !important;
        white-space: nowrap;
    }

    .s271-direct-payment-tabs > .nav-tabs > li.active > .s271-payment-tab-button {
        color: #000000 !important;
        background-color: #ffffff !important;
        border: 1px solid #dddddd;
        border-bottom-color: transparent;
    }

    .s271-direct-payment-tabs > .nav-tabs > li.disabled > .s271-payment-tab-button {
        cursor: not-allowed;
        opacity: .55;
    }

    .s271-hidden-template-bank {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        overflow: hidden !important;
    }

    .s271-active-payment-content {
        display: block !important;
        visibility: visible !important;
        min-height: 120px;
        clear: both;
    }

    /*
     * IS-PD-LAYOUT: contain the floats of the CLONED tab content.
     *
     * This container is what the user actually sees: s271SyncActivePaymentTabTemplate()
     * clones the active tab's markup out of the hidden #<tab>_tab template and renders
     * it here. Ten of the twelve payment partials open with a bare
     * <div class="col-md-12">, and Bootstrap 3 floats every .col-*.
     *
     * "clear: both" above does NOT help with this: it pushes this box below floats that
     * PRECEDE it, but it does not enclose floats INSIDE it. So the cloned body was out
     * of flow, this container collapsed to its min-height of 120px, and the payment
     * entry fields overflowed across the Payment due total and the Payment To Finalize
     * button below - on every tab, which is exactly the reported symptom.
     *
     * The ::after below is the standard clearfix and makes this box grow to fit the
     * cloned content.
     */
    .s271-active-payment-content::after {
        content: " ";
        display: table;
        clear: both;
    }

    /* Keep Select2 dropdowns inside the Add Payment modal and prevent repeated
       initialisation from leaving orphan customer dropdown boxes on inactive tabs. */
    .add_payment .select2-container {
        max-width: 100% !important;
    }

    .add_payment .select2-container--open {
        z-index: 1065 !important;
    }


    /* IS1607-002 Select2 modal positioning: keep customer/product dropdowns attached
       to the active Add Payment modal/form group, so they open below the clicked field. */
    .add_payment .select2-container { width: 100% !important; max-width: 100% !important; }
    .add_payment .select2-dropdown { z-index: 100000 !important; }
    .add_payment .select2-container--open { z-index: 100000 !important; }


    /* IS1624: keep inactive payment sections out of the visible page and stop
       their controls from interfering with the active payment entry section. */
    .s271-hidden-template-bank,
    .s271-hidden-template-bank * {
        display: none !important;
        visibility: hidden !important;
    }
    .s271-active-payment-content .s271-rendered-tab {
        display: block !important;
        visibility: visible !important;
    }
</style>

<div class="row">
    <div class="col-sm-12">
        <div class="settlement_tabs s271-direct-payment-tabs">
            <ul class="nav nav-tabs" role="tablist">
                @foreach($s271_payment_tabs as $tab)
                    <li role="presentation" class="{{ $tab['id'] === $active_tab_id ? 'active' : '' }}">
                        <button type="button"
                           class="s271-payment-tab-button {{ $tab['class'] }}"
                           aria-controls="{{ $tab['id'] }}"
                           aria-pressed="{{ $tab['id'] === $active_tab_id ? 'true' : 'false' }}"
                           data-petrodirect-payment-tab="{{ $tab['id'] }}"
                           onclick="return window.petroDirectOpenPaymentTab(this, event);">
                            <i class="fa {{ $tab['icon'] }}"></i>
                            <strong>{{ $tab['label'] }}</strong>
                        </button>
                    </li>
                @endforeach
            </ul>

            {{-- Render every payment section once. Bootstrap hides inactive panes.
                 This avoids duplicate element IDs and prevents hidden template copies
                 from receiving values or AJAX rows instead of the visible tab. --}}
            <div class="tab-content s1667-payment-tab-content">
                @foreach($s271_payment_tabs as $tab)
                    @php $class = ""; @endphp
                    @if(!empty($package_details[$tab['block_key']]))
                        @php $class = "hidden"; @endphp
                    @endif
                    <div role="tabpanel"
                         class="tab-pane fade {{ $tab['id'] === $active_tab_id ? 'in active' : '' }}"
                         id="{{ $tab['id'] }}">
                        @if(!empty($package_details[$tab['block_key']]))
                            <div class="notice_card card text-center">{{ $message }}</div>
                        @endif
                        {{--
                            IS-PD-LAYOUT: "clearfix" is load-bearing - do not remove.

                            Ten of the twelve payment partials (shortage, excess, cash,
                            cards, cheques, cash_deposit, customer_loans, loan_payments,
                            owners_drawings, pos_sales) open with a bare
                            <div class="col-md-12">. Bootstrap 3 FLOATS every .col-*,
                            and a floated child contributes no height to a parent that
                            is not a clearfix.

                            This wrapper was a plain <div>, so those blocks were
                            invisible to the pane's height calculation. Measured on the
                            Shortage tab: the pane computed to 216.469px while its own
                            content row was 273px tall, so ~57px of the Amount / Payment
                            Note / Add panel escaped the pane (.tab-pane is
                            overflow: visible) and painted straight over the Payment due
                            total and the Payment To Finalize button below it.

                            Adding Bootstrap's .clearfix makes the wrapper enclose its
                            floats, so the pane grows to fit its content and nothing
                            overlaps. Fixing it here covers every tab at once instead of
                            patching ten partials, and leaves the two that already wrap
                            correctly (credit_sales, expense) unaffected - .clearfix is
                            a no-op for non-floated content.

                            $class is "hidden" for package-locked tabs; .hidden is
                            display:none !important, so the combination is harmless.
                        --}}
                        <div class="clearfix {{ $class }}">
                            @include($tab['include'])
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style>
    /* IS1667: one active payment form only; inactive forms must never be displayed
       as a vertical list or participate in layout. */
    .s1667-payment-tab-content > .tab-pane { display: none !important; }
    .s1667-payment-tab-content > .tab-pane.active { display: block !important; }
</style>


<script type="text/x-disabled" id="is1669-direct-payment-tab-runtime-fix-disabled-by-is1861">
(function ($) {
    'use strict';

    var selector = '.s271-direct-payment-tabs';

    function activate($wrap, target) {
        if (!$wrap.length || !target) { return; }
        var $links = $wrap.children('.nav-tabs').find('a[data-toggle="tab"]');
        var $panes = $wrap.find('> .tab-content > .tab-pane');
        var $link = $links.filter('[href="' + target + '"]').first();
        var $pane = $wrap.find('> .tab-content > ' + target).first();
        if (!$link.length || !$pane.length) { return; }

        $links.parent('li').removeClass('active');
        $link.parent('li').addClass('active');
        $panes.removeClass('active in').attr('aria-hidden', 'true').hide();
        $pane.addClass('active in').attr('aria-hidden', 'false').show();
        $wrap.data('is1669-active-payment-tab', target);

        if ($.fn.dataTable) {
            $pane.find('table.dataTable').each(function () {
                try { $(this).DataTable().columns.adjust(); } catch (e) {}
            });
        }
    }

    // Public, safe replacement for an older helper that was referenced but not defined.
    window.syncActivePaymentRows = function ($wrap) {
        $wrap = $wrap && $wrap.jquery ? $wrap : $(selector).first();
        var target = $wrap.data('is1669-active-payment-tab') ||
            $wrap.children('.nav-tabs').find('li.active a[data-toggle="tab"]').attr('href') || '#cash_tab';
        activate($wrap, target);
    };

    $(document)
        .off('click.is1669DirectPaymentTabs', selector + ' > .nav-tabs a[data-toggle="tab"]')
        .on('click.is1669DirectPaymentTabs', selector + ' > .nav-tabs a[data-toggle="tab"]', function (event) {
            event.preventDefault();
            var $link = $(this);
            var $wrap = $link.closest(selector);
            activate($wrap, $link.attr('href'));
            try { $link.tab('show'); } catch (e) {}
        })
        .off('shown.bs.modal.is1669DirectPaymentTabs', '.add_payment')
        .on('shown.bs.modal.is1669DirectPaymentTabs', '.add_payment', function () {
            $(this).find(selector).each(function () { window.syncActivePaymentRows($(this)); });
        });

    // Keep the same payment section visible after an Add AJAX call. Existing handlers
    // prepend the saved row to the visible table; this prevents a redraw from exposing
    // every section or switching the operator back to Cash.
    $(document).ajaxComplete(function () {
        $('.add_payment:visible').find(selector).each(function () {
            var $wrap = $(this);
            setTimeout(function () { window.syncActivePaymentRows($wrap); }, 0);
        });
    });
})(jQuery);
</script>

{{-- The stable Create/Edit page loads payment_tab_controller before this AJAX partial. --}}

<script type="text/x-disabled" id="is1667-payment-tab-widget-controller-disabled-by-is1861">
(function () {
    function initActivePaymentWidgets($pane) {
        if (!$pane || !$pane.length) { return; }
        if ($.fn.select2) {
            $pane.find('select.select2, select.form-control').filter(':visible').each(function () {
                var $select = $(this);
                try {
                    if ($select.data('select2')) { $select.select2('destroy'); }
                    $select.next('.select2-container').remove();
                    var options = { width: '100%' };
                    var $modal = $select.closest('.modal');
                    if ($modal.length) { options.dropdownParent = $modal; }
                    $select.select2(options);
                } catch (e) {}
            });
        }
    }

    $(document)
        .off('shown.bs.tab.is1667PaymentTabs', '.s271-direct-payment-tabs a[data-toggle="tab"]')
        .on('shown.bs.tab.is1667PaymentTabs', '.s271-direct-payment-tabs a[data-toggle="tab"]', function (e) {
            var target = $(e.target).attr('href');
            initActivePaymentWidgets($(target));
            if (typeof window.__petroRecalculatePaymentTotals === 'function') {
                window.__petroRecalculatePaymentTotals();
            }
        });

    $(document).on('shown.bs.modal.is1667PaymentTabs', '.add_payment', function () {
        var $first = $(this).find('.s271-direct-payment-tabs > .nav-tabs > li.active > a').first();
        if (!$first.length) {
            $first = $(this).find('.s271-direct-payment-tabs > .nav-tabs > li:first > a').first();
            $first.tab('show');
        }
        initActivePaymentWidgets($(this).find('.s1667-payment-tab-content > .tab-pane.active'));
    });
})();
</script>

<script>
(function () {
    window.__is1607InitAddPaymentWidgets = function (context) {
        var $scope = context ? $(context) : $('.add_payment');
        if (!$scope.length) { $scope = $('.modal:visible').last(); }

        if ($.fn.select2) {
            $scope.find('select.select2, select.form-control').each(function () {
                var $select = $(this);
                if (!$select.is(':visible')) { return; }
                try {
                    if ($select.data('select2')) { $select.select2('destroy'); }
                    $select.next('.select2-container').remove();
                    $select.siblings('.select2-container').remove();
                    var $parent = $select.closest('.form-group');
                    if (!$parent.length) { $parent = $select.closest('.modal'); }
                    $select.select2({ width: '100%', dropdownParent: $parent.length ? $parent : $(document.body) });
                } catch (e) {}
            });
        }
    };

    $(document)
        .off('shown.bs.modal.is1607addpayment')
        .on('shown.bs.modal.is1607addpayment', '.add_payment, .modal', function () {
            var modal = this;
            setTimeout(function () { window.__is1607InitAddPaymentWidgets(modal); }, 150);
        })
        .off('shown.bs.tab.is1607addpayment')
        .on('shown.bs.tab.is1607addpayment', '.add_payment [data-petrodirect-payment-tab]', function () {
            var pane = $('#' + $(this).attr('data-petrodirect-payment-tab'));
            // LA-1150: see the note on ajaxComplete below - re-initialising
            // widgets replaces the DOM and would kill an open dropdown.
            setTimeout(function () {
                if ($('.select2-container--open').length) { return; }
                window.__is1607InitAddPaymentWidgets(pane);
            }, 100);
        });

    // After any Add button AJAX succeeds, re-initialise widgets and refresh totals in the currently visible tab.
    $(document).ajaxComplete(function (event, xhr, settings) {
        if (!$('.add_payment:visible').length) { return; }

        /*
         * LA-1150: never re-render while a dropdown is open.
         *
         * ajaxComplete fires after EVERY ajax call on the page, not just the
         * Add button - including select2's own option loading and any
         * background request. 250ms later this re-initialises the widgets and
         * calls s271SyncActivePaymentTabTemplate(), which REPLACES the tab's
         * DOM. The <select> the user had just opened is swapped out and its
         * dropdown goes with it, so the list closed a moment after appearing
         * and nothing could be picked - and a selection already made was lost.
         *
         * If a dropdown is open the user is mid-interaction, so the refresh is
         * skipped. It is checked twice: once now, and again inside the timeout,
         * because the dropdown may be opened during those 250ms.
         *
         * Nothing is lost by skipping - the next ajaxComplete (the Add post
         * itself) refreshes the tab once the dropdown has closed.
         */
        if ($('.select2-container--open').length) { return; }

        setTimeout(function () {
            if ($('.select2-container--open').length) { return; }

            window.__is1607InitAddPaymentWidgets($('.add_payment:visible'));

            /*
             * LA-1150: re-assert the modal body's scroll after a re-render.
             *
             * s271SyncActivePaymentTabTemplate() replaces the tab's DOM, and the
             * replacement can leave the scroll container in a state where it no
             * longer scrolls - the user is stranded wherever they happened to be.
             * These are the same values set inline on .modal-body; re-applying
             * them costs nothing when they are already correct.
             */
            $('.add_payment:visible').find('.modal-body').each(function () {
                this.style.setProperty('overflow-y', 'auto', 'important');
                this.style.setProperty('overflow-x', 'hidden', 'important');
                this.style.setProperty('max-height', 'calc(100vh - 150px)', 'important');
            });
            if (typeof window.__petroRecalculatePaymentTotals === 'function') {
                window.__petroRecalculatePaymentTotals();
            }
            var $wrap = $('.add_payment:visible').find('.s271-direct-payment-tabs').first();
            if ($wrap.length && typeof window.syncActivePaymentRows === 'function') {
                window.syncActivePaymentRows($wrap);
            } else if (typeof window.s271SyncActivePaymentTabTemplate === 'function') {
                window.s271SyncActivePaymentTabTemplate($('.add_payment:visible'));
            }
        }, 250);
    });
})();
</script>

@if($is_real_time_shift)
<script>
    (function() {
        var lockFields = function() {
            $('.s271-direct-payment-tabs').find('#s271_active_payment_content').find('input, select, textarea, button').prop('disabled', true);
            $('.s271-direct-payment-tabs').find('#s271_active_payment_content').find('select').each(function() {
                if ($(this).data('select2')) {
                    $(this).trigger('change');
                }
            });
        };

        lockFields();
        setTimeout(lockFields, 500);
        setTimeout(lockFields, 1000);

        $(document).on('shown.bs.modal', '.add_payment', function() {
            lockFields();
        });
    })();
</script>
@endif

<style id="s385-force-settlement-button-text-white">
/* S385: focused fix requested by user.
   Settlement/Add Payment button text must be WHITE on Add Settlement and Add Payment pages.
   No functional logic changed. */
.settlement_tabs .btn,
.settlement_tabs .btn:link,
.settlement_tabs .btn:visited,
.settlement_tabs .btn:hover,
.settlement_tabs .btn:focus,
.settlement_tabs .btn:active,
.settlement_tabs .btn.active,
.settlement_tabs .btn.selected,
.settlement_tabs .btn.is-active,
.settlement_tabs .btn *,
.payment_tabs .btn,
.payment_tabs .btn:link,
.payment_tabs .btn:visited,
.payment_tabs .btn:hover,
.payment_tabs .btn:focus,
.payment_tabs .btn:active,
.payment_tabs .btn.active,
.payment_tabs .btn.selected,
.payment_tabs .btn.is-active,
.payment_tabs .btn *,
#settlement_form .btn,
#settlement_form .btn:link,
#settlement_form .btn:visited,
#settlement_form .btn:hover,
#settlement_form .btn:focus,
#settlement_form .btn:active,
#settlement_form .btn.active,
#settlement_form .btn.selected,
#settlement_form .btn.is-active,
#settlement_form .btn *,
#settlement_save_btn,
#settlement_save_btn:link,
#settlement_save_btn:visited,
#settlement_save_btn:hover,
#settlement_save_btn:focus,
#settlement_save_btn:active,
#settlement_save_btn.active,
#settlement_save_btn *,
#payment_review_btn,
#payment_review_btn:link,
#payment_review_btn:visited,
#payment_review_btn:hover,
#payment_review_btn:focus,
#payment_review_btn:active,
#payment_review_btn.active,
#payment_review_btn *,
.btn_meter_sale_cancel,
.btn_meter_sale_cancel *,
.btn_update_meter_sale,
.btn_update_meter_sale *,
.btn-modal.btn,
.btn-modal.btn *,
button.btn,
button.btn *,
a.btn,
a.btn *,
input.btn {
    color: #ffffff !important;
}
</style>


<style id="s429-payment-entry-single-section-fix">
/* S429: only the selected payment entry section is visible. */
.s271-direct-payment-tabs > .s1667-payment-tab-content > .tab-pane {
    display: none !important;
    visibility: hidden !important;
    height: 0 !important;
    overflow: hidden !important;
}
.s271-direct-payment-tabs > .s1667-payment-tab-content > .tab-pane.active {
    display: block !important;
    visibility: visible !important;
    height: auto !important;
    overflow: visible !important;
}
/*
 * IS-PD-LAYOUT: make the active pane itself a float container.
 *
 * height:auto above only grows the pane to fit content that is IN FLOW.
 * Bootstrap 3 floats every .col-*, and ten of the twelve payment partials open
 * with a bare <div class="col-md-12">, so their entire body was out of flow and
 * contributed zero height. Measured on Shortage: pane 216.469px vs content
 * 273px - the missing 57px overflowed (overflow:visible above) straight across
 * the Payment due total and the Payment To Finalize button.
 *
 * This ::after is the standard clearfix. Applying it to the pane fixes every
 * tab at once and is independent of how each partial nests its columns, so it
 * keeps working even if a partial is restructured later.
 */
.s271-direct-payment-tabs > .s1667-payment-tab-content > .tab-pane.active::after {
    content: " ";
    display: table;
    clear: both;
}
</style>
<script type="text/x-disabled" id="s429-payment-tabs-final-guard-disabled-by-is1861">
(function ($) {
    'use strict';
    var wrap = '.s271-direct-payment-tabs';
    function activate($wrap, target) {
        var $links = $wrap.children('.nav-tabs').find('a[data-toggle="tab"]');
        var $panes = $wrap.children('.s1667-payment-tab-content').children('.tab-pane');
        var $link = $links.filter('[href="' + target + '"]').first();
        var $pane = $panes.filter(target).first();
        if (!$link.length || !$pane.length) { return; }
        $links.closest('li').removeClass('active');
        $link.closest('li').addClass('active');
        $panes.removeClass('active in show').attr('aria-hidden', 'true');
        $pane.addClass('active in show').attr('aria-hidden', 'false');
    }
    $(document)
      .off('click.s429PaymentTabs', wrap + ' > .nav-tabs a[data-toggle="tab"]')
      .on('click.s429PaymentTabs', wrap + ' > .nav-tabs a[data-toggle="tab"]', function (e) {
          e.preventDefault();
          e.stopImmediatePropagation();
          var $link = $(this), $wrap = $link.closest(wrap);
          $wrap.data('s429-active', $link.attr('href'));
          activate($wrap, $link.attr('href'));
      })
      .off('shown.bs.modal.s429PaymentTabs', '.add_payment')
      .on('shown.bs.modal.s429PaymentTabs', '.add_payment', function () {
          $(this).find(wrap).each(function () {
              var $wrap = $(this);
              var target = $wrap.data('s429-active') || $wrap.children('.nav-tabs').find('li.active a').attr('href') || '#cash_tab';
              activate($wrap, target);
          });
      });
})(jQuery);
</script>

@include('petrodirect::partials.global_tab_standard')
