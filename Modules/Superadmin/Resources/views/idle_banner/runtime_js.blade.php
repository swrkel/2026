(function () {
    'use strict';

    if (window.__saIdleBannerRuntimeInstalled) {
        return;
    }
    window.__saIdleBannerRuntimeInstalled = true;

    var payloadUrl = @json($payloadUrl);
    var config = null;
    var idleTimer = null;
    var rotateTimer = null;
    var overlay = null;
    var currentIndex = 0;

    // 8051: idle time is wall-clock time, not only foreground-tab time.
    // Start counting as soon as this page runtime is installed. Switching to
    // another tab/window/browser must NOT reset or pause the idle clock.
    var lastActivityAt = Date.now();

    function clearTimeoutSafe(timer) {
        if (timer) {
            window.clearTimeout(timer);
        }
    }

    function hideOverlay() {
        clearTimeoutSafe(rotateTimer);
        rotateTimer = null;

        if (overlay && overlay.parentNode) {
            overlay.parentNode.removeChild(overlay);
        }

        overlay = null;
        currentIndex = 0;
    }

    function validConfig() {
        return !!(
            config &&
            config.enabled &&
            Array.isArray(config.banners) &&
            config.banners.length
        );
    }

    function idleDelayMs() {
        if (!validConfig()) {
            return 0;
        }

        // 8051: idle threshold is now configured in whole seconds.
        // Keep idle_minutes as a compatibility fallback during rolling deploys.
        var seconds = parseFloat(config.idle_seconds || 0);
        if (!isFinite(seconds) || seconds <= 0) {
            var minutes = parseFloat(config.idle_minutes || 0);
            seconds = isFinite(minutes) && minutes > 0 ? minutes * 60 : 0;
        }

        if (!isFinite(seconds) || seconds <= 0) {
            return 0;
        }

        return Math.min(2147483647, Math.max(1000, seconds * 1000));
    }

    function idleElapsedMs() {
        return Math.max(0, Date.now() - lastActivityAt);
    }

    function checkIdleNow() {
        clearTimeoutSafe(idleTimer);
        idleTimer = null;

        var delayMs = idleDelayMs();
        if (!delayMs) {
            return;
        }

        var remainingMs = delayMs - idleElapsedMs();

        if (remainingMs <= 0) {
            // Do not postpone the banner just because the page/tab is hidden.
            // The overlay may be prepared while hidden and will therefore be
            // present immediately when the user returns. If browser throttling
            // delayed this callback, visibility/focus/pageshow re-check below
            // uses Date.now() and catches up immediately.
            showOverlay();
            return;
        }

        idleTimer = window.setTimeout(checkIdleNow, remainingMs);
    }

    function scheduleIdle() {
        checkIdleNow();
    }

    function overlayIsVisible() {
        return !!(overlay && overlay.parentNode);
    }

    // 8051: Before the idle screen appears, normal interaction resets the
    // idle clock. Once the idle screen is visible, the VERY FIRST deliberate
    // activity must restore the exact working page underneath it.
    //
    // Mouse movement is treated as a wake action too. Click/key/touch wake
    // events are consumed while the overlay is visible so that the wake-up
    // action cannot also activate a banner link, submit a form, type into an
    // already-focused field, or require a second click to get back to work.
    function normalActivity() {
        if (overlayIsVisible()) {
            return;
        }

        lastActivityAt = Date.now();
        scheduleIdle();
    }

    function wakeFromIdle(event) {
        if (!overlayIsVisible()) {
            lastActivityAt = Date.now();
            scheduleIdle();
            return;
        }

        lastActivityAt = Date.now();

        if (event) {
            try {
                if (event.cancelable) {
                    event.preventDefault();
                }
            } catch (ignore) {}

            try {
                event.stopPropagation();
                if (event.stopImmediatePropagation) {
                    event.stopImmediatePropagation();
                }
            } catch (ignore) {}
        }

        hideOverlay();
        scheduleIdle();
    }

    function wakeOnMouseMove() {
        if (overlayIsVisible()) {
            lastActivityAt = Date.now();
            hideOverlay();
            scheduleIdle();
            return;
        }

        normalActivity();
    }

    function ensureOverlay() {
        if (overlay) {
            return overlay;
        }

        overlay = document.createElement('div');
        overlay.id = 'sa-idle-banner-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Banner');
        overlay.style.cssText = [
            'position:fixed',
            'inset:0',
            'z-index:2147483000',
            'background:rgba(0,0,0,.94)',
            'display:flex',
            'align-items:center',
            'justify-content:center',
            'overflow:hidden',
            'cursor:default'
        ].join(';');

        var shell = document.createElement('div');
        shell.className = 'sa-idle-banner-shell';
        shell.style.cssText = 'position:relative;width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:20px;box-sizing:border-box;';

        var link = document.createElement('a');
        link.className = 'sa-idle-banner-link';
        link.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;text-decoration:none;outline:none;';

        var image = document.createElement('img');
        image.className = 'sa-idle-banner-image';
        image.alt = '';
        image.style.cssText = 'display:block;width:100%;height:100%;object-fit:contain;border:0;box-shadow:none;';

        var caption = document.createElement('div');
        caption.className = 'sa-idle-banner-caption';
        caption.style.cssText = 'position:absolute;left:24px;right:24px;bottom:18px;text-align:center;font:600 16px/1.4 Arial,sans-serif;color:#fff;text-shadow:0 1px 4px rgba(0,0,0,.85);pointer-events:none;';

        link.appendChild(image);
        shell.appendChild(link);
        shell.appendChild(caption);
        overlay.appendChild(shell);
        document.body.appendChild(overlay);

        image.addEventListener('error', function () {
            if (!validConfig()) {
                hideOverlay();
                return;
            }

            if (config.banners.length > 1) {
                currentIndex = (currentIndex + 1) % config.banners.length;
                showBanner(currentIndex);
            } else {
                hideOverlay();
                scheduleIdle();
            }
        });

        return overlay;
    }

    function showBanner(index) {
        if (!validConfig()) {
            hideOverlay();
            return;
        }

        var root = ensureOverlay();
        var banner = config.banners[index] || config.banners[0];
        var image = root.querySelector('.sa-idle-banner-image');
        var link = root.querySelector('.sa-idle-banner-link');
        var caption = root.querySelector('.sa-idle-banner-caption');

        var imageSizePercent = parseInt(config.image_size_percent, 10);
        if (!isFinite(imageSizePercent)) {
            imageSizePercent = 90;
        }
        imageSizePercent = Math.max(25, Math.min(100, imageSizePercent));
        link.style.width = imageSizePercent + 'vw';
        link.style.height = imageSizePercent + 'vh';

        image.src = banner.image_url || '';
        image.alt = banner.title || 'Banner';

        caption.textContent = banner.title || '';
        caption.style.display = banner.title ? 'block' : 'none';

        if (banner.link_url) {
            link.setAttribute('href', banner.link_url);
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
            link.style.pointerEvents = 'auto';
        } else {
            link.removeAttribute('href');
            link.removeAttribute('target');
            link.removeAttribute('rel');
            link.style.pointerEvents = 'none';
        }

        clearTimeoutSafe(rotateTimer);
        rotateTimer = null;

        var seconds = parseInt(banner.display_duration, 10);
        if (!isFinite(seconds) || seconds < 1) {
            seconds = 5;
        }

        if (config.banners.length > 1) {
            rotateTimer = window.setTimeout(function () {
                currentIndex = (index + 1) % config.banners.length;
                showBanner(currentIndex);
            }, seconds * 1000);
        }
    }

    function showOverlay() {
        currentIndex = 0;
        showBanner(currentIndex);
    }

    function installActivityListeners() {
        // Switching to another tab/window/browser is NOT activity. Idle time
        // therefore continues using wall-clock time in the background.
        //
        // Mouse movement must wake the screen immediately in ONE movement.
        // Wheel/scroll keep the page active before idle; while the overlay is
        // visible they also wake it immediately.
        document.addEventListener('mousemove', wakeOnMouseMove, {passive: true});
        document.addEventListener('wheel', wakeFromIdle, {capture: true, passive: false});
        document.addEventListener('scroll', wakeFromIdle, {capture: true, passive: true});

        // Capture phase is intentional. The first click/key/touch is strictly a
        // wake-up action and must not fall through to banner links or controls
        // beneath the idle screen. One action restores the working page.
        document.addEventListener('click', wakeFromIdle, true);
        document.addEventListener('keydown', wakeFromIdle, true);
        document.addEventListener('touchstart', wakeFromIdle, {capture: true, passive: false});

        // Background tabs can throttle setTimeout. Re-check elapsed WALL-CLOCK
        // time whenever the page becomes visible/focused again, without treating
        // that state change as user activity.
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                checkIdleNow();
            }
        });

        window.addEventListener('focus', function () {
            checkIdleNow();
        });

        window.addEventListener('pageshow', function () {
            checkIdleNow();
        });
    }

    function loadConfiguration() {
        if (!window.fetch) {
            return;
        }

        window.fetch(payloadUrl, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            cache: 'no-store'
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Banner configuration request failed');
            }
            return response.json();
        })
        .then(function (data) {
            config = data || null;
            scheduleIdle();
        })
        .catch(function () {
            config = null;
        });
    }

    installActivityListeners();
    loadConfiguration();
})();
