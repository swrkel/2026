@php
    $subscription_grace_details = $subscription_grace_details ?? null;
    $subscription_read_only = false;
    $subscription_read_only_message = 'Subscription has expired, please renew';
    $subscription_business_id = null;

    if (empty($subscription_grace_details) && auth()->check() && !auth()->user()->can('superadmin')) {
        $subscription_business_id = session()->get('user.business_id') ?? session()->get('business.id') ?? auth()->user()->business_id;
        $subscription_grace_details = \Modules\Superadmin\Entities\Subscription::expired_grace_period_details($subscription_business_id);

        $subscription_read_only = !empty($subscription_grace_details);
    }

    if (!empty($subscription_grace_details)) {
        $subscription_read_only_message = $subscription_grace_details['message'];
    }
@endphp

@if (!empty($subscription_read_only))
    @if (!empty($subscription_grace_details))
    <div class="modal fade" id="subscription_grace_period_modal" tabindex="-1" role="dialog" aria-labelledby="subscriptionGracePeriodTitle">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-yellow">
                    <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="subscriptionGracePeriodTitle">@lang('superadmin::lang.subscription_expired')</h4>
                </div>
                <div class="modal-body">
                    <p class="text-bold">{!! e($subscription_grace_details['message']) !!}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">@lang('messages.close')</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
        $(document).ready(function() {
            var subscriptionGraceKey = 'subscription_grace_notice_closed_{{ $subscription_business_id }}_{{ $subscription_grace_details['delete_on'] ?? 'expired' }}';
            var subscriptionGraceMessage = @json($subscription_read_only_message);
            var mutatingFormSelector = 'form[method="post"], form[method="POST"], form[method="put"], form[method="PUT"], form[method="patch"], form[method="PATCH"], form[method="delete"], form[method="DELETE"]';
            var disableSelector = 'input:not([type="hidden"]):not([data-subscription-grace-allow]), textarea:not([data-subscription-grace-allow]), select:not([data-subscription-grace-allow]), button[type="submit"]:not([data-subscription-grace-allow])';

            @if (!empty($subscription_grace_details))
            if (window.localStorage && localStorage.getItem(subscriptionGraceKey) !== '1') {
                $('#subscription_grace_period_modal').modal('show');
            }

            $('#subscription_grace_period_modal').on('hidden.bs.modal', function() {
                if (window.localStorage) {
                    localStorage.setItem(subscriptionGraceKey, '1');
                }
            });
            @endif

            function applySubscriptionGraceReadOnly($scope) {
                $scope = $scope || $(document);

                $scope.find(mutatingFormSelector).each(function() {
                    var $form = $(this);

                    if ($form.data('subscriptionGraceAllow')) {
                        return;
                    }

                    $form.find(disableSelector).prop('disabled', true).addClass('subscription-grace-disabled');
                    $form.find('.select2, .select2-hidden-accessible').prop('disabled', true).trigger('change.select2');
                });

                $scope.find('a, button').filter(function() {
                    var text = $.trim($(this).text()).toLowerCase();
                    return $.inArray(text, ['add', 'edit', 'update', 'save', 'delete', 'remove', 'finalize settlement']) !== -1;
                }).not('[data-subscription-grace-allow]').each(function() {
                    $(this).addClass('disabled subscription-grace-disabled-link').attr('aria-disabled', 'true');
                });
            }

            applySubscriptionGraceReadOnly($(document));

            $(document).ajaxComplete(function() {
                applySubscriptionGraceReadOnly($(document));
            });

            $(document).on('submit', mutatingFormSelector, function(e) {
                if (!$(this).data('subscriptionGraceAllow')) {
                    e.preventDefault();
                    toastr.error(subscriptionGraceMessage, 'Error');
                    return false;
                }
            });

            $(document).on('click', '.subscription-grace-disabled-link', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                toastr.error(subscriptionGraceMessage, 'Error');
                return false;
            });
        });
    </script>
@endif
