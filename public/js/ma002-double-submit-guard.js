/*
 * MA-002 - GLOBAL DOUBLE-SUBMIT GUARD
 * ---------------------------------------------------------------------------
 * Stops the same Add / Save / Update / Submit action being fired twice when a
 * slow response leaves the user unsure whether their first click registered.
 *
 * WHY THIS EXISTS
 * LA-1134: settlement_loan_payments rows 1 and 2 on settlement 1 were both
 * 4,000 on the same loan account, created TWO SECONDS APART. The credit sale
 * tab carried the same signature - two rows of 15,000 on ST1. The settlement
 * posted both faithfully, so the Cash Account book correctly showed two debits
 * and two credits. Nothing was wrong with the posting or the report: the rows
 * were genuinely entered twice.
 *
 * Per-button guards were fixing this one screen at a time. This does it once,
 * everywhere.
 *
 * ---------------------------------------------------------------------------
 * DESIGN - the risk here is locking a button that SHOULD still work, so every
 * decision below errs towards releasing early rather than holding on.
 *
 *  1. A button is locked only once it has actually started work: either its
 *     form submitted, or an AJAX request began. Clicking a button that fails
 *     validation, opens a modal, or does nothing never locks it.
 *
 *  2. Release happens on ALL of these, whichever comes first:
 *       - the AJAX request completes (success OR failure)
 *       - the page starts unloading (normal form post)
 *       - a timeout backstop, in case a request never completes at all
 *     A guard that can jam a button is worse than the problem it solves.
 *
 *  3. Opt-out is explicit and easy: add class "ma002-allow-double" or the
 *     attribute data-allow-double="1" to anything that genuinely needs to be
 *     pressed repeatedly.
 *
 *  4. Nothing here changes what is submitted, or where. It only suppresses a
 *     SECOND identical dispatch while the first is still in flight.
 *
 *  5. It attaches on the capture phase at document level, so it works for
 *     buttons added to the page later - modals, DataTables rows, dynamically
 *     built payment tabs. Those are exactly the screens where this matters.
 */
(function () {
    'use strict';

    if (!window.jQuery) {
        return;
    }

    var $ = window.jQuery;

    // How long a lock may persist if nothing else releases it.
    var FALLBACK_RELEASE_MS = 15000;

    // What counts as an action worth guarding.
    var GUARDED = [
        'button[type="submit"]',
        'input[type="submit"]',
        'button.btn-submit',
        '.ma002-guard'
    ].join(',');

    // Text on a button that indicates a save-type action, used when the
    // element is a plain button wired to AJAX rather than a real submit.
    var SAVE_WORDS = /^(add|save|update|submit|create|store|confirm|finalize|finalise|post|pay|apply)\b/i;

    function isOptedOut($el) {
        return $el.hasClass('ma002-allow-double') ||
               $el.attr('data-allow-double') === '1' ||
               $el.closest('[data-allow-double="1"]').length > 0;
    }

    function lock($el) {
        if (!$el || !$el.length || isOptedOut($el)) {
            return;
        }
        if ($el.data('ma002Locked')) {
            return;
        }
        $el.data('ma002Locked', true);
        $el.addClass('ma002-locked');

        // Do NOT set the disabled attribute on a submit button before its own
        // form posts - a disabled control is omitted from the submitted data,
        // which would silently drop the button's own name/value. Pointer
        // events are enough to stop a second click.
        $el.css('pointer-events', 'none').css('opacity', '0.65');

        var timer = window.setTimeout(function () {
            release($el);
        }, FALLBACK_RELEASE_MS);
        $el.data('ma002LockTimer', timer);
    }

    function release($el) {
        if (!$el || !$el.length) {
            return;
        }
        var timer = $el.data('ma002LockTimer');
        if (timer) {
            window.clearTimeout(timer);
        }
        $el.removeData('ma002LockTimer');
        $el.removeData('ma002Locked');
        $el.removeClass('ma002-locked');
        $el.css('pointer-events', '').css('opacity', '');
    }

    function releaseAll() {
        $('.ma002-locked').each(function () {
            release($(this));
        });
    }

    // The element most recently pressed, so an AJAX call started by it can be
    // attributed back to it.
    var $lastPressed = null;

    $(document).on('mousedown keydown', GUARDED + ',button,input[type="button"]', function (e) {
        if (e.type === 'keydown' && e.which !== 13 && e.which !== 32) {
            return;
        }
        $lastPressed = $(this);
    });

    /*
     * A real form submission. The browser is about to navigate or post, so the
     * button has certainly started work.
     */
    $(document).on('submit', 'form', function () {
        var $form = $(this);
        if (isOptedOut($form)) {
            return;
        }
        var $btn = $form.find('button[type="submit"], input[type="submit"]').filter(':visible').first();
        if (!$btn.length && $lastPressed && $lastPressed.closest('form').is($form)) {
            $btn = $lastPressed;
        }
        lock($btn);
    });

    /*
     * AJAX-driven buttons. jQuery fires these globally, so this covers every
     * $.ajax / $.post call in the application without touching any of them.
     */
    $(document).ajaxSend(function () {
        if (!$lastPressed || !$lastPressed.length) {
            return;
        }
        var text = $.trim($lastPressed.text() || $lastPressed.val() || '');
        var looksLikeSave = $lastPressed.is(GUARDED) || SAVE_WORDS.test(text);
        if (looksLikeSave) {
            lock($lastPressed);
        }
    });

    $(document).ajaxComplete(function () {
        // Release whatever this request locked. Runs on success AND failure,
        // which is the point - a failed save must leave the button usable.
        releaseAll();
        $lastPressed = null;
    });

    // Normal navigation away after a form post.
    $(window).on('beforeunload pagehide', releaseAll);

    // Bootstrap modals: reopening a modal should always start clean.
    $(document).on('shown.bs.modal hidden.bs.modal', releaseAll);
}());
