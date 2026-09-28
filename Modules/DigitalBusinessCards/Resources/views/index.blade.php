@extends('dbc::layouts.app')

@section('title', 'Business cards')

@section('content')

<style>
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:var(--line);
       border:1px solid var(--line);border-radius:12px;overflow:hidden;margin-bottom:26px}
.stat{background:var(--paper);padding:16px 18px}
.stat__n{font-size:1.5rem;font-weight:680;letter-spacing:-.035em;line-height:1.1}
.stat__l{font-family:var(--mono);font-size:.6rem;text-transform:uppercase;
         letter-spacing:.16em;color:var(--muted);margin-top:5px}
@media (max-width:640px){.stats{grid-template-columns:repeat(2,1fr)}}

.cards{display:grid;gap:16px;grid-template-columns:repeat(auto-fill,minmax(304px,1fr))}

.card{
  background:var(--paper);border:1px solid var(--line);border-radius:12px;
  box-shadow:var(--shadow);display:flex;flex-direction:column;overflow:hidden;
  transition:box-shadow .16s,transform .16s,border-color .16s;
}
.card:hover{box-shadow:var(--shadow-lift);transform:translateY(-2px);border-color:#D5DDD9}

.card__top{padding:18px 18px 0;display:flex;gap:13px;align-items:flex-start}
.avatar{width:44px;height:44px;border-radius:50%;object-fit:cover;flex:none;border:1px solid var(--line)}
.avatar--initials{
  display:grid;place-items:center;color:#fff;font-family:var(--mono);
  font-size:.85rem;letter-spacing:.04em;border:0;
}
.card__id{min-width:0;flex:1}
.card__name{margin:0;font-size:1.02rem;font-weight:640;letter-spacing:-.022em;
            line-height:1.25;overflow-wrap:anywhere}
.card__role{margin:3px 0 0;font-size:.84rem;color:var(--muted);line-height:1.4}

.card__url{
  margin:15px 18px 0;padding:8px 11px;background:var(--wash);border-radius:7px;
  font-family:var(--mono);font-size:.75rem;color:#4B5C56;
  overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
}

.card__meta{display:flex;gap:20px;padding:14px 18px 0}
.meta__n{font-size:.95rem;font-weight:640;letter-spacing:-.02em}
.meta__l{font-family:var(--mono);font-size:.58rem;text-transform:uppercase;
         letter-spacing:.15em;color:var(--muted);margin-top:2px}

.card__foot{
  margin-top:auto;padding:14px 12px 12px 18px;display:flex;align-items:center;
  gap:4px;flex-wrap:wrap;
}
.card__foot form{display:inline}
.spacer{flex:1}

.empty{padding:56px 28px;text-align:center}
.empty__art{width:52px;height:52px;border-radius:12px;background:var(--accent-soft);
            display:grid;place-items:center;margin:0 auto 18px}
.empty__art svg{width:24px;height:24px;stroke:var(--accent)}
.empty h2{margin:0 0 7px;font-size:1.1rem;font-weight:640;letter-spacing:-.022em}
.empty p{margin:0 auto 22px;color:var(--muted);font-size:.9rem;max-width:40ch}

.pager{margin-top:26px}
.pager svg{width:15px;height:15px}
</style>

<div class="pagehead">
  <div>
    <p class="eyebrow">Digital business cards</p>
    <h1>Your cards</h1>
    <p class="lede">Each card gets its own web address and a QR code. Share either one and people can save your contact details in a single tap.</p>
  </div>
  <a class="btn btn--primary" href="{{ route('dbc.create') }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
    New card
  </a>
</div>

@if(! $qrAvailable)
  <p class="notice notice--warn">
    <span class="notice__dot" aria-hidden="true"></span>
    <span>QR codes are switched off because no QR renderer is installed. Everything else works normally — cards can still be shared by link.</span>
  </p>
@endif

@if($stats['total'] > 0)
  <div class="stats">
    <div class="stat">
      <div class="stat__n">{{ number_format($stats['total']) }}</div>
      <div class="stat__l">Cards</div>
    </div>
    <div class="stat">
      <div class="stat__n">{{ number_format($stats['live']) }}</div>
      <div class="stat__l">Live</div>
    </div>
    <div class="stat">
      <div class="stat__n">{{ number_format($stats['views']) }}</div>
      <div class="stat__l">Views</div>
    </div>
    <div class="stat">
      <div class="stat__n">{{ number_format($stats['saves']) }}</div>
      <div class="stat__l">Saved</div>
    </div>
  </div>
@endif

@if($cards->count())
<div class="cards">
  @foreach($cards as $card)
    <article class="card">
      <div class="card__top">
        @if($card->photoUrl())
          <img class="avatar" src="{{ $card->photoUrl() }}" alt="">
        @else
          <div class="avatar avatar--initials" style="background:{{ $card->accent_color ?: '#2F6F62' }}" aria-hidden="true">{{ $card->initials() }}</div>
        @endif

        <div class="card__id">
          <h2 class="card__name">{{ $card->fullName() }}</h2>
          <p class="card__role">{{ $card->role() ?? 'No role set yet' }}</p>
        </div>

        @if($card->is_published)
          <span class="pill pill--live"><span class="pill__dot"></span>Live</span>
        @else
          <span class="pill pill--draft"><span class="pill__dot"></span>Draft</span>
        @endif
      </div>

      <div class="card__url">/{{ config('digital-business-cards.routes.public.prefix') }}/{{ $card->slug }}</div>

      <p class="mono" style="margin:9px 18px 0">{{ $card->themeLabel() }} design</p>

      <div class="card__meta">
        <div>
          <div class="meta__n">{{ number_format($card->views_count) }}</div>
          <div class="meta__l">Views</div>
        </div>
        <div>
          <div class="meta__n">{{ number_format($card->saves_count) }}</div>
          <div class="meta__l">Saved</div>
        </div>
      </div>

      <div class="card__foot">
        <a class="btn btn--sm" href="{{ route('dbc.edit', $card) }}">Edit</a>
        @if($card->is_published)
          <a class="btn btn--sm btn--ghost" href="{{ $card->publicUrl() }}" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/></svg>
            Open
          </a>
        @endif
        <span class="spacer"></span>
        <form method="POST" action="{{ route('dbc.destroy', $card) }}"
              onsubmit="return confirm('Delete the card for {{ $card->fullName() }}?\n\nIts link and QR code will stop working straight away.')">
          @csrf
          @method('DELETE')
          <button class="btn btn--sm btn--danger" type="submit" aria-label="Delete {{ $card->fullName() }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
          </button>
        </form>
      </div>
    </article>
  @endforeach
</div>
@else
  <div class="panel empty">
    <div class="empty__art" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M7 15h4"/>
      </svg>
    </div>
    <h2>No cards yet</h2>
    <p>Create your first card and you’ll get a shareable link and a QR code right away.</p>
    <a class="btn btn--primary" href="{{ route('dbc.create') }}">Create your first card</a>
  </div>
@endif

@if($cards->hasPages())
  <div class="pager">{{ $cards->links() }}</div>
@endif

@endsection
