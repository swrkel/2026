
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
            $is_real_time_shift = \Modules\Petro\Entities\PumpOperatorPayment::where('business_id', $business_id)
                ->whereIn('shift_id', $settlement_work_shift_ids)
                ->exists();
        }
    }

    $s271_payment_tabs = [
        [
            'id' => 'cash_tab',
            'class' => 'cash_tab',
            'icon' => 'fa-money',
            'label' => empty($package_details['rename_cash_tab']) ? __('petro::lang.cash') : __('petro::lang.sale_amount'),
            'block_key' => 'ns_cash',
            'include' => 'petro::settlement.partials.payment_tabs.cash',
        ],
        [
            'id' => 'cash_deposit_tab',
            'class' => 'cash_deposit_tab',
            'icon' => 'fa-credit-card',
            'label' => __('petro::lang.cash_deposit'),
            'block_key' => 'ns_cash_deposit',
            'include' => 'petro::settlement.partials.payment_tabs.cash_deposit',
        ],
        [
            'id' => 'cards_tab',
            'class' => 'cards_tab',
            'icon' => 'fa-credit-card',
            'label' => __('petro::lang.cards'),
            'block_key' => 'ns_cards',
            'include' => 'petro::settlement.partials.payment_tabs.cards',
        ],
        [
            'id' => 'cheques_tab',
            'class' => 'cheques_tab',
            'icon' => 'fa-pencil',
            'label' => __('petro::lang.cheques'),
            'block_key' => 'ns_cheques',
            'include' => 'petro::settlement.partials.payment_tabs.cheques',
        ],
        [
            'id' => 'expense_tab',
            'class' => 'expense_tab',
            'icon' => 'fa-bell-o',
            'label' => __('petro::lang.expneses'),
            'block_key' => 'ns_expenses',
            'include' => 'petro::settlement.partials.payment_tabs.expense',
        ],
        [
            'id' => 'shortage_tab',
            'class' => 'shortage_tab',
            'icon' => 'fa-thermometer-o',
            'label' => __('petro::lang.shortage'),
            'block_key' => 'ns_shortage',
            'include' => 'petro::settlement.partials.payment_tabs.shortage',
        ],
        [
            'id' => 'excess_tab',
            'class' => 'excess_tab',
            'icon' => 'fa-thermometer-full',
            'label' => __('petro::lang.excess'),
            'block_key' => 'ns_excess',
            'include' => 'petro::settlement.partials.payment_tabs.excess',
        ],
        [
            'id' => 'credit_sales_tab',
            'class' => 'credit_sales_tab',
            'icon' => 'fa-credit-card-alt',
            'label' => __('petro::lang.credit_sales'),
            'block_key' => 'ns_credit_sales',
            'include' => 'petro::settlement.partials.payment_tabs.credit_sales',
        ],
        [
            'id' => 'loan_payments_tab',
            'class' => 'loan_payments_tab',
            'icon' => 'fa-credit-card-alt',
            'label' => __('petro::lang.loan_payments'),
            'block_key' => 'ns_loan_payments',
            'include' => 'petro::settlement.partials.payment_tabs.loan_payments',
        ],
        [
            'id' => 'drawing_payments_tab',
            'class' => 'drawing_payments_tab',
            'icon' => 'fa-credit-card-alt',
            'label' => __('petro::lang.drawing_payments'),
            'block_key' => 'ns_drawing_payments',
            'include' => 'petro::settlement.partials.payment_tabs.owners_drawings',
        ],
        [
            'id' => 'settlement_customer_loans',
            'class' => 'settlement_customer_loans_tab',
            'icon' => 'fa-credit-card-alt',
            'label' => __('petro::lang.customer_loans'),
            'block_key' => 'ns_customer_loans',
            'include' => 'petro::settlement.partials.payment_tabs.customer_loans',
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
        position: relative;
        z-index: 20;
        pointer-events: auto !important;
    }

    .s271-direct-payment-tabs > .nav-tabs > li {
        display: inline-block !important;
        float: none !important;
        margin-bottom: 8px;
        position: relative;
        pointer-events: auto !important;
    }

    .s271-direct-payment-tabs > .nav-tabs > li > a {
        pointer-events: auto !important;
        cursor: pointer;
    }

    /* LA1081: each payment method is a real, unique tab pane.  The earlier
       template bank rendered the same form fields more than once and depended
       on cloned inline scripts, which made the clicked payment tab unreliable. */
    .s271-direct-payment-tabs > .tab-content > .tab-pane {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        overflow: hidden !important;
    }

    .s271-direct-payment-tabs > .tab-content > .tab-pane.active,
    .s271-direct-payment-tabs > .tab-content > .tab-pane.in,
    .s271-direct-payment-tabs > .tab-content > .tab-pane.show {
        display: block !important;
        visibility: visible !important;
        height: auto !important;
        overflow: visible !important;
        min-height: 120px;
        clear: both;
    }

    /* IS1762: every Select2 list is anchored to its own form-group. This keeps
       Card Type directly below the field and prevents product lists from being
       clipped or repositioned by the scrolling modal. */
    .add_payment .s271-direct-payment-tabs .form-group {
        position: relative;
        overflow: visible !important;
    }

    .add_payment .select2-container {
        width: 100% !important;
        max-width: 100% !important;
    }

    .add_payment .select2-dropdown,
    .add_payment .select2-container--open {
        z-index: 100000 !important;
    }

    .add_payment .is1762-select2-anchor > .select2-container--open {
        z-index: 100001 !important;
    }

    .add_payment .select2-results__options {
        max-height: 230px !important;
        overflow-y: auto !important;
    }
</style>

<div class="row">
    <div class="col-sm-12">
        <div class="settlement_tabs s271-direct-payment-tabs" data-la1081-payment-tabs="1">
            <ul class="nav nav-tabs no-erp-global-tabs no-exf-tabs" role="tablist">
                @foreach($s271_payment_tabs as $tab)
                    <li role="presentation" class="{{ $tab['id'] === $active_tab_id ? 'active' : '' }}">
                        <a href="#{{ $tab['id'] }}"
                           class="tabs {{ $tab['class'] }} {{ $tab['id'] === $active_tab_id ? 'active' : '' }}"
                           data-s271-tab="{{ $tab['id'] }}"
                           data-petro-payment-target="{{ $tab['id'] }}"
                           role="tab"
                           aria-controls="{{ $tab['id'] }}"
                           aria-selected="{{ $tab['id'] === $active_tab_id ? 'true' : 'false' }}">
                            <i class="fa {{ $tab['icon'] }}"></i>
                            <strong>{{ $tab['label'] }}</strong>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- LA1081: render every payment form exactly once in a standard tab pane.
                 This preserves all existing field IDs, AJAX rows and event handlers. --}}
            <div id="s271_active_payment_content" class="tab-content s271-active-payment-content">
                @foreach($s271_payment_tabs as $tab)
                    <div id="{{ $tab['id'] }}"
                         class="tab-pane fade {{ $tab['id'] === $active_tab_id ? 'active in show' : '' }}"
                         data-petro-payment-pane="{{ $tab['id'] }}"
                         role="tabpanel"
                         aria-hidden="{{ $tab['id'] === $active_tab_id ? 'false' : 'true' }}">
                        @php $class = ""; @endphp
                        @if(!empty($package_details[$tab['block_key']]))
                            @php $class = "hidden"; @endphp
                            <div class="notice_card card text-center">{{ $message }}</div>
                        @endif
                        <div class="{{ $class }}">
                            @include($tab['include'])
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script id="is1731-direct-settlement-payment-tabs-fix">
(function (window, document, $) {
    'use strict';

    var rootSelector = '.s271-direct-payment-tabs';
    var linkSelector = 'a[data-petro-payment-target]';

    function normaliseTarget(rawTarget) {
        if (!rawTarget) { return 'cash_tab'; }
        rawTarget = String(rawTarget);
        var hashPosition = rawTarget.indexOf('#');
        if (hashPosition >= 0) {
            rawTarget = rawTarget.substring(hashPosition + 1);
        }
        return rawTarget.replace(/[^A-Za-z0-9_-]/g, '') || 'cash_tab';
    }

    function directChild(parent, className) {
        if (!parent) { return null; }
        for (var i = 0; i < parent.children.length; i++) {
            if (parent.children[i].classList && parent.children[i].classList.contains(className)) {
                return parent.children[i];
            }
        }
        return null;
    }

    function getWrap(context) {
        var element = context && context.jquery ? context[0] : context;

        if (element && element.nodeType === 1 && element.closest) {
            var closest = element.closest(rootSelector);
            if (closest) { return closest; }

            var inside = element.querySelector ? element.querySelector(rootSelector) : null;
            if (inside) { return inside; }
        }

        var visibleModalRoot = document.querySelector('.add_payment.in ' + rootSelector + ', .add_payment.show ' + rootSelector);
        if (visibleModalRoot) { return visibleModalRoot; }

        var roots = document.querySelectorAll(rootSelector);
        return roots.length ? roots[roots.length - 1] : null;
    }

    function getLinks(root) {
        var nav = directChild(root, 'nav-tabs');
        return nav ? nav.querySelectorAll(linkSelector) : [];
    }

    function getPanes(root) {
        var content = directChild(root, 'tab-content');
        if (!content) { return []; }

        var panes = [];
        for (var i = 0; i < content.children.length; i++) {
            if (content.children[i].classList && content.children[i].classList.contains('tab-pane')) {
                panes.push(content.children[i]);
            }
        }
        return panes;
    }

    function findLink(root, targetId) {
        var links = getLinks(root);
        for (var i = 0; i < links.length; i++) {
            if (normaliseTarget(links[i].getAttribute('data-petro-payment-target')) === targetId) {
                return links[i];
            }
        }
        return null;
    }

    function findPane(root, targetId) {
        var panes = getPanes(root);
        for (var i = 0; i < panes.length; i++) {
            if (normaliseTarget(panes[i].getAttribute('data-petro-payment-pane') || panes[i].id) === targetId) {
                return panes[i];
            }
        }
        return null;
    }

    function is1762Select2Parent($select) {
        var $parent = $select.closest('.form-group');

        if (!$parent.length) {
            $parent = $select.closest('.input-group');
        }
        if (!$parent.length) {
            $parent = $select.closest('.tab-pane');
        }
        if (!$parent.length) {
            $parent = $select.closest('.add_payment');
        }
        if (!$parent.length) {
            $parent = $(document.body);
        }

        $parent.addClass('is1762-select2-anchor').css({
            position: 'relative',
            overflow: 'visible'
        });

        return $parent;
    }

    window.__is1762InitPetroPaymentSelect2 = function (context) {
        if (!$ || !$.fn || !$.fn.select2) {
            return;
        }

        var $scope = context ? $(context) : $('.add_payment:visible');
        if (!$scope.length) {
            return;
        }

        var $selects = $scope.is('select.select2')
            ? $scope
            : $scope.find('select.select2');

        $selects.each(function () {
            var $select = $(this);
            var $parent = is1762Select2Parent($select);
            var currentParent = $select.data('is1762Select2Parent');

            // Do not destroy an already-correct Select2. The old repeated
            // destroy/recreate cycle was closing the Credit Sales product list.
            if ($select.data('select2') && currentParent === $parent[0]) {
                return;
            }

            // Never tear down a dropdown while the user is interacting with it.
            if ($select.data('select2') &&
                $select.next('.select2-container').hasClass('select2-container--open')) {
                return;
            }

            try {
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }

                $select.select2({
                    width: '100%',
                    dropdownParent: $parent
                });

                $select.data('is1762Select2Parent', $parent[0]);
            } catch (error) {
                if (window.console && console.error) {
                    console.error('IS1762 Select2 initialization failed', error);
                }
            }
        });
    };

    function is1762ForceDropdownBelow(select) {
        setTimeout(function () {
            var $select = $(select);
            var $parent = is1762Select2Parent($select);
            var $selection = $select.next('.select2-container');
            var $dropdownContainer = $parent.children('.select2-container--open')
                .has('.select2-dropdown')
                .last();

            if (!$dropdownContainer.length || !$selection.length) {
                return;
            }

            var position = $selection.position();
            var top = (position ? position.top : 0) + $selection.outerHeight();

            $dropdownContainer
                .removeClass('select2-container--above')
                .addClass('select2-container--below')
                .css({
                    top: top + 'px',
                    left: (position ? position.left : 0) + 'px',
                    width: $selection.outerWidth() + 'px'
                });

            $dropdownContainer.find('.select2-dropdown')
                .removeClass('select2-dropdown--above')
                .addClass('select2-dropdown--below');
        }, 0);
    }

    if ($) {
        $(document)
            .off('select2:open.is1762PetroPayment')
            .on('select2:open.is1762PetroPayment',
                '.add_payment select[data-force-dropdown-below="1"]',
                function () {
                    is1762ForceDropdownBelow(this);
                });
    }

    function initialiseWidgets(pane) {
        if (!pane || !$) { return; }
        var $pane = $(pane);

        window.__is1762InitPetroPaymentSelect2($pane);

        if ($.fn && $.fn.iCheck) {
            $pane.find('input[type="checkbox"].input-icheck, input[type="checkbox"].input-icheck-red').each(function () {
                try {
                    var $input = $(this);
                    if (!$input.parent().hasClass('icheckbox_square-blue')) {
                        $input.iCheck({
                            checkboxClass: 'icheckbox_square-blue',
                            radioClass: 'iradio_square-blue'
                        });
                    }
                } catch (error) {}
            });
        }
    }

    function focusActiveField(pane, targetId) {
        if (!pane || !$) { return; }
        setTimeout(function () {
            if (targetId === 'expense_tab' && $('#expense_category').length) {
                $('#expense_category').focus();
                return;
            }
            if (targetId === 'credit_sales_tab' && $('#order_number').length) {
                $('#order_number').focus();
                return;
            }
            $(pane).find(':input:enabled:visible:first').focus();
        }, 30);
    }

    function activatePaymentTab(context, rawTarget, emitEvent) {
        var root = getWrap(context);
        if (!root) { return false; }

        var targetId = normaliseTarget(rawTarget);
        var link = findLink(root, targetId);
        var pane = findPane(root, targetId);

        if (!link || !pane) {
            targetId = 'cash_tab';
            link = findLink(root, targetId);
            pane = findPane(root, targetId);
        }

        if (!link || !pane) { return false; }

        var item = link.closest ? link.closest('li') : link.parentElement;
        if (item && item.classList.contains('disabled')) { return false; }

        var links = getLinks(root);
        for (var i = 0; i < links.length; i++) {
            var linkItem = links[i].closest ? links[i].closest('li') : links[i].parentElement;
            links[i].classList.remove('active');
            links[i].setAttribute('aria-selected', 'false');
            links[i].setAttribute('tabindex', '-1');
            if (linkItem) {
                linkItem.classList.remove('active');
                linkItem.classList.remove('show');
            }
        }

        var panes = getPanes(root);
        for (var p = 0; p < panes.length; p++) {
            panes[p].classList.remove('active');
            panes[p].classList.remove('in');
            panes[p].classList.remove('show');
            panes[p].setAttribute('aria-hidden', 'true');
            panes[p].hidden = true;
            panes[p].style.display = 'none';
            panes[p].style.visibility = 'hidden';
            panes[p].style.height = '0';
            panes[p].style.overflow = 'hidden';
        }

        link.classList.add('active');
        link.setAttribute('aria-selected', 'true');
        link.setAttribute('tabindex', '0');
        if (item) {
            item.classList.add('active');
            item.classList.add('show');
        }

        pane.classList.add('active');
        pane.classList.add('in');
        pane.classList.add('show');
        pane.setAttribute('aria-hidden', 'false');
        pane.hidden = false;
        pane.style.display = 'block';
        pane.style.visibility = 'visible';
        pane.style.height = 'auto';
        pane.style.overflow = 'visible';

        initialiseWidgets(pane);
        focusActiveField(pane, targetId);

        if (typeof window.__petroRecalculatePaymentTotals === 'function') {
            window.__petroRecalculatePaymentTotals();
        }

        if (emitEvent && $) {
            $(link).trigger('shown.bs.tab');
        }

        return true;
    }

    window.s271SyncActivePaymentTabTemplate = function (context) {
        var root = getWrap(context);
        if (!root) { return; }
        var nav = directChild(root, 'nav-tabs');
        var activeLink = nav ? nav.querySelector('li.active ' + linkSelector) : null;
        var targetId = activeLink ? activeLink.getAttribute('data-petro-payment-target') : 'cash_tab';
        initialiseWidgets(findPane(root, normaliseTarget(targetId)));
    };

    window.s271LoadOnlySelectedPaymentTab = function (context, target) {
        return activatePaymentTab(context, target, false);
    };

    function closestPaymentLink(target) {
        var element = target && target.nodeType === 1 ? target : (target ? target.parentElement : null);
        if (!element || !element.closest) { return null; }
        var link = element.closest(linkSelector);
        if (!link) { return null; }
        return link.closest(rootSelector) ? link : null;
    }

    function capturePaymentTabClick(event) {
        var link = closestPaymentLink(event.target);
        if (!link) { return; }

        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }

        activatePaymentTab(link, link.getAttribute('data-petro-payment-target'), true);
    }

    if (window.__is1731DirectPaymentTabClickHandler) {
        document.removeEventListener('click', window.__is1731DirectPaymentTabClickHandler, true);
    }
    window.__is1731DirectPaymentTabClickHandler = capturePaymentTabClick;
    document.addEventListener('click', window.__is1731DirectPaymentTabClickHandler, true);

    function initialiseRoot(root) {
        var nav = directChild(root, 'nav-tabs');
        if (!nav) { return; }
        var active = nav.querySelector('li.active ' + linkSelector) || nav.querySelector(linkSelector);
        activatePaymentTab(root, active ? active.getAttribute('data-petro-payment-target') : 'cash_tab', false);
    }

    function initialiseAll() {
        var roots = document.querySelectorAll(rootSelector);
        for (var i = 0; i < roots.length; i++) {
            initialiseRoot(roots[i]);
        }
    }

    if ($) {
        $(document)
            .off('shown.bs.modal.is1731PaymentTabs')
            .on('shown.bs.modal.is1731PaymentTabs', '.add_payment', function () {
                initialiseRoot(getWrap(this));
            })
            .off('erp.modal.loaded.is1731PaymentTabs')
            .on('erp.modal.loaded.is1731PaymentTabs', '.add_payment', function () {
                initialiseRoot(getWrap(this));
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialiseAll, { once: true });
    } else {
        setTimeout(initialiseAll, 0);
    }
})(window, document, window.jQuery);
</script>

<script>
(function () {
    window.__is1607InitAddPaymentWidgets = function (context) {
        var $scope = context ? $(context) : $('.add_payment:visible');
        if (!$scope.length) {
            $scope = $('.modal:visible').last();
        }

        if (typeof window.__is1762InitPetroPaymentSelect2 === 'function') {
            var $activePane = $scope.is('.tab-pane')
                ? $scope
                : $scope.find('.s271-direct-payment-tabs > .tab-content > .tab-pane.active');

            if (!$activePane.length && $scope.is('.s271-direct-payment-tabs')) {
                $activePane = $scope.children('.tab-content').children('.tab-pane.active');
            }

            window.__is1762InitPetroPaymentSelect2($activePane);
        }
    };

    $(document)
        .off('shown.bs.modal.is1607addpayment')
        .on('shown.bs.modal.is1607addpayment', '.add_payment', function () {
            var modal = this;
            setTimeout(function () {
                window.__is1607InitAddPaymentWidgets(modal);
            }, 100);
        })
        .off('shown.bs.tab.is1607addpayment')
        .on('shown.bs.tab.is1607addpayment', '.s271-direct-payment-tabs a[data-s271-tab]', function () {
            var pane = $($(this).attr('href'));
            setTimeout(function () {
                window.__is1607InitAddPaymentWidgets(pane);
            }, 50);
        })
        .off('ajaxComplete.is1607addpayment')
        .on('ajaxComplete.is1607addpayment', function () {
            if (!$('.add_payment:visible').length) {
                return;
            }

            setTimeout(function () {
                // This call is idempotent; it no longer destroys an open dropdown.
                window.__is1607InitAddPaymentWidgets($('.add_payment:visible'));

                if (typeof window.__petroRecalculatePaymentTotals === 'function') {
                    window.__petroRecalculatePaymentTotals();
                }
            }, 150);
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
