

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
.settlement_tabs .nav-tabs > li,
.settlement_tabs .nav-tabs > li > a[data-pd-payment-tab] {
    pointer-events: auto !important;
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

<style>
    .notice_card{
        color: {{$font_color}} !important;
        font-family:  {!! $font_family !!} !important;
        background-color: {{$background_color}} !important;
        font-size:  {{$font_size}}px !important;
    }
</style>

<div class="row">
    <div class="col-sm-12">
        <div class="settlement_tabs">
            <ul class="nav nav-tabs">
                <li class="active">
                    <a class="tabs cash_tab" data-pd-payment-tab="cash_tab" role="tab" tabindex="0"
                        onclick="return window.petropdActivatePaymentTab(this, event);">
                        <i class="fa fa-money"></i> 
                        @if(empty($package_details['rename_cash_tab']))
                            <strong>@lang('petropd::lang.cash')</strong>
                        @else
                            <strong>@lang('petropd::lang.sale_amount')</strong>
                        @endif
                    </a>
                </li>
                
                {{-- Hidden on PD settlements - not used for Pumper Dashboard operations. --}}
                @if (empty($pd_hide_non_pumper_tabs))
                <li>
                    <a href="#cash_deposit_tab" class="tabs cash_deposit_tab" style="" data-pd-payment-tab="cash_deposit_tab" role="tab"
                        onclick="return window.petropdActivatePaymentTab(this, event);">
                        <i class="fa fa-credit-card"></i> <strong>
                            @lang('petropd::lang.cash_deposit') </strong>
                    </a>
                </li>
                @endif
           
                <li>
                    <a class="tabs cards_tab" style="" data-pd-payment-tab="cards_tab" role="tab" tabindex="0"
                        onclick="return window.petropdActivatePaymentTab(this, event);">
                        <i class="fa fa-credit-card"></i> <strong>
                            @lang('petropd::lang.cards') </strong>
                    </a>
                </li>

                <li>
                    <a href="#cheques_tab" class="tabs cheques_tab" style="" data-pd-payment-tab="cheques_tab" role="tab"
                        onclick="return window.petropdActivatePaymentTab(this, event);">
                        <i class="fa fa-pencil"></i> <strong>
                            @lang('petropd::lang.cheques') </strong>
                    </a>
                </li>

                {{-- Hidden on PD settlements - not used for Pumper Dashboard operations. --}}
                @if (empty($pd_hide_non_pumper_tabs))
                <li>
                    <a href="#expense_tab" class="tabs expense_tab" style="" data-pd-payment-tab="expense_tab" role="tab"
                        onclick="return window.petropdActivatePaymentTab(this, event);">
                        <i class="fa fa-bell-o"></i> <strong>
                            @lang('petropd::lang.expneses') </strong>
                    </a>
                </li>
                @endif

                <li>
                    <a href="#shortage_tab" class="tabs shortage_tab" style="" data-pd-payment-tab="shortage_tab" role="tab"
                        onclick="return window.petropdActivatePaymentTab(this, event);">
                        <i class="fa fa-thermometer-O"></i> <strong>
                            @lang('petropd::lang.shortage') </strong>
                    </a>
                </li>
            
                <li>
                    <a href="#excess_tab" class="tabs excess_tab" style="" data-pd-payment-tab="excess_tab" role="tab"
                        onclick="return window.petropdActivatePaymentTab(this, event);">
                        <i class="fa fa-thermometer-full"></i> <strong>
                            @lang('petropd::lang.excess') </strong>
                    </a>
                </li>

                <li>
                    <a class="tabs credit_sales_tab" style="" data-pd-payment-tab="credit_sales_tab" role="tab" tabindex="0"
                        onclick="return window.petropdActivatePaymentTab(this, event);">
                        <i class="fa fa-credit-card-alt"></i> <strong>
                            @lang('petropd::lang.credit_sales') </strong>
                    </a>
                </li>
            
                {{-- Hidden on PD settlements - not used for Pumper Dashboard operations. --}}
                @if (empty($pd_hide_non_pumper_tabs))
                <li>
                    <a href="#loan_payments_tab" class="tabs loan_payments_tab" style="" data-pd-payment-tab="loan_payments_tab" role="tab"
                        onclick="return window.petropdActivatePaymentTab(this, event);">
                        <i class="fa fa-credit-card-alt"></i> <strong>
                            @lang('petropd::lang.loan_payments') </strong>
                    </a>
                </li>
                @endif
                
                {{-- Hidden on PD settlements - not used for Pumper Dashboard operations. --}}
                @if (empty($pd_hide_non_pumper_tabs))
                <li>
                    <a href="#drawing_payments_tab" class="tabs drawing_payments_tab" style="" data-pd-payment-tab="drawing_payments_tab" role="tab"
                        onclick="return window.petropdActivatePaymentTab(this, event);">
                        <i class="fa fa-credit-card-alt"></i> <strong>
                            @lang('petropd::lang.drawing_payments') </strong>
                    </a>
                </li>
                @endif
                {{-- Hidden on PD settlements - not used for Pumper Dashboard operations. --}}
                @if (empty($pd_hide_non_pumper_tabs))
                <li>
                    <a href="#settlement_customer_loans" class="tabs settlement_customer_loans_tab" style="" data-pd-payment-tab="settlement_customer_loans" role="tab"
                        onclick="return window.petropdActivatePaymentTab(this, event);">
                        <i class="fa fa-credit-card-alt"></i> <strong>
                            @lang('petropd::lang.customer_loans') </strong>
                    </a>
                </li>
                @endif
                
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="cash_tab" data-pd-payment-edit-lock="1">
                    
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_cash']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petropd::pd_settlement.partials.payment_tabs.cash')
                    </div>
                    
                </div>
                
                <div class="tab-pane" id="cash_deposit_tab">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_cash_deposit']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petropd::pd_settlement.partials.payment_tabs.cash_deposit')
                    </div>
                    
                </div>

                <div class="tab-pane" id="cards_tab" data-pd-payment-edit-lock="1">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_cards']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petropd::pd_settlement.partials.payment_tabs.cards')
                    </div>
                   
                </div>

                <div class="tab-pane" id="cheques_tab">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_cheques']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petropd::pd_settlement.partials.payment_tabs.cheques')
                    </div>
                   
                </div>

                <div class="tab-pane" id="expense_tab">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_expenses']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petropd::pd_settlement.partials.payment_tabs.expense')
                    </div>
                   
                </div>

                <div class="tab-pane" id="shortage_tab" data-pd-payment-edit-lock="1">
                     @php $class = ""; @endphp
                    @if(!empty($package_details['ns_shortage']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petropd::pd_settlement.partials.payment_tabs.shortage')
                    </div>
                   
                </div>

                <div class="tab-pane" id="excess_tab" data-pd-payment-edit-lock="1">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_excess']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petropd::pd_settlement.partials.payment_tabs.excess')
                    </div>
                   
                </div>

                <div class="tab-pane" id="credit_sales_tab" data-pd-payment-edit-lock="1">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_credit_sales']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petropd::pd_settlement.partials.payment_tabs.credit_sales')
                    </div>
                   
                </div>
                
                <div class="tab-pane" id="loan_payments_tab">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_loan_payments']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petropd::pd_settlement.partials.payment_tabs.loan_payments')
                    </div>
                    
                   
                </div>
                
                <div class="tab-pane" id="drawing_payments_tab">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_drawing_payments']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petropd::pd_settlement.partials.payment_tabs.owners_drawings')
                    </div>
                    
                   
                </div>
                
                <div class="tab-pane" id="settlement_customer_loans">
                    @php $class = ""; @endphp
                    @if(!empty($package_details['ns_customer_loans']))
                        @php $class = "hidden"; @endphp
                        <div class="notice_card card text-center"> {{ $message }} </div>
                    @endif
                    <div class="{{$class}}">
                        @include('petropd::pd_settlement.partials.payment_tabs.customer_loans')
                    </div>
                    
                   
                </div>

            </div>
        </div>
    </div>
</div>

<script id="petropd-payment-tabs-controller">
/* PetroPD Add Payment tabs are isolated from Bootstrap and the global ERP tab
   engine. The modal is loaded dynamically, so the listener is installed on the
   document in capture phase and works for both current and future modal content. */
(function () {
    'use strict';

    function directChildWithClass(root, className) {
        if (!root) return null;
        for (var i = 0; i < root.children.length; i++) {
            if (root.children[i].classList.contains(className)) {
                return root.children[i];
            }
        }
        return null;
    }

    function activatePetroPdPaymentTab(link) {
        if (!link) return false;

        var targetId = link.getAttribute('data-pd-payment-tab');
        var root = link.closest ? link.closest('.settlement_tabs') : null;
        var nav = directChildWithClass(root, 'nav-tabs');
        var content = directChildWithClass(root, 'tab-content');
        var pane = targetId && content ? content.querySelector('#' + targetId) : null;

        if (!root || !nav || !content || !pane || pane.parentNode !== content) {
            return false;
        }

        for (var n = 0; n < nav.children.length; n++) {
            var item = nav.children[n];
            var itemLink = null;
            for (var a = 0; a < item.children.length; a++) {
                if (item.children[a].tagName === 'A' && item.children[a].hasAttribute('data-pd-payment-tab')) {
                    itemLink = item.children[a];
                    break;
                }
            }
            var selected = itemLink === link;
            item.classList.remove('business-manage-disabled-tab');
            item.removeAttribute('disabled');
            item.removeAttribute('aria-hidden');
            item.classList.toggle('active', selected);
            item.classList.toggle('show', selected);
            if (itemLink) {
                itemLink.classList.remove('business-manage-disabled-tab');
                itemLink.removeAttribute('disabled');
                itemLink.removeAttribute('data-auto-permission-blocked');
                itemLink.removeAttribute('aria-hidden');
                itemLink.style.setProperty('pointer-events', 'auto', 'important');
                itemLink.classList.toggle('active', selected);
                itemLink.setAttribute('aria-selected', selected ? 'true' : 'false');
            }
        }

        for (var p = 0; p < content.children.length; p++) {
            var candidate = content.children[p];
            if (!candidate.classList.contains('tab-pane')) continue;
            var visible = candidate === pane;
            // These panes were rendered only after the explicit PetroPD payment
            // permission checks. Remove stale global discovery markers that can
            // otherwise leave Cash, Cards or Credit Sales unclickable.
            candidate.classList.remove('business-manage-disabled-tab');
            candidate.removeAttribute('disabled');
            candidate.removeAttribute('data-auto-permission-blocked');
            candidate.classList.toggle('active', visible);
            candidate.classList.toggle('show', visible);
            candidate.classList.toggle('in', visible);
            candidate.style.setProperty('display', visible ? 'block' : 'none', 'important');
            candidate.style.setProperty('visibility', visible ? 'visible' : 'hidden', 'important');
            candidate.style.setProperty('pointer-events', visible ? 'auto' : 'none', 'important');
            candidate.setAttribute('aria-hidden', visible ? 'false' : 'true');
        }

        if (window.jQuery) {
            window.jQuery(link).trigger('shown.bs.tab');
            setTimeout(function () {
                try {
                    if (window.jQuery.fn && window.jQuery.fn.dataTable) {
                        window.jQuery.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                    }
                } catch (e) {}
            }, 60);
        }

        return true;
    }

    window.petropdActivatePaymentTab = function (link, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
            if (typeof event.stopImmediatePropagation === 'function') {
                event.stopImmediatePropagation();
            }
        }

        activatePetroPdPaymentTab(link);
        return false;
    };

    function initialiseRoot(root) {
        if (!root) return;
        var nav = directChildWithClass(root, 'nav-tabs');
        if (!nav) return;
        var link = nav.querySelector('li.active > a[data-pd-payment-tab]')
            || nav.querySelector('a[data-pd-payment-tab]');
        if (link) activatePetroPdPaymentTab(link);
    }

    function initialiseAll(scope) {
        var searchRoot = scope && scope.querySelectorAll ? scope : document;
        var roots = searchRoot.querySelectorAll('.settlement_tabs');
        for (var i = 0; i < roots.length; i++) initialiseRoot(roots[i]);
        if (scope && scope.classList && scope.classList.contains('settlement_tabs')) {
            initialiseRoot(scope);
        }
    }

    if (!window.__petroPdPaymentTabsInstalled) {
        window.__petroPdPaymentTabsInstalled = true;

        document.addEventListener('click', function (event) {
            var node = event.target;
            while (node && node !== document && !(node.tagName === 'A' && node.hasAttribute('data-pd-payment-tab'))) {
                node = node.parentNode;
            }
            if (!node || node === document) return;

            var root = node.closest ? node.closest('.settlement_tabs') : null;
            var nav = directChildWithClass(root, 'nav-tabs');
            if (!root || !nav || !node.parentNode || node.parentNode.parentNode !== nav) return;

            event.preventDefault();
            event.stopPropagation();
            if (typeof event.stopImmediatePropagation === 'function') event.stopImmediatePropagation();
            activatePetroPdPaymentTab(node);
        }, true);

        document.addEventListener('keydown', function (event) {
            var node = event.target;
            if ((event.key === 'Enter' || event.key === ' ') && node && node.hasAttribute('data-pd-payment-tab')) {
                event.preventDefault();
                activatePetroPdPaymentTab(node);
            }
        }, true);

        if (window.jQuery) {
            window.jQuery(document)
                .off('shown.bs.modal.petropdPaymentTabs')
                .on('shown.bs.modal.petropdPaymentTabs', '.add_payment, .modal', function () {
                    initialiseAll(this);
                });
        }
    }

    /*
     * The global business permission guard handles click in capture phase.
     * On dynamically loaded Add Payment HTML it can retain a stale discovered
     * tab marker and consume the click before this module receives it. Activate
     * an already-rendered PetroPD payment tab on pointer-down (which the guard
     * does not consume), then let the ordinary click finish harmlessly.
     *
     * This is intentionally scoped to #settlement_form and links that carry
     * PetroPD's explicit data-pd-payment-tab marker.
     */
    if (!window.__petroPdPaymentTabsPointerFixInstalled) {
        window.__petroPdPaymentTabsPointerFixInstalled = true;

        var activateFromEarlyInput = function (event) {
            if (event.__petroPdPaymentTabHandled) return;

            var node = event.target;
            while (node && node !== document
                && !(node.tagName === 'A' && node.hasAttribute('data-pd-payment-tab'))) {
                node = node.parentNode;
            }
            if (!node || node === document || !node.closest || !node.closest('#settlement_form')) {
                return;
            }

            var root = node.closest('.settlement_tabs');
            if (!root) return;

            // A genuinely disabled Manage Page tab is hidden with this class
            // and must remain unavailable.
            if (node.classList.contains('business-manage-disabled-tab')
                || (node.parentNode
                    && node.parentNode.classList
                    && node.parentNode.classList.contains('business-manage-disabled-tab'))) {
                return;
            }

            event.__petroPdPaymentTabHandled = true;
            node.removeAttribute('data-auto-permission-blocked');
            node.removeAttribute('aria-hidden');
            node.classList.remove('business-manage-disabled-tab');
            if (node.parentNode) {
                node.parentNode.classList.remove('business-manage-disabled-tab');
                node.parentNode.removeAttribute('aria-hidden');
            }

            activatePetroPdPaymentTab(node);
        };

        if (window.PointerEvent) {
            document.addEventListener('pointerdown', activateFromEarlyInput, true);
        } else {
            document.addEventListener('mousedown', activateFromEarlyInput, true);
            document.addEventListener('touchstart', activateFromEarlyInput, true);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initialiseAll(document); });
    } else {
        initialiseAll(document);
    }
})();
</script>

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
