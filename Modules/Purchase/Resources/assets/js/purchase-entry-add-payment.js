(function ($) {
    'use strict';

    $(function () {
        var cfg = window.purchaseEntryAddPaymentConfig || {};
        var locationId = String(cfg.locationId || '');
        var mappings = cfg.methodAccounts || {};
        var oldAccountId = String(cfg.oldAccountId || '');
        var $method = $('#payment_method');
        var $account = $('#payment_account_id');
        var $help = $('#payment_account_help');

        function normalise(value) {
            return String(value || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        }

        function linkedIds(method) {
            var byLocation = mappings[locationId] || {};
            var key = normalise(method);
            var aliases = {
                cash: ['cash'],
                bank_transfer: ['bank_transfer', 'direct_bank_deposit', 'bank'],
                cheque: ['cheque'],
                card: ['card', 'own_cards'],
                advance: ['advance', 'pre_payments', 'prepayment'],
                prepayment: ['prepayment', 'pre_payments', 'advance'],
                other: ['other']
            };
            var ids = [];
            (aliases[key] || [key]).forEach(function (alias) {
                var value = byLocation[alias] || [];
                if (Array.isArray(value)) ids = ids.concat(value.map(String));
            });
            return ids.filter(function (value, index, all) { return all.indexOf(value) === index; });
        }

        function refreshAccounts() {
            var method = normalise($method.val());
            var allowed = linkedIds(method);
            var first = '';
            var selectedStillVisible = false;

            $account.find('option').each(function () {
                var $option = $(this);
                var value = String($option.val() || '');
                if (!value) {
                    $option.prop('hidden', false).prop('disabled', false);
                    return;
                }
                var groupId = String($option.data('group-id') || '');
                var scope = String($option.data('location-id') == null ? 'all' : $option.data('location-id'));
                var scopeAllowed = !scope || scope.toLowerCase() === 'all' || scope === locationId;
                var mapped = allowed.indexOf(value) !== -1 || (groupId && allowed.indexOf(groupId) !== -1);
                var visible = scopeAllowed && mapped;
                $option.prop('hidden', !visible).prop('disabled', !visible);
                if (visible && !first) first = value;
                if (visible && String($account.val() || '') === value) selectedStillVisible = true;
            });

            if (!selectedStillVisible) {
                var preferred = oldAccountId && $account.find('option[value="' + oldAccountId.replace(/"/g, '\\"') + '"]:not(:disabled)').length
                    ? oldAccountId : first;
                $account.val(preferred || '');
            }

            $help.text(allowed.length ? '' : 'No payment account is linked to this method for the purchase location.');
            if ($.fn.select2) {
                try { if ($account.hasClass('select2-hidden-accessible')) $account.select2('destroy'); } catch (e) {}
                $account.select2({ width: '100%' });
            }
        }

        function refreshExtraFields() {
            var method = normalise($method.val());
            $('.purchase-payment-extra').hide().find(':input').prop('disabled', true);
            $('.purchase-payment-extra[data-payment-extra="' + method + '"]').show().find(':input').prop('disabled', false);
        }

        $method.on('change', function () {
            oldAccountId = '';
            refreshAccounts();
            refreshExtraFields();
        });

        refreshAccounts();
        refreshExtraFields();
    });
})(jQuery);
