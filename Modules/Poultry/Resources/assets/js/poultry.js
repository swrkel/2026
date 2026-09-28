/*
 * Poultry module scripts.
 *
 * Shared helpers only - screen specific behaviour lives in the @section('javascript')
 * block of the view that needs it, so a change to one screen cannot break another.
 *
 * Publish with:  php artisan module:publish Poultry
 * which copies this to public/modules/poultry/js/poultry.js
 */
(function (window, $) {
    'use strict';

    if (!$) { return; }

    var Poultry = {

        /**
         * Render a bootstrap alert into a container. Used by every ajax screen
         * so success, warning and failure look the same throughout the module.
         *
         * "warning" matters here: a collection that was recorded but not posted
         * to stock (withdrawal period, unmapped grade) succeeded - it is not an
         * error, but it is not a plain success either.
         */
        feedback: function ($target, level, message) {
            $target.html(
                '<div class="alert alert-' + level + '" style="margin:8px 0 0">' +
                $('<div>').text(message).html() +
                '</div>'
            );
        },

        /** Pull a readable message out of a Laravel validation error response. */
        errorMessage: function (xhr, fallback) {
            if (xhr && xhr.responseJSON) {
                if (xhr.responseJSON.errors) {
                    return Object.keys(xhr.responseJSON.errors)
                        .map(function (key) { return xhr.responseJSON.errors[key].join(' '); })
                        .join(' ');
                }
                if (xhr.responseJSON.msg) { return xhr.responseJSON.msg; }
                if (xhr.responseJSON.message) { return xhr.responseJSON.message; }
            }
            return fallback || 'Request failed.';
        },

        number: function (value, decimals) {
            var n = parseFloat(value);
            if (isNaN(n)) { return '-'; }
            return n.toLocaleString(undefined, {
                minimumFractionDigits: decimals || 0,
                maximumFractionDigits: decimals || 0
            });
        },

        /**
         * Guard against a double submit on the slower posting screens - feed
         * issue and harvest both write to shared stock, and a duplicate post
         * there is materially worse than a duplicate read.
         */
        guard: function ($button, promise) {
            $button.prop('disabled', true);
            return promise.always(function () {
                $button.prop('disabled', false);
            });
        }
    };

    window.Poultry = Poultry;

    $(function () {
        // Date inputs never accept a future date anywhere in this module.
        $('input[type="date"]:not([data-allow-future])').each(function () {
            if (!this.max) {
                this.max = new Date().toISOString().slice(0, 10);
            }
        });
    });

}(window, window.jQuery));
