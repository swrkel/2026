<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Help Guide')</title>
    <style>
        :root{--hg-blue:#2563eb;--hg-cyan:#0891b2;--hg-ink:#0f172a;--hg-muted:#64748b;--hg-line:#dbe7f3;--hg-bg:#f4f8fc;--hg-card:#fff;--hg-soft:#eef6ff;--hg-green:#16a34a}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:linear-gradient(180deg,#edf6ff 0,#f8fbff 240px,#f4f8fc 100%);color:var(--hg-ink);font-family:Inter,"Segoe UI",Arial,sans-serif;min-height:100vh}
        a{color:inherit}.hg-top{background:rgba(255,255,255,.96);border-bottom:1px solid rgba(219,231,243,.95);position:sticky;top:0;z-index:50;backdrop-filter:blur(14px);box-shadow:0 6px 24px rgba(15,23,42,.05)}
        .hg-top-inner{max-width:1480px;margin:auto;padding:13px 24px;display:flex;align-items:center;justify-content:space-between;gap:18px}.hg-brand-wrap{display:flex;align-items:center;gap:11px;min-width:0}.hg-logo{width:42px;height:42px;border-radius:13px;background:linear-gradient(135deg,#2563eb,#06b6d4);box-shadow:0 9px 20px rgba(37,99,235,.23);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:20px}.hg-brand{font-size:20px;font-weight:850;color:#102033;text-decoration:none;letter-spacing:-.02em}.hg-brand-sub{display:block;font-size:11px;color:#718096;font-weight:650;margin-top:2px}.hg-system-link{display:inline-flex;align-items:center;gap:7px;border:1px solid var(--hg-line);background:#fff;border-radius:10px;padding:9px 13px;text-decoration:none;font-size:13px;font-weight:750;color:#334155}.hg-system-link:hover{background:var(--hg-soft);border-color:#bfd5ef;color:var(--hg-blue)}
        .hg-search-zone{max-width:1480px;margin:0 auto;padding:20px 24px 0}.hg-search-card{background:linear-gradient(135deg,#ffffff 0,#f8fbff 58%,#eaf5ff 100%);border:1px solid #cfe0f3;border-radius:20px;padding:18px 20px;box-shadow:0 16px 38px rgba(15,23,42,.08);position:relative}.hg-search-label{font-size:12px;color:#2563eb;font-weight:850;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px}.hg-search-row{display:flex;gap:10px;align-items:center}.hg-search-box{position:relative;flex:1}.hg-search-icon{position:absolute;left:15px;top:50%;transform:translateY(-50%);font-size:18px;color:#64748b;pointer-events:none}.hg-global-search{width:100%;height:50px;border:1px solid #c9dbef;border-radius:14px;background:#fff;padding:0 48px 0 45px;font-size:15px;color:#0f172a;outline:none;box-shadow:0 5px 14px rgba(15,23,42,.04)}.hg-global-search:focus{border-color:#2563eb;box-shadow:0 0 0 4px rgba(37,99,235,.11),0 8px 20px rgba(15,23,42,.06)}.hg-search-clear{position:absolute;right:12px;top:50%;transform:translateY(-50%);border:0;background:#eef2f7;width:28px;height:28px;border-radius:50%;cursor:pointer;color:#64748b;font-size:16px;display:none}.hg-search-hint{font-size:12px;color:#718096;margin-top:8px}.hg-search-results{display:none;position:absolute;left:20px;right:20px;top:90px;background:#fff;border:1px solid #cfe0f3;border-radius:14px;box-shadow:0 24px 50px rgba(15,23,42,.16);z-index:60;max-height:420px;overflow:auto;padding:7px}.hg-search-result{display:block;text-decoration:none;border-radius:10px;padding:11px 12px;color:#1e293b}.hg-search-result:hover,.hg-search-result.active{background:#eef6ff}.hg-search-result-top{display:flex;gap:8px;align-items:center}.hg-search-type{display:inline-flex;border-radius:999px;padding:3px 8px;font-size:10px;font-weight:850;text-transform:uppercase;letter-spacing:.05em;background:#dbeafe;color:#1d4ed8}.hg-search-result-title{font-weight:800;font-size:13px}.hg-search-result-sub{font-size:12px;color:#64748b;margin-top:4px;line-height:1.4}.hg-search-empty{padding:18px;text-align:center;color:#64748b;font-size:13px}
        .hg-wrap{max-width:1480px;margin:20px auto 40px;padding:0 24px}.hg-page-hero{background:#fff;border:1px solid #dbe7f3;border-radius:18px;padding:22px 24px;margin-bottom:20px;box-shadow:0 14px 34px rgba(15,23,42,.07);display:flex;justify-content:space-between;gap:18px;align-items:flex-start}.hg-title{font-size:28px;line-height:1.15;font-weight:850;margin:0 0 7px;letter-spacing:-.025em}.hg-sub{color:#67738a;margin:0;line-height:1.55;font-size:13px}.hg-card{background:#fff;border:1px solid #dbe7f3;border-radius:17px;box-shadow:0 13px 32px rgba(15,23,42,.07);padding:19px}.hg-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(245px,1fr));gap:16px}.hg-module{display:block;color:inherit;text-decoration:none;min-height:150px;transition:.18s;position:relative;overflow:hidden}.hg-module:before{content:"";position:absolute;left:0;right:0;top:0;height:4px;background:linear-gradient(90deg,#2563eb,#06b6d4)}.hg-module:hover{transform:translateY(-3px);box-shadow:0 19px 38px rgba(15,23,42,.11);border-color:#bcd3ec}.hg-icon{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#e8f1ff,#e6fbff);display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:13px;color:#2563eb;font-weight:900}.hg-module h3{font-size:16px;margin:0 0 7px}.hg-muted{color:#758198;font-size:13px;line-height:1.55}.hg-badge{display:inline-block;border-radius:999px;padding:4px 9px;font-size:11px;background:#eef7ef;color:#29733a;font-weight:800}.hg-count{position:absolute;right:15px;top:15px;border-radius:999px;padding:5px 9px;background:#eff6ff;color:#1d4ed8;font-size:11px;font-weight:850}.hg-btn{border:1px solid #2563eb;border-radius:10px;padding:10px 15px;font-weight:750;cursor:pointer;background:#2563eb;color:#fff;text-decoration:none;display:inline-flex;align-items:center;gap:7px}.hg-btn:hover{background:#1d4ed8}.hg-btn.secondary{background:#fff;color:#334155;border-color:#dbe7f3}.hg-alert{padding:12px 14px;border-radius:10px;margin-bottom:16px;background:#fff3cd;border:1px solid #ffe69c;color:#664d03}.hg-empty{text-align:center;padding:48px 20px;color:#69758b}.hg-language-bar{display:flex;justify-content:flex-end;align-items:end;gap:8px;flex-wrap:wrap;margin:0}.hg-language-select label{display:block;font-size:11px;font-weight:800;color:#68758b;margin-bottom:4px}.hg-language-select select{min-width:190px;height:40px;border:1px solid #d9e0ea;border-radius:10px;background:#fff;padding:0 10px}.hg-language-default{margin:0}.hg-section-title{font-size:19px;font-weight:850;margin:28px 0 6px}.hg-section-sub{font-size:13px;color:#718096;margin:0 0 15px}.hg-article-card{display:flex;gap:14px;align-items:flex-start;text-decoration:none;color:inherit;transition:.16s}.hg-article-card:hover{transform:translateY(-2px);border-color:#bdd4ee;box-shadow:0 16px 34px rgba(15,23,42,.09)}.hg-article-bullet{width:42px;height:42px;min-width:42px;border-radius:12px;background:#eef6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:900}.hg-article-card h3{font-size:15px;margin:0 0 5px;line-height:1.35}.hg-meta{font-size:11px;color:#64748b;font-weight:750;margin-top:9px}.hg-content{line-height:1.75;color:#27364a}.hg-content img{max-width:100%;height:auto;border-radius:12px}.hg-content table{max-width:100%;display:block;overflow:auto;border-collapse:collapse}.hg-content table td,.hg-content table th{border:1px solid #dbe7f3;padding:8px}.hg-content pre{white-space:pre-wrap;background:#0f172a;color:#e2e8f0;border-radius:12px;padding:15px;overflow:auto}.hg-content blockquote{border-left:4px solid #2563eb;margin-left:0;padding:10px 15px;background:#f8fbff;color:#475569;border-radius:0 10px 10px 0}.hg-back{color:#335b88;text-decoration:none;font-size:13px;font-weight:750}.hg-back:hover{color:#2563eb}.hg-filter-hidden{display:none!important}
        .hg-scroll-controls{position:fixed;right:18px;bottom:28px;z-index:70;display:flex;flex-direction:column;align-items:center;gap:7px}.hg-scroll-btn{width:42px;height:42px;border:1px solid #cfe0f3;border-radius:13px;background:#fff;color:#2563eb;box-shadow:0 10px 25px rgba(15,23,42,.13);font-size:19px;font-weight:900;cursor:pointer}.hg-scroll-btn:hover{background:#eef6ff}.hg-scroll-rail{width:5px;height:72px;background:#dbe7f3;border-radius:999px;position:relative;overflow:hidden}.hg-scroll-progress{position:absolute;left:0;top:0;width:100%;height:0;background:linear-gradient(180deg,#2563eb,#06b6d4);border-radius:999px}
        @media(max-width:760px){.hg-wrap,.hg-top-inner,.hg-search-zone{padding-left:12px;padding-right:12px}.hg-search-results{left:12px;right:12px}.hg-title{font-size:23px}.hg-page-hero{display:block;padding:18px}.hg-language-bar{justify-content:flex-start;margin-top:14px}.hg-grid{grid-template-columns:1fr}.hg-system-link span{display:none}.hg-scroll-controls{right:9px;bottom:16px}.hg-search-card{padding:14px}.hg-global-search{font-size:14px}}
    </style>
    @stack('styles')
</head>
<body>
<header class="hg-top">
    <div class="hg-top-inner">
        <a class="hg-brand-wrap" href="{{ route('helpguide.index', ['lang' => $selectedLanguage ?? null]) }}">
            <span class="hg-logo">?</span>
            <span><span class="hg-brand">Help Guide</span><span class="hg-brand-sub">Guidance for your system</span></span>
        </a>
        <a class="hg-system-link" href="{{ url('/home') }}"><span>Back to System</span> →</a>
    </div>
</header>

<div class="hg-search-zone">
    <div class="hg-search-card">
        <div class="hg-search-label">Find help quickly</div>
        <div class="hg-search-row">
            <div class="hg-search-box">
                <span class="hg-search-icon">⌕</span>
                <input id="hg-global-search" class="hg-global-search" type="search"
                       placeholder="Type to search modules and help articles…" autocomplete="off"
                       data-url="{{ route('helpguide.search') }}" data-lang="{{ $selectedLanguage ?? 'en' }}">
                <button type="button" id="hg-search-clear" class="hg-search-clear" aria-label="Clear search">×</button>
            </div>
        </div>
        <div class="hg-search-hint">Results filter automatically as you type. Press ↑ or ↓ to move through suggestions and Enter to open.</div>
        <div id="hg-search-results" class="hg-search-results"></div>
    </div>
</div>

<main class="hg-wrap">@yield('content')</main>

<div class="hg-scroll-controls" aria-label="Page scroll controls">
    <button class="hg-scroll-btn" type="button" id="hg-scroll-up" title="Go to top">↑</button>
    <div class="hg-scroll-rail"><div class="hg-scroll-progress" id="hg-scroll-progress"></div></div>
    <button class="hg-scroll-btn" type="button" id="hg-scroll-down" title="Go to bottom">↓</button>
</div>

<script>
(function () {
    var input = document.getElementById('hg-global-search');
    var clear = document.getElementById('hg-search-clear');
    var results = document.getElementById('hg-search-results');
    var timer = null;
    var controller = null;
    var activeIndex = -1;

    function localFilter(value) {
        var q = value.trim().toLowerCase();
        document.querySelectorAll('[data-hg-filter-text]').forEach(function (node) {
            var text = (node.getAttribute('data-hg-filter-text') || '').toLowerCase();
            node.classList.toggle('hg-filter-hidden', q !== '' && text.indexOf(q) === -1);
        });
        document.querySelectorAll('[data-hg-filter-empty]').forEach(function (emptyNode) {
            var group = emptyNode.getAttribute('data-hg-filter-empty');
            var visible = document.querySelectorAll('[data-hg-filter-group="' + group + '"]:not(.hg-filter-hidden)').length;
            emptyNode.style.display = (q !== '' && visible === 0) ? 'block' : 'none';
        });
    }

    function closeResults() {
        results.style.display = 'none';
        results.innerHTML = '';
        activeIndex = -1;
    }

    function render(items) {
        results.innerHTML = '';
        activeIndex = -1;
        if (!items.length) {
            results.innerHTML = '<div class="hg-search-empty">No matching help was found.</div>';
            results.style.display = 'block';
            return;
        }
        items.forEach(function (item) {
            var a = document.createElement('a');
            a.className = 'hg-search-result';
            a.href = item.url;
            var sub = item.summary || '';
            if (item.module) { sub = item.module + (sub ? ' — ' + sub : ''); }
            a.innerHTML = '<div class="hg-search-result-top"><span class="hg-search-type"></span><span class="hg-search-result-title"></span></div><div class="hg-search-result-sub"></div>';
            a.querySelector('.hg-search-type').textContent = item.type || 'Help';
            a.querySelector('.hg-search-result-title').textContent = item.title || '';
            a.querySelector('.hg-search-result-sub').textContent = sub;
            results.appendChild(a);
        });
        results.style.display = 'block';
    }

    function remoteSearch(value) {
        var q = value.trim();
        if (!q) { closeResults(); return; }
        if (controller) { controller.abort(); }
        controller = new AbortController();
        var url = input.getAttribute('data-url') + '?q=' + encodeURIComponent(q) + '&lang=' + encodeURIComponent(input.getAttribute('data-lang') || 'en');
        fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}, signal:controller.signal})
            .then(function (r) { return r.json(); })
            .then(function (data) { render(Array.isArray(data.results) ? data.results : []); })
            .catch(function (e) { if (e.name !== 'AbortError') { closeResults(); } });
    }

    input.addEventListener('input', function () {
        var value = input.value;
        clear.style.display = value ? 'block' : 'none';
        localFilter(value);
        window.clearTimeout(timer);
        timer = window.setTimeout(function () { remoteSearch(value); }, 180);
    });

    input.addEventListener('keydown', function (e) {
        var links = results.querySelectorAll('.hg-search-result');
        if (!links.length || results.style.display === 'none') { return; }
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (activeIndex >= 0) { links[activeIndex].classList.remove('active'); }
            activeIndex = e.key === 'ArrowDown'
                ? Math.min(activeIndex + 1, links.length - 1)
                : Math.max(activeIndex - 1, 0);
            links[activeIndex].classList.add('active');
            links[activeIndex].scrollIntoView({block:'nearest'});
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            window.location.href = links[activeIndex].href;
        } else if (e.key === 'Escape') {
            closeResults();
        }
    });

    clear.addEventListener('click', function () {
        input.value = '';
        clear.style.display = 'none';
        localFilter('');
        closeResults();
        input.focus();
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.hg-search-card')) { closeResults(); }
    });

    document.getElementById('hg-scroll-up').addEventListener('click', function () {
        window.scrollTo({top:0,behavior:'smooth'});
    });
    document.getElementById('hg-scroll-down').addEventListener('click', function () {
        window.scrollTo({top:document.documentElement.scrollHeight,behavior:'smooth'});
    });

    function updateProgress() {
        var max = document.documentElement.scrollHeight - window.innerHeight;
        var pct = max > 0 ? Math.max(0, Math.min(100, (window.scrollY / max) * 100)) : 0;
        document.getElementById('hg-scroll-progress').style.height = pct + '%';
    }
    window.addEventListener('scroll', updateProgress, {passive:true});
    window.addEventListener('resize', updateProgress);
    updateProgress();
})();
</script>
@stack('scripts')
</body>
</html>
