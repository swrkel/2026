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
            $is_real_time_shift = \Modules\PetroGeneral\Entities\PumpOperatorPayment::where('business_id', $business_id)
                ->whereIn('shift_id', $settlement_work_shift_ids)
                ->exists();
        }
    }

    $s271_payment_tabs = [
        [
            'id' => 'cash_tab',
            'class' => 'cash_tab',
            'icon' => 'fa-money',
            'label' => empty($package_details['rename_cash_tab']) ? __('petrogeneral::lang.cash') : __('petrogeneral::lang.sale_amount'),
            'block_key' => 'ns_cash',
            'include' => 'petrogeneral::settlement.partials.payment_tabs.cash',
        ],
        [
            'id' => 'cash_deposit_tab',
            'class' => 'cash_deposit_tab',
            'icon' => 'fa-credit-card',
            'label' => __('petrogeneral::lang.cash_deposit'),
            'block_key' => 'ns_cash_deposit',
            'include' => 'petrogeneral::settlement.partials.payment_tabs.cash_deposit',
        ],
        [
            'id' => 'cards_tab',
            'class' => 'cards_tab',
            'icon' => 'fa-credit-card',
            'label' => __('petrogeneral::lang.cards'),
            'block_key' => 'ns_cards',
            'include' => 'petrogeneral::settlement.partials.payment_tabs.cards',
        ],
        [
            'id' => 'cheques_tab',
            'class' => 'cheques_tab',
            'icon' => 'fa-pencil',
            'label' => __('petrogeneral::lang.cheques'),
            'block_key' => 'ns_cheques',
            'include' => 'petrogeneral::settlement.partials.payment_tabs.cheques',
        ],
        [
            'id' => 'expense_tab',
            'class' => 'expense_tab',
            'icon' => 'fa-bell-o',
            'label' => __('petrogeneral::lang.expneses'),
            'block_key' => 'ns_expenses',
            'include' => 'petrogeneral::settlement.partials.payment_tabs.expense',
        ],
        [
            'id' => 'shortage_tab',
            'class' => 'shortage_tab',
            'icon' => 'fa-thermometer-o',
            'label' => __('petrogeneral::lang.shortage'),
            'block_key' => 'ns_shortage',
            'include' => 'petrogeneral::settlement.partials.payment_tabs.shortage',
        ],
        [
            'id' => 'excess_tab',
            'class' => 'excess_tab',
            'icon' => 'fa-thermometer-full',
            'label' => __('petrogeneral::lang.excess'),
            'block_key' => 'ns_excess',
            'include' => 'petrogeneral::settlement.partials.payment_tabs.excess',
        ],
        [
            'id' => 'credit_sales_tab',
            'class' => 'credit_sales_tab',
            'icon' => 'fa-credit-card-alt',
            'label' => __('petrogeneral::lang.credit_sales'),
            'block_key' => 'ns_credit_sales',
            'include' => 'petrogeneral::settlement.partials.payment_tabs.credit_sales',
        ],
        [
            'id' => 'loan_payments_tab',
            'class' => 'loan_payments_tab',
            'icon' => 'fa-credit-card-alt',
            'label' => __('petrogeneral::lang.loan_payments'),
            'block_key' => 'ns_loan_payments',
            'include' => 'petrogeneral::settlement.partials.payment_tabs.loan_payments',
        ],
        [
            'id' => 'drawing_payments_tab',
            'class' => 'drawing_payments_tab',
            'icon' => 'fa-credit-card-alt',
            'label' => __('petrogeneral::lang.drawing_payments'),
            'block_key' => 'ns_drawing_payments',
            'include' => 'petrogeneral::settlement.partials.payment_tabs.owners_drawings',
        ],
        [
            'id' => 'settlement_customer_loans',
            'class' => 'settlement_customer_loans_tab',
            'icon' => 'fa-credit-card-alt',
            'label' => __('petrogeneral::lang.customer_loans'),
            'block_key' => 'ns_customer_loans',
            'include' => 'petrogeneral::settlement.partials.payment_tabs.customer_loans',
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
</style>

<div class="row">
    <div class="col-sm-12">
        <div class="settlement_tabs s271-direct-payment-tabs">
            <ul class="nav nav-tabs">
                @foreach($s271_payment_tabs as $tab)
                    <li class="{{ $tab['id'] === $active_tab_id ? 'active' : '' }}">
                        <a href="#{{ $tab['id'] }}" class="tabs {{ $tab['class'] }}" data-toggle="tab" data-s271-tab="{{ $tab['id'] }}">
                            <i class="fa {{ $tab['icon'] }}"></i>
                            <strong>{{ $tab['label'] }}</strong>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Only this one container is visible. It is replaced on tab click. --}}
            <div id="s271_active_payment_content" class="s271-active-payment-content">
                @foreach($s271_payment_tabs as $tab)
                    @if($tab['id'] === $active_tab_id)
                        <div class="s271-rendered-tab" data-s271-rendered="{{ $tab['id'] }}">
                            @php $class = ""; @endphp
                            @if(!empty($package_details[$tab['block_key']]))
                                @php $class = "hidden"; @endphp
                                <div class="notice_card card text-center">{{ $message }}</div>
                            @endif
                            <div class="{{ $class }}">
                                @include($tab['include'])
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Hidden source templates. They are not shown; only copied into the visible container. --}}
            <div class="s271-hidden-template-bank">
                @foreach($s271_payment_tabs as $tab)
                    <script type="text/template" id="s271_template_{{ $tab['id'] }}">
                        <div class="s271-rendered-tab" data-s271-rendered="{{ $tab['id'] }}">
                            @php $class = ""; @endphp
                            @if(!empty($package_details[$tab['block_key']]))
                                @php $class = "hidden"; @endphp
                                <div class="notice_card card text-center">{{ $message }}</div>
                            @endif
                            <div class="{{ $class }}">
                                @include($tab['include'])
                            </div>
                        </div>
                    </script>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function initWidgets($container) {
        if ($.fn.select2) {
            $container.find('.select2').each(function () {
                try {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                    $(this).select2({ width: '100%' });
                } catch (e) {}
            });
        }

        if ($.fn.iCheck) {
            $container.find('input[type="checkbox"].input-icheck, input[type="checkbox"].input-icheck-red').iCheck({
                checkboxClass: 'icheckbox_square-blue',
                radioClass: 'iradio_square-blue'
            });
        }
    }

    window.s271LoadOnlySelectedPaymentTab = function (context, target) {
        var $wrap = context ? $(context).closest('.s271-direct-payment-tabs') : $();

        if (!$wrap.length && context) {
            $wrap = $(context).find('.s271-direct-payment-tabs').first();
        }

        if (!$wrap.length) {
            $wrap = $('.s271-direct-payment-tabs').first();
        }

        if (!$wrap.length) {
            return;
        }

        if (!target || target.charAt(0) !== '#') {
            target = '#cash_tab';
        }

        var tabId = target.replace('#', '');
        var template = $('#s271_template_' + tabId).html();

        if (!template) {
            tabId = 'cash_tab';
            target = '#cash_tab';
            template = $('#s271_template_cash_tab').html();
        }

        $wrap.find('> .nav-tabs > li').removeClass('active');
        $wrap.find('> .nav-tabs > li > a[href="' + target + '"]').closest('li').addClass('active');

        var $content = $('#s271_active_payment_content');
        $content.html(template);

        initWidgets($content);

        if (typeof window.__petroRecalculatePaymentTotals === 'function') {
            window.__petroRecalculatePaymentTotals();
        }
    };

    $(document)
        .off('click.s271OneTabContent', '.s271-direct-payment-tabs > .nav-tabs > li > a[data-toggle="tab"]')
        .on('click.s271OneTabContent', '.s271-direct-payment-tabs > .nav-tabs > li > a[data-toggle="tab"]', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            window.s271LoadOnlySelectedPaymentTab(this, $(this).attr('href'));
            return false;
        });

    $(document).ready(function () {
        setTimeout(function () { window.s271LoadOnlySelectedPaymentTab(document, '#cash_tab'); }, 200);
    });

    $(document).on('shown.bs.modal.s271OneTabContent', '.add_payment, .modal', function () {
        var modal = this;
        setTimeout(function () { window.s271LoadOnlySelectedPaymentTab(modal, '#cash_tab'); }, 200);
    });
})();
</script>

@if($is_real_time_shift)
<script>
    (function() {
        var lockFields = function() {
            $('#s271_active_payment_content').find('input, select, textarea, button').prop('disabled', true);
            $('#s271_active_payment_content').find('select').each(function() {
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
