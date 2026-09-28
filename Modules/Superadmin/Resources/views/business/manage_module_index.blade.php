@extends('layouts.app')
@section('title', 'Modules — ' . $business->name)

@section('content')
{{--
 | Module index - MA 007 split, level A search.
 |
 | Type a module name OR a page name. Searching "dip" surfaces Petro General,
 | Petro and Petro Direct, and shows which of their pages matched - so you can
 | find a permission without knowing which module owns it.
 |
 | All filtering happens in the browser. A page load on this installation costs
 | 2 to 3 seconds, so a server round-trip per keystroke would be unusable.
--}}

<style>
.mix-wrap {
    --mix-ink: #1b2430;
    --mix-muted: #6b7785;
    --mix-line: #e3e8ee;
    --mix-bg: #f7f9fb;
    --mix-on: #0f766e;
    --mix-on-soft: #ecfdf5;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: var(--mix-ink);
    padding-bottom: 40px;
}

.mix-bar {
    position: sticky;
    top: 0;
    z-index: 900;
    background: #fff;
    border-bottom: 1px solid var(--mix-line);
    box-shadow: 0 1px 3px rgba(27, 36, 48, .06);
    padding: 14px 20px;
    margin: -15px -15px 20px;
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
}
.mix-id { min-width: 200px; }
.mix-id h2 { margin: 0; font-size: 19px; font-weight: 650; letter-spacing: -.01em; }
.mix-id p { margin: 2px 0 0; font-size: 12.5px; color: var(--mix-muted); }

.mix-search { flex: 1 1 260px; min-width: 200px; position: relative; }
.mix-search input {
    width: 100%;
    padding: 9px 12px 9px 34px;
    border: 1px solid var(--mix-line);
    border-radius: 7px;
    font-size: 14px;
    background: var(--mix-bg);
    transition: border-color .15s, background .15s;
}
.mix-search input:focus { outline: none; border-color: var(--mix-on); background: #fff; }
.mix-search svg { position: absolute; left: 11px; top: 11px; width: 14px; height: 14px; fill: var(--mix-muted); }

.mix-count { font-size: 13px; color: var(--mix-muted); white-space: nowrap; font-variant-numeric: tabular-nums; }
.mix-count b { color: var(--mix-on); font-weight: 650; }


.mix-tabs { display: flex; gap: 4px; }
.mix-tab {
    border: 1px solid var(--mix-line); background: #fff; color: var(--mix-muted);
    padding: 7px 13px; border-radius: 7px; font-size: 13px; cursor: pointer;
}
.mix-tab:hover { border-color: var(--mix-on); color: var(--mix-on); }
.mix-tab.is-on { background: var(--mix-on-soft); border-color: var(--mix-on); color: var(--mix-on); font-weight: 620; }
.mix-dot {
    display: inline-block; width: 7px; height: 7px; border-radius: 50%;
    background: var(--mix-on); margin-right: 7px; vertical-align: 1px;
}
.mix-dot.is-off { background: #98a2b3; }

/*
 | State on the card itself, not only in the dot.
 |
 | With 129 cards a 7px dot is too small to scan. The card carries the state:
 | a light green wash when the module is enabled, grey when it is not.
 |
 | Text stays dark in both. Colouring the text as well as the background is
 | what usually makes this kind of thing hard to read - #1b2430 on #f0fdf9 is
 | roughly 14:1, well above the 4.5:1 the guidelines ask for, and the grey card
 | keeps its heading at full strength rather than fading it out.
 */
.mix-card.is-on {
    background: #f0fdf9;
    border-color: #a7f3d0;
}
.mix-card.is-on:hover,
.mix-card.is-on:focus-visible {
    background: #e6fbf4;
    border-color: var(--mix-on);
}

.mix-card.is-off {
    background: #f4f5f7;
    border-color: #dfe3e8;
}
.mix-card.is-off:hover,
.mix-card.is-off:focus-visible {
    background: #eceef1;
    border-color: #98a2b3;
}
.mix-card.is-off h3 { color: var(--mix-ink); }

/* A word, because a colour alone is easy to misread. */
.mix-state {
    font-size: 10.5px;
    font-weight: 650;
    letter-spacing: .04em;
    text-transform: uppercase;
    padding: 2px 7px;
    border-radius: 4px;
    white-space: nowrap;
    flex: none;
}
.mix-state.is-on  { color: #065f46; background: #d1fae5; }
.mix-state.is-off { color: #475467; background: #e4e7ec; }

.mix-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 10px;
}
.mix-card {
    display: block;
    background: #fff;
    border: 1px solid var(--mix-line);
    border-radius: 9px;
    padding: 13px 15px;
    text-decoration: none;
    color: inherit;
    transition: border-color .15s, box-shadow .15s, transform .12s;
}
.mix-card:hover,
.mix-card:focus-visible {
    border-color: var(--mix-on);
    box-shadow: 0 2px 8px rgba(15, 118, 110, .1);
    text-decoration: none;
    color: inherit;
    transform: translateY(-1px);
}
.mix-card-meta { margin-top: 7px; }
.mix-card-top { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; }
.mix-card h3 { margin: 0; font-size: 14.5px; font-weight: 620; line-height: 1.25; }
.mix-n {
    font-size: 12px;
    color: var(--mix-on);
    background: var(--mix-on-soft);
    border-radius: 20px;
    padding: 2px 9px;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
    flex: none;
}
.mix-n.is-zero { color: var(--mix-muted); background: var(--mix-bg); }
.mix-hits { margin: 7px 0 0; font-size: 12px; color: var(--mix-muted); line-height: 1.5; display: none; }
.mix-hits b { color: var(--mix-ink); font-weight: 600; }

.mix-nomatch { padding: 40px; text-align: center; color: var(--mix-muted); font-size: 14px; display: none; }
.mix-hint { font-size: 13px; color: var(--mix-muted); margin: 0 0 16px; }

@media (max-width: 640px) {
    .mix-bar { gap: 12px; padding: 12px 15px; }
    .mix-id { min-width: 100%; }
}
@media (prefers-reduced-motion: reduce) {
    .mix-card { transition: none; }
    .mix-card:hover { transform: none; }
}
</style>

<div class="mix-wrap">

    <div class="mix-bar">
        <div class="mix-id">
            <h2>Modules</h2>
            <p>{{ $business->name }}</p>
        </div>

        <div class="mix-search">
            <svg viewBox="0 0 16 16"><path d="M11.7 10.3a6 6 0 1 0-1.4 1.4l3 3a1 1 0 0 0 1.4-1.4zM7 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/></svg>
            <input type="search" id="mix-filter" placeholder="Search a module, or a page inside one…" autocomplete="off" autofocus>
        </div>

        {{-- All / Enabled / Disabled. With 129 modules there was previously no
             way to see which were switched off for this business. --}}
        <div class="mix-tabs">
            <button type="button" class="mix-tab is-on" data-state="all">All</button>
            <button type="button" class="mix-tab" data-state="on">Enabled</button>
            <button type="button" class="mix-tab" data-state="off">Disabled</button>
        </div>

        <div class="mix-count">
            <b id="mix-shown">{{ count($modules) }}</b> of {{ count($modules) }} modules
        </div>
    </div>

    <p class="mix-hint">
        {{ number_format($totalPages) }} pages and tabs across {{ count($modules) }} modules.
        Searching matches page names too — type <b>dip</b> to find every module with a dip chart.
    </p>

    <div class="mix-grid" id="mix-grid">
        @foreach($modules as $m)
            <a class="mix-card {{ ($m['enabled'] ?? true) ? 'is-on' : 'is-off' }}"
               href="{{ action('\Modules\Superadmin\Http\Controllers\BusinessController@manageModule', [$business->id, $m['key']]) }}"
               data-find="{{ $m['find'] }}"
               data-on="{{ ($m['enabled'] ?? true) ? '1' : '0' }}"
               data-pages="{{ implode('|', $m['pages']) }}">
                <div class="mix-card-top">
                    <h3>{{ $m['title'] }}</h3>
                    <span class="mix-state {{ ($m['enabled'] ?? true) ? 'is-on' : 'is-off' }}">
                        {{ ($m['enabled'] ?? true) ? 'On' : 'Off' }}
                    </span>
                </div>
                <div class="mix-card-meta">
                    <span class="mix-n {{ $m['count'] === 0 ? 'is-zero' : '' }}">
                        {{ $m['count'] === 0 ? 'no pages' : $m['count'] . ($m['tabs'] ? ' (' . $m['tabs'] . ' tab' . ($m['tabs'] > 1 ? 's' : '') . ')' : '') }}
                    </span>
                </div>
                <p class="mix-hits"></p>
            </a>
        @endforeach
    </div>

    <div class="mix-nomatch" id="mix-nomatch">
        Nothing matches that. Try part of a page name, like <b>dip</b> or <b>settlement</b>.
    </div>

</div>

<script>
(function () {
    var grid   = document.getElementById('mix-grid');
    var filter = document.getElementById('mix-filter');
    if (!grid || !filter) { return; }

    var cards  = Array.prototype.slice.call(grid.querySelectorAll('.mix-card'));
    var shown  = document.getElementById('mix-shown');
    var none   = document.getElementById('mix-nomatch');

    var state = 'all';
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.mix-tab'));
    tabs.forEach(function (t) {
        t.addEventListener('click', function () {
            tabs.forEach(function (x) { x.classList.remove('is-on'); });
            t.classList.add('is-on');
            state = t.getAttribute('data-state');
            apply();
        });
    });

    function apply() {
        var q = filter.value.trim().toLowerCase();
        var visible = 0;

        cards.forEach(function (card) {
            var on = card.getAttribute('data-on') === '1';
            var okState = state === 'all' || (state === 'on' && on) || (state === 'off' && !on);
            var match = okState && (!q || card.getAttribute('data-find').indexOf(q) !== -1);
            card.style.display = match ? '' : 'none';
            if (!match) { return; }
            visible++;

            var hits = card.querySelector('.mix-hits');
            if (!q) { hits.style.display = 'none'; return; }

            // Which pages inside this module matched, so it is clear WHY the
            // module is in the results.
            var pages = (card.getAttribute('data-pages') || '').split('|');
            var found = pages.filter(function (p) {
                return p && p.toLowerCase().indexOf(q) !== -1;
            });

            if (found.length) {
                var list = found.slice(0, 3).join(', ');
                if (found.length > 3) { list += ' and ' + (found.length - 3) + ' more'; }
                hits.innerHTML = '<b>' + found.length + '</b> matching: ' + list;
                hits.style.display = 'block';
            } else {
                hits.style.display = 'none';
            }
        });

        shown.textContent = visible;
        none.style.display = visible ? 'none' : 'block';
    }

    filter.addEventListener('input', apply);
    filter.addEventListener('keydown', function (e) {
        // Enter opens the only remaining result - the common case after typing
        // enough to narrow it to one.
        if (e.key !== 'Enter') { return; }
        var open = cards.filter(function (c) { return c.style.display !== 'none'; });
        if (open.length === 1) { window.location = open[0].href; }
    });
}());
</script>
@endsection
