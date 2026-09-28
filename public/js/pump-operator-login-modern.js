(function () {
    'use strict';

    var config = window.PumpOperatorLoginConfig || {};
    var form = document.getElementById('login_form');
    var input = document.getElementById('passcode');
    var keypad = document.getElementById('key_pad');
    var submitButton = document.getElementById('check_password_btn');
    var dateNode = document.getElementById('pump-current-date');
    var timeNode = document.getElementById('pump-current-time');

    if (!form || !input || !keypad) {
        return;
    }

    // Prevent duplicate listeners when the login script is evaluated more than once
    // by a layout, cached page restore, or partial page loader.
    if (keypad.dataset.pumpOperatorLoginBound === '1') {
        return;
    }
    keypad.dataset.pumpOperatorLoginBound = '1';

    // Multiple listeners attached to the same DOM click must still process it once.
    var processedKeypadEvents = window.__pumpOperatorProcessedKeypadEvents;
    if (!processedKeypadEvents && typeof WeakSet !== 'undefined') {
        processedKeypadEvents = new WeakSet();
        window.__pumpOperatorProcessedKeypadEvents = processedKeypadEvents;
    }

    function appendDigit(digit) {
        if (input.value.length >= Number(input.maxLength || 20)) {
            return;
        }

        input.value += String(digit);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.focus();
    }

    function removeLastDigit() {
        input.value = input.value.slice(0, -1);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.focus();
    }

    function submitForm() {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    keypad.addEventListener('click', function (event) {
        if (processedKeypadEvents) {
            if (processedKeypadEvents.has(event)) {
                return;
            }
            processedKeypadEvents.add(event);
        }

        var button = event.target.closest('button');
        if (!button) {
            return;
        }

        if (button.dataset.key !== undefined) {
            appendDigit(button.dataset.key);
            return;
        }

        if (button.dataset.action === 'backspace') {
            removeLastDigit();
            return;
        }

        if (button.dataset.action === 'submit') {
            submitForm();
        }
    });

    input.addEventListener('input', function () {
        input.value = input.value.replace(/\D+/g, '');
    });

    document.addEventListener('keydown', function (event) {
        if (/^[0-9]$/.test(event.key)) {
            if (document.activeElement !== input) {
                event.preventDefault();
                appendDigit(event.key);
            }
            return;
        }

        if (event.key === 'Backspace' && document.activeElement !== input) {
            event.preventDefault();
            removeLastDigit();
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            submitForm();
        }
    });

    form.addEventListener('submit', function () {
        if (submitButton) {
            submitButton.disabled = true;
            var label = submitButton.querySelector('span');
            if (label && config.loadingText) {
                label.textContent = config.loadingText;
            }
        }
    });

    function updateDateTime() {
        if (!dateNode || !timeNode) {
            return;
        }

        var now = new Date();
        var timezone = config.timezone || 'Asia/Colombo';

        try {
            dateNode.textContent = new Intl.DateTimeFormat('en-US', {
                timeZone: timezone,
                month: '2-digit',
                day: '2-digit',
                year: 'numeric'
            }).format(now).replace(/\//g, '-');

            timeNode.textContent = new Intl.DateTimeFormat('en-GB', {
                timeZone: timezone,
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            }).format(now);
        } catch (error) {
            // Keep the server-rendered values when Intl/timezone support is unavailable.
        }
    }

    updateDateTime();
    window.setInterval(updateDateTime, 30000);
    input.focus();
})();
