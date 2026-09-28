<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Business cards')</title>
<style>
:root{
  --ink:#12211C;
  --paper:#FFFFFF;
  --wash:#F3F6F4;
  --line:#E3E8E5;
  --line-soft:#EFF2F0;
  --muted:#6B7B74;
  --accent:#2F6F62;
  --accent-deep:#245549;
  --accent-soft:#E7F0ED;
  --amber:#9A6520;
  --amber-soft:#F6EEE1;
  --danger:#A2402F;
  --sans:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
  --mono:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono",monospace;
  --shadow:0 1px 2px rgba(18,33,28,.04),0 8px 24px -12px rgba(18,33,28,.18);
  --shadow-lift:0 2px 4px rgba(18,33,28,.06),0 16px 32px -14px rgba(18,33,28,.26);
}

*,*::before,*::after{box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{
  margin:0;background:var(--wash);color:var(--ink);
  font-family:var(--sans);font-size:15px;line-height:1.55;
  -webkit-font-smoothing:antialiased;
}
a{color:var(--accent);text-decoration:none}
a:hover{color:var(--accent-deep)}

/* ---------------------------------------------------------------- Shell */
.topbar{
  background:var(--paper);border-bottom:1px solid var(--line);
  position:sticky;top:0;z-index:20;
}
.topbar__inner{
  max-width:1060px;margin:0 auto;padding:0 24px;height:58px;
  display:flex;align-items:center;gap:12px;
}
.mark{
  width:26px;height:26px;border-radius:6px;background:var(--accent);
  display:grid;place-items:center;flex:none;
}
.mark svg{width:14px;height:14px;display:block}
.wordmark{font-weight:650;letter-spacing:-.02em;font-size:.95rem}
.topbar__spacer{flex:1}

.wrap{max-width:1060px;margin:0 auto;padding:32px 24px 80px}

/* ------------------------------------------------------------ Page head */
.pagehead{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:28px}
.eyebrow{
  font-family:var(--mono);font-size:.625rem;text-transform:uppercase;
  letter-spacing:.18em;color:var(--muted);margin:0 0 6px;
}
h1{margin:0;font-size:1.75rem;font-weight:680;letter-spacing:-.032em;line-height:1.15}
.lede{margin:8px 0 0;color:var(--muted);font-size:.94rem;max-width:56ch}

/* --------------------------------------------------------------- Alerts */
.notice{
  display:flex;gap:10px;align-items:flex-start;
  margin:0 0 22px;padding:13px 16px;border-radius:8px;
  background:var(--accent-soft);color:var(--accent-deep);
  font-size:.9rem;border:1px solid rgba(47,111,98,.16);
}
.notice--warn{background:var(--amber-soft);color:var(--amber);border-color:rgba(154,101,32,.18)}
.notice--error{background:#F9ECE9;color:var(--danger);border-color:rgba(162,64,47,.18)}
.notice code{
  font-family:var(--mono);font-size:.82em;background:rgba(18,33,28,.06);
  padding:1px 5px;border-radius:4px;
}
.notice__dot{width:6px;height:6px;border-radius:50%;background:currentColor;margin-top:8px;flex:none}

/* --------------------------------------------------------------- Panels */
.panel{background:var(--paper);border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow)}

/* -------------------------------------------------------------- Buttons */
.btn{
  display:inline-flex;align-items:center;gap:7px;
  padding:9px 15px;border-radius:8px;border:1px solid var(--line);
  background:var(--paper);color:var(--ink);
  font:inherit;font-size:.875rem;font-weight:560;letter-spacing:-.005em;
  cursor:pointer;white-space:nowrap;transition:border-color .14s,background .14s,box-shadow .14s;
}
.btn:hover{border-color:#CBD4CF;background:#FBFCFB;color:var(--ink)}
.btn svg{width:14px;height:14px;flex:none}
.btn--primary{background:var(--accent);border-color:var(--accent);color:#fff;box-shadow:0 1px 2px rgba(36,85,73,.28)}
.btn--primary:hover{background:var(--accent-deep);border-color:var(--accent-deep);color:#fff}
.btn--ghost{background:transparent;border-color:transparent;color:var(--muted);padding:9px 10px}
.btn--ghost:hover{background:var(--line-soft);color:var(--ink);border-color:transparent}
.btn--danger{color:var(--danger);border-color:transparent;background:transparent;padding:9px 10px}
.btn--danger:hover{background:#F9ECE9;border-color:transparent}
.btn--sm{padding:6px 11px;font-size:.82rem}

/* ---------------------------------------------------------------- Forms */
.field{margin-bottom:18px}
.field:last-child{margin-bottom:0}
label{display:block;font-size:.8rem;font-weight:580;color:#3E4F49;margin-bottom:6px}
input[type=text],input[type=email],input[type=url],input[type=tel],textarea,select{
  width:100%;padding:10px 13px;border:1px solid var(--line);border-radius:8px;
  background:var(--paper);font:inherit;font-size:.92rem;color:var(--ink);
  transition:border-color .14s,box-shadow .14s;
}
input:focus,textarea:focus,select:focus{
  outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(47,111,98,.13);
}
input[type=file]{
  width:100%;padding:9px 12px;border:1px dashed var(--line);border-radius:8px;
  background:var(--wash);font:inherit;font-size:.85rem;color:var(--muted);
}
input[type=color]{width:46px;height:40px;padding:3px;border:1px solid var(--line);border-radius:8px;background:var(--paper);cursor:pointer}
textarea{resize:vertical;min-height:84px;line-height:1.6}
.grid{display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(210px,1fr))}
.hint{margin:6px 0 0;font-size:.78rem;color:var(--muted);line-height:1.5}
.hint code{font-family:var(--mono);font-size:.9em;color:#4B5C56}
.invalid{border-color:var(--danger)!important}
.error{margin:6px 0 0;font-size:.78rem;color:var(--danger)}

fieldset{border:0;padding:0;margin:0}
.section{padding:24px}
.section + .section{border-top:1px solid var(--line-soft)}
.section__head{margin-bottom:18px}
.section__title{margin:0;font-size:.98rem;font-weight:640;letter-spacing:-.015em}
.section__note{margin:3px 0 0;font-size:.83rem;color:var(--muted)}

.check{display:flex;align-items:flex-start;gap:11px}
.check input{width:17px;height:17px;margin:2px 0 0;accent-color:var(--accent);flex:none;cursor:pointer}
.check label{margin:0;font-weight:520;font-size:.9rem;cursor:pointer}
.check .hint{margin-top:3px}

/* -------------------------------------------------------------- Utility */
.pill{
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 9px;border-radius:99px;font-size:.72rem;font-weight:620;
  letter-spacing:.02em;white-space:nowrap;
}
.pill--live{background:var(--accent-soft);color:var(--accent-deep)}
.pill--draft{background:var(--amber-soft);color:var(--amber)}
.pill__dot{width:5px;height:5px;border-radius:50%;background:currentColor}

.mono{font-family:var(--mono);font-size:.78rem;letter-spacing:-.01em;color:var(--muted)}

:focus-visible{outline:2px solid var(--accent);outline-offset:2px;border-radius:4px}

@media (max-width:560px){
  .wrap{padding:24px 16px 64px}
  .topbar__inner{padding:0 16px}
  h1{font-size:1.5rem}
  .section{padding:20px}
}
@media (prefers-reduced-motion:reduce){*{transition:none!important}}
</style>
</head>
<body>

<header class="topbar">
  <div class="topbar__inner">
    <span class="mark" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>
      </svg>
    </span>
    <span class="wordmark">Digital business cards</span>
    <span class="topbar__spacer"></span>
    <a class="btn btn--ghost btn--sm" href="{{ url('/') }}">Back to app</a>
  </div>
</header>

<div class="wrap">
  @if(session('dbc.status'))
    <p class="notice"><span class="notice__dot" aria-hidden="true"></span>{{ session('dbc.status') }}</p>
  @endif

  @if($errors->any())
    <div class="notice notice--error">
      <span class="notice__dot" aria-hidden="true"></span>
      <span>Some details need fixing before this can be saved — see the fields marked below.</span>
    </div>
  @endif

  @yield('content')
</div>

</body>
</html>
