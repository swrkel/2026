<div data-sa-idle-banner-runtime="1" style="display:none" aria-hidden="true"></div>
<script>
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
    var lastMoveHandled = 0;

    function clearTimer(ref) {
        if (ref) {
            window.clearTimeout(ref);
        }
    }

    function hideOverlay() {
        clearTimer(rotateTimer);
        rotateTimer = null;
        if (overlay && overlay.parentNode) {
            overlay.parentNode.removeChild(overlay);
        }
        overlay = null;
        currentIndex = 0;
    }

    function scheduleIdle() {
        clearTimer(idleTimer);
        idleTimer = null;

        if (!config || !config.enabled || !config.banners || !config.banners.length) {
            return;
        }

        var minutes = parseFloat(config.idle_minutes || 0);
        if (!isFinite(minutes) || minutes <= 0) {
            return;
        }

        var delayMs = Math.min(2147483647, Math.max(1000, minutes * 60 * 1000));
        idleTimer = window.setTimeout(function () {
            if (document.hidden) {
                scheduleIdle();
                return;
            }
            showOverlay();
        }, delayMs);
    }

    function activity(event) {
        if (event && event.type === 'mousemove') {
            var now = Date.now();
            if (now - lastMoveHandled < 500) {
                return;
            }
            lastMoveHandled = now;
        }

        hideOverlay();
        scheduleIdle();
    }

    function ensureOverlay() {
        if (overlay) {
            return overlay;
        }

        overlay = document.createElement('div');
        overlay.id = 'sa-idle-banner-overlay';
        overlay.setAttribute('role', 'dialog');
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
        link.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;max-width:100%;max-height:100%;text-decoration:none;outline:none;';

        var image = document.createElement('img');
        image.className = 'sa-idle-banner-image';
        image.alt = '';
        image.style.cssText = 'display:block;max-width:100%;max-height:100%;width:auto;height:auto;object-fit:contain;border:0;box-shadow:none;';

        var caption = document.createElement('div');
        caption.className = 'sa-idle-banner-caption';
        caption.style.cssText = 'position:absolute;left:24px;right:24px;bottom:18px;text-align:center;font:600 16px/1.4 Arial,sans-serif;color:#fff;text-shadow:0 1px 4px rgba(0,0,0,.85);pointer-events:none;';

        link.appendChild(image);
        shell.appendChild(link);
        shell.appendChild(caption);
        overlay.appendChild(shell);
        document.body.appendChild(overlay);

        overlay.addEventListener('click', function (event) {
            if (!event.target.closest || !event.target.closest('.sa-idle-banner-link')) {
                activity(event);
            }
        });

        return overlay;
    }

    function showBanner(index) {
        if (!config || !config.banners || !config.banners.length) {
            hideOverlay();
            return;
        }

        var root = ensureOverlay();
        var banner = config.banners[index] || config.banners[0];
        var image = root.querySelector('.sa-idle-banner-image');
        var link = root.querySelector('.sa-idle-banner-link');
        var caption = root.querySelector('.sa-idle-banner-caption');

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

        clearTimer(rotateTimer);
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
        // Mousedown is intentionally excluded so an already-visible banner
        // link can still be clicked. Mouse movement, typing, touch, wheel or
        // scrolling all count as activity and immediately dismiss the viewer.
        ['mousemove', 'keydown', 'touchstart', 'wheel', 'scroll'].forEach(function (name) {
            document.addEventListener(name, activity, {passive: true});
        });

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                activity();
            }
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
</script>
