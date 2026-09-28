(function () {
    'use strict';

    function all(selector, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(selector));
    }

    function one(selector, root) {
        return (root || document).querySelector(selector);
    }

    function updateClock(clock) {
        var timezone = clock.getAttribute('data-timezone') || 'Asia/Colombo';
        var now = new Date();
        var dateNode = one('[data-pone-clock-date]', clock);
        var timeNode = one('[data-pone-clock-time]', clock);

        try {
            if (dateNode) {
                dateNode.textContent = new Intl.DateTimeFormat('en-US', {
                    timeZone: timezone,
                    month: '2-digit',
                    day: '2-digit',
                    year: 'numeric'
                }).format(now);
            }
            if (timeNode) {
                timeNode.textContent = new Intl.DateTimeFormat('en-GB', {
                    timeZone: timezone,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                }).format(now);
            }
        } catch (error) {
            if (dateNode) dateNode.textContent = now.toLocaleDateString();
            if (timeNode) timeNode.textContent = now.toLocaleTimeString();
        }
    }

    all('[data-pone-clock]').forEach(function (clock) {
        updateClock(clock);
        window.setInterval(function () { updateClock(clock); }, 1000);
    });

    var loginForm = one('[data-pone-operator-login-form]');
    var passcode = one('[data-pone-passcode]');
    var keypad = one('[data-pone-keypad]');

    if (loginForm && passcode && keypad) {
        keypad.addEventListener('click', function (event) {
            var keyButton = event.target.closest('[data-pone-key]');
            var actionButton = event.target.closest('[data-pone-key-action]');

            if (keyButton) {
                event.preventDefault();
                if (passcode.value.length < Number(passcode.maxLength || 20)) {
                    passcode.value += keyButton.getAttribute('data-pone-key') || '';
                    passcode.dispatchEvent(new Event('input', { bubbles: true }));
                }
                passcode.focus();
                return;
            }

            if (!actionButton) return;
            event.preventDefault();
            var action = actionButton.getAttribute('data-pone-key-action');
            if (action === 'backspace') {
                passcode.value = passcode.value.slice(0, -1);
                passcode.dispatchEvent(new Event('input', { bubbles: true }));
                passcode.focus();
            } else if (action === 'submit') {
                if (typeof loginForm.requestSubmit === 'function') loginForm.requestSubmit();
                else loginForm.submit();
            }
        });

        loginForm.addEventListener('submit', function (event) {
            if (!passcode.value.trim()) {
                event.preventDefault();
                passcode.focus();
                return;
            }
            var button = one('[data-pone-login-submit]', loginForm);
            var label = one('[data-pone-login-submit-label]', loginForm);
            if (button) {
                button.disabled = true;
                button.classList.add('is-loading');
            }
            if (label) label.textContent = 'Please wait…';
        });
    }

    all('[data-pone-fullscreen]').forEach(function (button) {
        button.addEventListener('click', function () {
            var root = document.documentElement;
            if (!document.fullscreenElement) {
                var promise = root.requestFullscreen ? root.requestFullscreen() : null;
                if (promise && typeof promise.catch === 'function') promise.catch(function () {});
            } else if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        });
    });

    document.addEventListener('click', function (event) {
        var disabled = event.target.closest('.pone-legacy-dashboard-card.is-disabled');
        if (!disabled) return;
        event.preventDefault();
        var message = disabled.getAttribute('data-disabled-message') || 'This operation is not available at this time.';
        window.alert(message);
    });

    function setDashboardHeight() {
        var dashboard = one('.pone-legacy-dashboard');
        if (!dashboard) return;
        dashboard.style.setProperty('--pone-dashboard-height', window.innerHeight + 'px');
    }

    setDashboardHeight();
    window.addEventListener('resize', setDashboardHeight, { passive: true });
})();
