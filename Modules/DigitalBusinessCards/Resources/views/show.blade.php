@php
  $theme  = $card->themeKey();
  $accent = $card->accent_color ?: '#2F6F62';
  $tel    = $card->phone ? preg_replace('/[^0-9+]/', '', $card->phone) : null;
  $telAlt = $card->phone_alt ? preg_replace('/[^0-9+]/', '', $card->phone_alt) : null;
  $wa     = $tel ? preg_replace('/[^0-9]/', '', $tel) : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $card->fullName() }}{{ $card->company ? ' · '.$card->company : '' }}</title>
<meta name="description" content="{{ $card->role() ?? $card->fullName() }}">
<meta name="theme-color" content="{{ in_array($theme, ['midnight','classic']) ? '#0F1714' : ($theme === 'bold' ? $accent : '#FFFFFF') }}">
<meta property="og:type" content="profile">
<meta property="og:title" content="{{ $card->fullName() }}">
<meta property="og:description" content="{{ $card->role() ?? '' }}">
@if($card->photoUrl())<meta property="og:image" content="{{ $card->photoUrl() }}">@endif

<style>
/* ============================================================ Foundation */
:root{
  --accent:{{ $accent }};
  --sans:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
  --mono:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono",monospace;
}
*,*::before,*::after{box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{
  margin:0;min-height:100dvh;font-family:var(--sans);
  background:var(--bg);color:var(--fg);
  display:flex;justify-content:center;
  padding:var(--pad-y) 20px calc(40px + env(safe-area-inset-bottom));
  -webkit-font-smoothing:antialiased;
}
.sheet{width:100%;max-width:400px}

/* ================================================================ Themes */
/* --- Classic: letterpress stock on a deep pine press sheet ------------ */
body[data-theme="classic"]{
  --bg:radial-gradient(120% 80% at 50% 0%,#1E2E28 0%,#17231F 62%);
  --card-bg:#F7F4EC; --fg:#16211D; --fg-2:#3D4A45; --fg-muted:#6B7B74;
  --rule:#DCD5C6; --radius:3px; --pad-y:clamp(28px,7vw,64px);
  --card-shadow:0 1px 0 rgba(255,255,255,.5) inset,0 18px 40px -18px rgba(0,0,0,.65);
  --chip-bg:#EDE8DC; --chip-fg:#16211D; --outline-fg:#F7F4EC;
  --name-size:clamp(1.9rem,7.5vw,2.3rem); --name-weight:700; --name-track:-.035em;
}
body[data-theme="classic"] .crops{display:block}
body[data-theme="classic"] .card::before{
  content:"";position:absolute;left:0;top:0;bottom:0;width:3px;
  border-radius:3px 0 0 3px;background:var(--accent);
}

/* --- Bold: full-bleed accent band, oversized name --------------------- */
body[data-theme="bold"]{
  --bg:#F2F2F0; --card-bg:#FFFFFF; --fg:#14171A; --fg-2:#3A4046; --fg-muted:#767E86;
  --rule:#E8E8E6; --radius:18px; --pad-y:clamp(0px,0vw,0px);
  --card-shadow:0 24px 60px -24px rgba(0,0,0,.3);
  --chip-bg:#F4F4F2; --chip-fg:#14171A; --outline-fg:#14171A;
  --name-size:clamp(2.2rem,9vw,2.9rem); --name-weight:780; --name-track:-.045em;
}
body[data-theme="bold"]{padding:0 0 calc(40px + env(safe-area-inset-bottom))}
body[data-theme="bold"] .sheet{max-width:440px}
body[data-theme="bold"] .hero{display:block}
body[data-theme="bold"] .card{
  margin:-56px 18px 0;padding-top:26px;position:relative;z-index:2;
}
body[data-theme="bold"] .actions,
body[data-theme="bold"] .qr,
body[data-theme="bold"] .proof{padding:0 18px}
body[data-theme="bold"] .avatar{
  width:78px;height:78px;margin-top:-62px;border:4px solid #fff;
  box-shadow:0 6px 18px -6px rgba(0,0,0,.35);
}

/* --- Minimal: editorial white, hairlines, maximum air ----------------- */
body[data-theme="minimal"]{
  --bg:#FFFFFF; --card-bg:#FFFFFF; --fg:#111111; --fg-2:#444444; --fg-muted:#8A8A8A;
  --rule:#E9E9E9; --radius:0; --pad-y:clamp(44px,11vw,96px);
  --card-shadow:none;
  --chip-bg:transparent; --chip-fg:#111111; --outline-fg:#111111;
  --name-size:clamp(1.75rem,6.5vw,2.05rem); --name-weight:560; --name-track:-.02em;
}
body[data-theme="minimal"] .card{padding:0;border-top:2px solid var(--accent);padding-top:30px}
body[data-theme="minimal"] .avatar{width:54px;height:54px}
body[data-theme="minimal"] .chip{border:1px solid var(--rule)}
body[data-theme="minimal"] .rows{border-top-color:var(--rule)}
body[data-theme="minimal"] .btn--save{border-radius:0}

/* --- Midnight: frosted glass on near-black ---------------------------- */
body[data-theme="midnight"]{
  --bg:radial-gradient(130% 90% at 50% -10%,#1B2027 0%,#0C0E11 60%);
  --card-bg:rgba(255,255,255,.055); --fg:#F2F4F6; --fg-2:#C3C9D0; --fg-muted:#8C949E;
  --rule:rgba(255,255,255,.11); --radius:16px; --pad-y:clamp(32px,8vw,72px);
  --card-shadow:0 1px 0 rgba(255,255,255,.07) inset,0 30px 70px -30px rgba(0,0,0,.9);
  --chip-bg:rgba(255,255,255,.07); --chip-fg:#F2F4F6; --outline-fg:#F2F4F6;
  --name-size:clamp(1.95rem,7.5vw,2.35rem); --name-weight:640; --name-track:-.03em;
}
body[data-theme="midnight"] .card{backdrop-filter:blur(14px);border:1px solid var(--rule)}
body[data-theme="midnight"] .avatar{border-color:var(--rule)}

/* ================================================================= Card */
.frame{position:relative;padding:14px}
.crops{display:none}
.crop{position:absolute;width:16px;height:16px;opacity:.5}
.crop::before,.crop::after{content:"";position:absolute;background:var(--accent)}
.crop::before{width:100%;height:1px;top:0}
.crop::after{width:1px;height:100%;left:0}
.crop--tl{top:0;left:0}
.crop--tr{top:0;right:0;transform:scaleX(-1)}
.crop--bl{bottom:0;left:0;transform:scaleY(-1)}
.crop--br{bottom:0;right:0;transform:scale(-1)}

.hero{display:none;height:186px;background:
  linear-gradient(150deg,var(--accent) 0%,color-mix(in srgb,var(--accent) 62%,#000) 100%)}
@supports not (background:color-mix(in srgb,red,blue)){
  .hero{background:linear-gradient(150deg,var(--accent),rgba(0,0,0,.45))}
}

.card{
  position:relative;background:var(--card-bg);border-radius:var(--radius);
  padding:30px 26px 26px;box-shadow:var(--card-shadow);
  animation:place .55s cubic-bezier(.2,.7,.3,1) both;
}
@keyframes place{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}

.stamp{position:absolute;top:22px;right:24px;max-height:26px;max-width:88px;object-fit:contain;opacity:.9}
body[data-theme="bold"] .stamp{top:-40px;right:22px;max-height:30px;filter:brightness(0) invert(1);opacity:.95}

.avatar{
  width:62px;height:62px;border-radius:50%;object-fit:cover;
  border:1px solid var(--rule);margin-bottom:16px;display:block;
}
.avatar--fallback{
  display:flex;align-items:center;justify-content:center;background:var(--accent);
  color:#fff;border-color:transparent;font-family:var(--mono);font-size:20px;letter-spacing:.05em;
}

h1{margin:0;font-size:var(--name-size);font-weight:var(--name-weight);
   letter-spacing:var(--name-track);line-height:1.04}
.role{margin:9px 0 0;font-size:.95rem;color:var(--fg-muted);line-height:1.45}
.bio{margin:15px 0 0;font-size:.9rem;line-height:1.62;color:var(--fg-2)}

.rows{list-style:none;margin:24px 0 0;padding:20px 0 0;border-top:1px solid var(--rule)}
.row + .row{margin-top:15px}
.row a,.row span.value{display:block;font-size:1rem;color:var(--fg);
  text-decoration:none;word-break:break-word;line-height:1.4}
.row a:hover{color:var(--accent)}
.label{display:block;font-family:var(--mono);font-size:.625rem;text-transform:uppercase;
  letter-spacing:.18em;color:var(--fg-muted);margin-bottom:4px}

.chips{display:flex;flex-wrap:wrap;gap:8px;margin:22px 0 0;padding:0;list-style:none}
.chip{display:inline-block;padding:7px 13px;border-radius:99px;font-size:.8rem;
  color:var(--chip-fg);text-decoration:none;background:var(--chip-bg)}
.chip:hover{color:var(--accent)}

/* ============================================================== Actions */
.actions{margin-top:22px}
.btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;
  padding:15px 18px;border-radius:calc(var(--radius) + 5px);border:1px solid transparent;
  font-family:var(--sans);font-size:.97rem;font-weight:620;letter-spacing:-.012em;
  text-align:center;text-decoration:none;cursor:pointer;transition:filter .15s,background .15s}
.btn svg{width:17px;height:17px;flex:none}
.btn--save{background:var(--accent);color:#fff}
.btn--save:hover{filter:brightness(1.09)}

.quick{display:grid;grid-auto-flow:column;grid-auto-columns:1fr;gap:9px;margin-top:9px}
.qbtn{
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;
  padding:13px 6px;border-radius:calc(var(--radius) + 5px);
  border:1px solid var(--rule);background:transparent;color:var(--outline-fg);
  font-family:var(--sans);font-size:.7rem;font-weight:560;letter-spacing:.01em;
  text-decoration:none;cursor:pointer;transition:border-color .15s,background .15s}
.qbtn:hover{border-color:var(--accent);color:var(--accent)}
.qbtn svg{width:18px;height:18px}

.qr{margin-top:26px;text-align:center}
.qr img{width:132px;height:132px;background:#fff;padding:9px;border-radius:8px}
.qr figcaption{margin-top:10px;font-family:var(--mono);font-size:.625rem;
  text-transform:uppercase;letter-spacing:.18em;color:var(--fg-muted)}

.proof{margin:26px 0 0;font-family:var(--mono);font-size:.625rem;letter-spacing:.14em;
  text-transform:uppercase;color:var(--fg-muted);opacity:.7;text-align:center}

.toast{
  position:fixed;left:50%;bottom:26px;transform:translate(-50%,90px);
  background:#14171A;color:#fff;padding:11px 20px;border-radius:99px;
  font-size:.85rem;font-weight:560;box-shadow:0 12px 30px -10px rgba(0,0,0,.5);
  opacity:0;transition:transform .3s cubic-bezier(.2,.8,.3,1),opacity .3s;pointer-events:none;z-index:50}
.toast.on{transform:translate(-50%,0);opacity:1}

:focus-visible{outline:2px solid var(--accent);outline-offset:3px}
@media (prefers-reduced-motion:reduce){.card{animation:none}*{transition:none!important}}
</style>
</head>
<body data-theme="{{ $theme }}">

<main class="sheet">
  <div class="hero" aria-hidden="true"></div>

  <div class="frame">
    <div class="crops" aria-hidden="true">
      <span class="crop crop--tl"></span><span class="crop crop--tr"></span>
      <span class="crop crop--bl"></span><span class="crop crop--br"></span>
    </div>

    <article class="card">
      @if($card->logoUrl())
        <img class="stamp" src="{{ $card->logoUrl() }}" alt="{{ $card->company }}">
      @endif

      @if($card->photoUrl())
        <img class="avatar" src="{{ $card->photoUrl() }}" alt="{{ $card->fullName() }}">
      @else
        <div class="avatar avatar--fallback" aria-hidden="true">{{ $card->initials() }}</div>
      @endif

      <h1>{{ $card->fullName() }}</h1>

      @if($card->role())
        <p class="role">{{ $card->role() }}@if($card->department)<br>{{ $card->department }}@endif</p>
      @endif

      @if($card->bio)<p class="bio">{{ $card->bio }}</p>@endif

      <ul class="rows">
        @if($card->email)
          <li class="row"><span class="label">Email</span>
            <a href="mailto:{{ $card->email }}">{{ $card->email }}</a></li>
        @endif
        @if($card->phone)
          <li class="row"><span class="label">Mobile</span>
            <a href="tel:{{ $tel }}">{{ $card->phone }}</a></li>
        @endif
        @if($card->phone_alt)
          <li class="row"><span class="label">Office</span>
            <a href="tel:{{ $telAlt }}">{{ $card->phone_alt }}</a></li>
        @endif
        @if($card->website)
          <li class="row"><span class="label">Website</span>
            <a href="{{ $card->website }}" rel="noopener">{{ preg_replace('#^https?://(www\.)?#', '', $card->website) }}</a></li>
        @endif
        @if($card->addressLines())
          <li class="row"><span class="label">Address</span>
            <span class="value">{{ implode(', ', $card->addressLines()) }}</span></li>
        @endif
      </ul>

      @if($card->normalisedLinks())
        <ul class="chips">
          @foreach($card->normalisedLinks() as $link)
            <li><a class="chip" href="{{ $link['url'] }}" rel="noopener">{{ $link['label'] }}</a></li>
          @endforeach
        </ul>
      @endif
    </article>
  </div>

  <div class="actions">
    <a class="btn btn--save" href="{{ route('dbc.public.vcard', $card->slug) }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>
      </svg>
      Save to contacts
    </a>

    <div class="quick">
      @if($tel)
        <a class="qbtn" href="tel:{{ $tel }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.6a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.5-1.2a2 2 0 0 1 2.1-.5c.8.3 1.7.6 2.6.7a2 2 0 0 1 1.7 2Z"/></svg>
          Call
        </a>
      @endif
      @if($card->email)
        <a class="qbtn" href="mailto:{{ $card->email }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/></svg>
          Email
        </a>
      @endif
      @if($wa)
        <a class="qbtn" href="https://wa.me/{{ $wa }}" rel="noopener">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-12.4 7.4L3 21l2.1-5.6A8.4 8.4 0 1 1 21 11.5Z"/></svg>
          WhatsApp
        </a>
      @endif
      <button class="qbtn" type="button" id="shareBtn" data-url="{{ $card->publicUrl() }}" data-name="{{ $card->fullName() }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7"/><path d="M16 6l-4-4-4 4"/><path d="M12 2v13"/></svg>
        Share
      </button>
    </div>
  </div>

  @if($qrAvailable)
    <figure class="qr">
      <img src="{{ route('dbc.public.qr', $card->slug) }}" alt="QR code for this card" loading="lazy">
      <figcaption>Scan to open on another phone</figcaption>
    </figure>
  @endif

  <p class="proof">/{{ config('digital-business-cards.routes.public.prefix') }}/{{ $card->slug }}</p>
</main>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script>
(function () {
  var toastEl = document.getElementById('toast');
  function toast(msg) {
    toastEl.textContent = msg;
    toastEl.classList.add('on');
    setTimeout(function () { toastEl.classList.remove('on'); }, 2000);
  }

  var btn = document.getElementById('shareBtn');
  if (!btn) return;

  btn.addEventListener('click', async function () {
    var url = btn.dataset.url;
    if (navigator.share) {
      try {
        await navigator.share({ title: btn.dataset.name, url: url });
        return;
      } catch (e) { /* dismissed — fall through to copy */ }
    }
    try {
      await navigator.clipboard.writeText(url);
      toast('Link copied');
    } catch (e) {
      toast('Copy the address from your browser bar');
    }
  });
})();
</script>
</body>
</html>
