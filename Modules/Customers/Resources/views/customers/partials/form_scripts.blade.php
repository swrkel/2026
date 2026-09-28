<style>
    .customers-contact-parity-form .customers-form-tabs {
        margin-bottom: 15px;
    }

    .customers-contact-parity-form .customers-form-tabs > li > a {
        cursor: pointer;
        user-select: none;
    }

    .customers-contact-parity-form .customers-tab-content {
        padding-top: 10px;
    }

    .customers-contact-parity-form .customers-tab-content > .tab-pane {
        display: none;
    }

    .customers-contact-parity-form .customers-tab-content > .tab-pane.active {
        display: block;
    }

    .customers-contact-parity-form .form-control {
        min-height: 42px;
    }

    .customers-contact-parity-form .input-group-addon {
        min-width: 42px;
    }

    .customers-amount-field {
        text-align: right !important;
    }
</style>

<script>
(function (window, document, $) {
    'use strict';

    function activateCustomerFormTab(root, targetId, focusTab) {
        if (!root || !targetId) {
            return;
        }

        var links = root.querySelectorAll('[data-customers-form-tab]');
        var panes = root.querySelectorAll('.customers-tab-content > .tab-pane');
        var targetPane = root.querySelector('#' + targetId);
        var targetLink = root.querySelector('[data-customers-form-tab="' + targetId + '"]');

        if (!targetPane || !targetLink) {
            return;
        }

        Array.prototype.forEach.call(links, function (link) {
            var selected = link === targetLink;
            var parent = link.parentElement;

            link.setAttribute('aria-selected', selected ? 'true' : 'false');
            link.setAttribute('tabindex', selected ? '0' : '-1');

            if (parent) {
                parent.classList.toggle('active', selected);
            }
        });

        Array.prototype.forEach.call(panes, function (pane) {
            var selected = pane === targetPane;
            pane.classList.toggle('active', selected);
            pane.classList.toggle('in', selected);
            pane.setAttribute('aria-hidden', selected ? 'false' : 'true');
        });

        if (focusTab) {
            targetLink.focus();
        }

        if ($) {
            $(targetLink).trigger($.Event('shown.bs.tab', {
                target: targetLink,
                relatedTarget: null
            }));

            window.setTimeout(function () {
                $(window).trigger('resize');
            }, 0);
        }
    }

    function initialiseCustomerFormTabs(root) {
        if (!root || root.getAttribute('data-customers-tabs-ready') === '1') {
            return;
        }

        root.setAttribute('data-customers-tabs-ready', '1');

        var links = root.querySelectorAll('[data-customers-form-tab]');
        if (!links.length) {
            return;
        }

        Array.prototype.forEach.call(links, function (link, index) {
            link.setAttribute('tabindex', index === 0 ? '0' : '-1');

            link.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                activateCustomerFormTab(root, link.getAttribute('data-customers-form-tab'), false);
            }, true);

            link.addEventListener('keydown', function (event) {
                var key = event.key || event.keyCode;
                var currentIndex = Array.prototype.indexOf.call(links, link);
                var nextIndex = currentIndex;

                if (key === 'ArrowRight' || key === 39) {
                    nextIndex = (currentIndex + 1) % links.length;
                } else if (key === 'ArrowLeft' || key === 37) {
                    nextIndex = (currentIndex - 1 + links.length) % links.length;
                } else if (key === 'Home' || key === 36) {
                    nextIndex = 0;
                } else if (key === 'End' || key === 35) {
                    nextIndex = links.length - 1;
                } else if (key === 'Enter' || key === ' ' || key === 13 || key === 32) {
                    event.preventDefault();
                    activateCustomerFormTab(root, link.getAttribute('data-customers-form-tab'), false);
                    return;
                } else {
                    return;
                }

                event.preventDefault();
                activateCustomerFormTab(root, links[nextIndex].getAttribute('data-customers-form-tab'), true);
            });
        });

        var activeLink = root.querySelector('.customers-form-tabs > li.active [data-customers-form-tab]') || links[0];
        activateCustomerFormTab(root, activeLink.getAttribute('data-customers-form-tab'), false);
    }

    function initialiseCustomerFormWidgets() {
        var forms = document.querySelectorAll('.customers-contact-parity-form');
        Array.prototype.forEach.call(forms, initialiseCustomerFormTabs);

        if (!$) {
            return;
        }

        var $forms = $('.customers-contact-parity-form');

        if ($.fn.select2) {
            $forms.find('.select2').each(function () {
                var $select = $(this);
                if (!$select.hasClass('select2-hidden-accessible')) {
                    $select.select2({ width: '100%' });
                }
            });
        }

        if ($.fn.datepicker) {
            $forms.find('.customers-datepicker').each(function () {
                var $input = $(this);
                if (!$input.data('datepicker')) {
                    $input.datepicker({
                        autoclose: true,
                        todayHighlight: true,
                        format: 'mm/dd/yyyy'
                    });
                }
            });
        }

        // IS2297: Transaction Date must always expose a working calendar on
        // Customer Add/Edit, including when the form is loaded into the
        // Customer Register AJAX modal. Clicking the calendar icon opens the
        // existing Bootstrap datepicker; focus remains a safe fallback.
        $forms
            .off('click.customersDatepicker keydown.customersDatepicker', '.customers-open-datepicker')
            .on('click.customersDatepicker keydown.customersDatepicker', '.customers-open-datepicker', function (event) {
                if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ' && event.keyCode !== 13 && event.keyCode !== 32) {
                    return;
                }

                event.preventDefault();
                var $input = $(this).siblings('.customers-datepicker').first();
                if (!$input.length) {
                    return;
                }

                $input.trigger('focus');
                if ($.fn.datepicker) {
                    try {
                        $input.datepicker('show');
                    } catch (ignore) {}
                }
            });

        $forms
            .off('change.customersSubCustomer', '.customers-sub-customer-switch')
            .on('change.customersSubCustomer', '.customers-sub-customer-switch', function () {
                $(this)
                    .closest('.customers-contact-parity-form')
                    .find('.customers-sub-customer-list')
                    .toggle(String($(this).val()) === '1');
            });

        $forms.find('.customers-sub-customer-switch').trigger('change.customersSubCustomer');

        $forms
            .off('blur.customersAmount', '.customers-amount-field')
            .on('blur.customersAmount', '.customers-amount-field', function () {
                var value = ($(this).val() || '').replace(/,/g, '');
                if (value !== '' && !isNaN(value)) {
                    $(this).val(parseFloat(value).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));
                }
            });

        $('#customers_module_add_form, #customers_module_edit_form')
            .off('submit.customersForm')
            .on('submit.customersForm', function () {
                $(this).find(':input').prop('disabled', false);
                return true;
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialiseCustomerFormWidgets, { once: true });
    } else {
        initialiseCustomerFormWidgets();
    }
})(window, document, window.jQuery);
</script>
