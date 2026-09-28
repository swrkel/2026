(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    function ensureErrorBox(form) {
        var box = document.getElementById('erp-login-runtime-error');
        if (box) {
            return box;
        }

        box = document.createElement('div');
        box.id = 'erp-login-runtime-error';
        box.setAttribute('role', 'alert');
        box.style.display = 'none';
        box.style.margin = '0 12px 16px';
        box.style.padding = '12px 14px';
        box.style.border = '1px solid #dc2626';
        box.style.borderRadius = '8px';
        box.style.background = '#fef2f2';
        box.style.color = '#991b1b';
        box.style.fontWeight = '600';
        form.insertBefore(box, form.firstChild);
        return box;
    }

    function showError(box, message) {
        box.textContent = message || 'Login could not be completed.';
        box.style.display = 'block';
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function setSubmitting(form, submitting) {
        var button = form.querySelector('button[type="submit"], input[type="submit"]');
        if (!button) {
            return;
        }

        button.disabled = submitting;
        if (button.tagName === 'BUTTON') {
            if (!button.dataset.erpOriginalText) {
                button.dataset.erpOriginalText = button.textContent;
            }
            button.textContent = submitting ? 'Signing In…' : button.dataset.erpOriginalText;
        }
    }

    function showVerificationModal() {
        var modal = document.getElementById('self_verification_modal');
        if (!modal) {
            return false;
        }

        if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
            window.jQuery(modal).modal('show');
            return true;
        }

        modal.style.display = 'block';
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        return true;
    }

    ready(function () {
        var form = document.getElementById('login_form');
        if (!form || form.dataset.erpStableLoginBound === '1') {
            return;
        }

        form.dataset.erpStableLoginBound = '1';
        var errorBox = ensureErrorBox(form);

        // Remove every older delegated login handler. Other page handlers are
        // left untouched. The native handler below is independent of jQuery.
        if (window.jQuery) {
            window.jQuery(document).off('submit', 'form#login_form');
            window.jQuery(form).off('submit');
        }

        form.addEventListener('submit', function (event) {
            if (!window.fetch || !window.FormData || !window.AbortController) {
                // Native browser POST remains the guaranteed fallback.
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            errorBox.style.display = 'none';
            setSubmitting(form, true);

            var controller = new AbortController();
            var timeout = window.setTimeout(function () {
                controller.abort();
            }, 30000);

            fetch(window.location.origin + '/login', {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                cache: 'no-store',
                redirect: 'follow',
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    return response.text().then(function (text) {
                        var payload = null;
                        try {
                            payload = text ? JSON.parse(text) : null;
                        } catch (ignore) {
                            payload = null;
                        }

                        return {
                            response: response,
                            text: text,
                            payload: payload
                        };
                    });
                })
                .then(function (result) {
                    var response = result.response;
                    var payload = result.payload;

                    if (payload && payload.status) {
                        if (payload.step === 'verify_step') {
                            if (!showVerificationModal()) {
                                throw new Error('OTP verification is required, but the verification window could not be opened.');
                            }
                            setSubmitting(form, false);
                            return;
                        }

                        var destination = payload.redirect || '/home';
                        window.location.replace(new URL(destination, window.location.origin).href);
                        return;
                    }

                    var message = payload && (payload.msg || payload.message)
                        ? (payload.msg || payload.message)
                        : '';

                    if (!message && response.status === 419) {
                        message = 'The login session expired. Reload the page and try again.';
                    }

                    if (!message && result.text) {
                        var temporary = document.createElement('div');
                        temporary.innerHTML = result.text;
                        message = (temporary.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 600);
                    }

                    if (!message) {
                        message = 'Login failed with HTTP ' + response.status + '.';
                    }

                    throw new Error(message);
                })
                .catch(function (error) {
                    var message = error && error.name === 'AbortError'
                        ? 'The login request timed out after 30 seconds.'
                        : (error && error.message ? error.message : 'Login could not be completed.');

                    showError(errorBox, message);
                    setSubmitting(form, false);
                })
                .finally(function () {
                    window.clearTimeout(timeout);
                });
        }, true);

        if (window.ERP_FORCE_LOGIN_VERIFICATION === true) {
            showVerificationModal();
        }
    });
})();
