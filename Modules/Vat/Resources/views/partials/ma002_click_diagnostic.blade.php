{{--
    MA-002 CLICK DIAGNOSTIC v3 - TEMPORARY.

    v2 produced the page-load line but NO click line. That was a flaw in my
    filter, not a fault on your side.

    v2 only reported when the mousedown target was a select/input/button. But
    if something is COVERING the select, the browser reports the COVERING
    ELEMENT as the target - a div - so v2 skipped the exact case it existed to
    catch. My error.

    v3 has no filter at all. It reports the FIRST mousedown anywhere on the
    page and dumps the entire element stack under the pointer using
    document.elementsFromPoint(), which returns every element at that point in
    z-order, top first.

    That single line answers the question outright:

      stack: div.foo > div.bar > select#customer_id
          -> two elements sit ON TOP of the select. They are named. I fix them.

      stack: select#customer_id > div.form-group > ...
          -> nothing is covering it; the select IS the top element, so the
             cause is preventDefault, pointer-events, or the control's own
             state - all of which are reported alongside.

    Also reported: sel_found (is a <select> anywhere in the stack and at what
    depth), default_prevented, and the select's own computed style and state.

    DELETE this partial, Ma002DiagnosticController and the ma002-click-report
    route once the cause is known.
--}}
<script>
(function () {
    'use strict';

    if (!window.jQuery) { return; }

    var $ = window.jQuery;
    var reported = false;
    var pending = null;
    var url = '{{ route('vat.ma002.click-report') }}';

    function send(data) {
        data._token = $('meta[name="csrf-token"]').attr('content');
        data.page = window.location.pathname.substring(0, 180);
        $.ajax({ method: 'POST', url: url, data: data });
    }

    function probeStyles() {
        // MA-002 v6: report whether the page CSS actually arrived, and what
        // the browser computes for the controls in question. This removes
        // guesswork about deployment and about which rule is winning.
        var out = {};
        try {
            var form = document.getElementById('issue_bill_customer_form');
            out.ma002_css_build = form && window.getComputedStyle
                ? (window.getComputedStyle(form).getPropertyValue('--ma002-build') || 'EMPTY').trim()
                : 'no form element';

            var prod = document.querySelector('#issue_customer_bill_add_table select.product_id')
                    || document.querySelector('select.product_id');
            if (prod) {
                var pcs = window.getComputedStyle(prod);
                var pr = prod.getBoundingClientRect();
                out.product_select = 'pe:' + pcs.pointerEvents + ' pos:' + pcs.position +
                    ' z:' + pcs.zIndex + ' disp:' + pcs.display +
                    ' rect:' + Math.round(pr.left) + ',' + Math.round(pr.top) +
                    ' ' + Math.round(pr.width) + 'x' + Math.round(pr.height) +
                    ' inTable:' + (prod.closest && prod.closest('#issue_customer_bill_add_table') ? 'yes' : 'NO');
            } else {
                out.product_select = 'NOT FOUND on page';
            }

            /*
             * MA-002 v7 - THE DECISIVE PROBE.
             *
             * Six CSS/JS attempts have not fixed the product dropdown. Rather
             * than guess a seventh, this asks the browser directly, on page
             * load, with no click required:
             *
             *   "At the exact centre of the product select, what is stacked
             *    on top of it, and what is each of those elements?"
             *
             * If the select is first in that list, nothing covers it and the
             * cause is not an overlay at all - it is the control itself, and
             * I stop working on overlays.
             *
             * If something is above it, this names that element AND its
             * computed pointer-events, so I can see whether my rules reached
             * it or whether it is an element I have never targeted.
             */
            if (prod && document.elementsFromPoint) {
                var r2 = prod.getBoundingClientRect();
                var cx = Math.round(r2.left + r2.width / 2);
                var cy = Math.round(r2.top + r2.height / 2);
                var stack2 = document.elementsFromPoint(cx, cy) || [];
                out.probe_point = cx + ',' + cy;
                out.over_product = stack2.slice(0, 6).map(function (el) {
                    if (!el || !el.tagName) { return '?'; }
                    var cs2 = window.getComputedStyle(el);
                    var tag = el.tagName.toLowerCase();
                    var id = el.id ? '#' + el.id : '';
                    var cls = (typeof el.className === 'string' && el.className)
                        ? '.' + el.className.trim().split(/\s+/).slice(0, 2).join('.')
                        : '';
                    return tag + id + cls + '{pe:' + cs2.pointerEvents +
                           ' z:' + cs2.zIndex + ' pos:' + cs2.position + '}';
                }).join('  >  ');
                out.product_is_top = (stack2[0] === prod) ? 'YES - nothing covers it'
                                                          : 'NO - something is on top';
            }

            var pay = document.querySelector('.box-body.payment_row');
            if (pay) {
                var ycs = window.getComputedStyle(pay);
                var yr = pay.getBoundingClientRect();
                out.payment_block = 'pe:' + ycs.pointerEvents + ' pos:' + ycs.position +
                    ' z:' + ycs.zIndex +
                    ' rect:' + Math.round(yr.left) + ',' + Math.round(yr.top) +
                    ' ' + Math.round(yr.width) + 'x' + Math.round(yr.height);
            } else {
                out.payment_block = 'not present';
            }
        } catch (e) {
            out.probe_error = String(e).substring(0, 150);
        }
        return out;
    }

    $(function () {
        send($.extend({
            kind: 'pageload',
            note: 'diagnostic v7 active - reports what covers the product select',
            select_count: $('#issue_bill_customer_form select').length,
            select2_count: $('#issue_bill_customer_form .select2-container').length,
            backdrop_count: $('.modal-backdrop').length,
            body_classes: (document.body.className || '').substring(0, 250)
        }, probeStyles()));

        // Run again after the page's own fallback has executed, so the log
        // shows the state BEFORE and AFTER it - if they differ, the fallback
        // is working but something re-applies afterwards.
        window.setTimeout(function () {
            send($.extend({ kind: 'pageload_recheck', note: 'after 1500ms' },
                          probeStyles()));
        }, 1500);
    });

    function brief(el) {
        if (!el || !el.tagName) { return '?'; }
        var tag = el.tagName.toLowerCase();
        var id = el.id ? '#' + el.id : '';
        var cls = (typeof el.className === 'string' && el.className)
            ? '.' + el.className.trim().split(/\s+/).slice(0, 3).join('.')
            : '';
        return (tag + id + cls).substring(0, 90);
    }

    function styleOf(el) {
        if (!el || !window.getComputedStyle) { return ''; }
        var cs = window.getComputedStyle(el);
        var r = el.getBoundingClientRect ? el.getBoundingClientRect() : {};
        return 'pe:' + cs.pointerEvents +
               '/pos:' + cs.position +
               '/z:' + cs.zIndex +
               '/op:' + cs.opacity +
               '/vis:' + cs.visibility +
               '/size:' + Math.round(r.width || 0) + 'x' + Math.round(r.height || 0);
    }

    // No tag filter. Capture phase, so nothing can stop us seeing it.
    document.addEventListener('mousedown', function (e) {
        if (reported) { return; }

        var stack = [];
        if (document.elementsFromPoint) {
            stack = document.elementsFromPoint(e.clientX, e.clientY) || [];
        } else if (document.elementFromPoint) {
            stack = [document.elementFromPoint(e.clientX, e.clientY)];
        }

        var selDepth = -1;
        for (var i = 0; i < stack.length; i++) {
            if (stack[i] && stack[i].tagName &&
                stack[i].tagName.toLowerCase() === 'select') { selDepth = i; break; }
        }

        var sel = selDepth >= 0 ? stack[selDepth] : null;

        pending = {
            kind: 'click',
            target: brief(e.target),
            stack: stack.slice(0, 6).map(brief).join('  >  '),
            // MA-002 v4: geometry of the top 4, so an overlap can be seen
            // directly rather than inferred.
            geom: stack.slice(0, 4).map(function (el) {
                if (!el || !el.getBoundingClientRect) { return '?'; }
                var r = el.getBoundingClientRect();
                var cs = window.getComputedStyle ? window.getComputedStyle(el) : {};
                return brief(el).substring(0, 40) +
                       '[' + Math.round(r.left) + ',' + Math.round(r.top) +
                       ' ' + Math.round(r.width) + 'x' + Math.round(r.height) +
                       ' pos:' + (cs.position || '') + ' z:' + (cs.zIndex || '') + ']';
            }).join(' | '),
            click_at: Math.round(e.clientX) + ',' + Math.round(e.clientY),
            sel_found: selDepth >= 0 ? ('yes at depth ' + selDepth) : 'no select under pointer',
            sel_style: sel ? styleOf(sel) : '',
            sel_state: sel ? ('disabled:' + (sel.disabled ? 'yes' : 'no') +
                              '/name:' + (sel.getAttribute('name') || sel.id || '')) : '',
            top_style: stack.length ? styleOf(stack[0]) : '',
            backdrop_count: $('.modal-backdrop').length,
            body_classes: (document.body.className || '').substring(0, 250)
        };
    }, true);

    /*
     * MA-002 v4 - ISSUE 4 PROBE.
     *
     * The log proved the Add button IS the topmost element and nothing calls
     * preventDefault, so the click reaches it and the modal simply does not
     * open. The three things that can cause that are checked directly.
     */
    $(document).on('click', '[data-toggle="modal"][data-target]', function () {
        var target = $(this).attr('data-target');
        var $modal = $(target);

        window.setTimeout(function () {
            send({
                kind: 'modal_probe',
                note: 'clicked ' + target,
                modal_exists: $modal.length ? 'yes (' + $modal.length + ')' : 'NO - not in DOM',
                modal_plugin: (typeof $.fn.modal === 'function') ? 'present' : 'MISSING',
                bootstrap_ver: (window.bootstrap && window.bootstrap.Modal)
                    ? 'bootstrap5-object-present' : 'no-bs5-object',
                modal_opened: $modal.hasClass('in') || $modal.hasClass('show')
                    ? 'YES' : 'no',
                modal_display: $modal.length && window.getComputedStyle
                    ? window.getComputedStyle($modal.get(0)).display : '',
                // MA-002 v5: where is it actually drawn, and is it visible?
                modal_rect: (function () {
                    if (!$modal.length) { return ''; }
                    var r = $modal.get(0).getBoundingClientRect();
                    var cs = window.getComputedStyle($modal.get(0));
                    return Math.round(r.left) + ',' + Math.round(r.top) + ' ' +
                           Math.round(r.width) + 'x' + Math.round(r.height) +
                           ' op:' + cs.opacity + ' vis:' + cs.visibility +
                           ' z:' + cs.zIndex + ' pos:' + cs.position;
                })(),
                dialog_rect: (function () {
                    var d = $modal.find('.modal-dialog').get(0);
                    if (!d) { return 'NO .modal-dialog'; }
                    var r = d.getBoundingClientRect();
                    var cs = window.getComputedStyle(d);
                    return Math.round(r.left) + ',' + Math.round(r.top) + ' ' +
                           Math.round(r.width) + 'x' + Math.round(r.height) +
                           ' op:' + cs.opacity + ' tr:' + cs.transform;
                })(),
                modal_parent: $modal.parent().get(0)
                    ? ($modal.parent().get(0).tagName || '').toLowerCase() +
                      ($modal.parent().get(0).id ? '#' + $modal.parent().get(0).id : '')
                    : '',
                viewport: window.innerWidth + 'x' + window.innerHeight,
                jq_count: (window.jQuery && window.jQuery.fn && window.jQuery.fn.jquery)
                    ? window.jQuery.fn.jquery : 'unknown',
                backdrop_count: $('.modal-backdrop').length
            });
        }, 700);
    });

    // Bubble phase at document level: runs after every other handler.
    document.addEventListener('mousedown', function (e) {
        if (reported || !pending) { return; }
        reported = true;
        pending.default_prevented = e.defaultPrevented ? 'YES' : 'no';
        send(pending);
        pending = null;
    }, false);
}());
</script>
