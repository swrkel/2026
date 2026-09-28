@extends('dbc::layouts.app')

@php
  $editing = $card->exists;
  $links   = old('links', $card->links ?: []);
  $rows    = max(4, count($links) + 1);
@endphp

@section('title', $editing ? 'Edit '.$card->fullName() : 'New card')

@section('content')

<style>
.layout{display:grid;gap:22px;grid-template-columns:minmax(0,1fr) 332px;align-items:start}
@media (max-width:960px){.layout{grid-template-columns:1fr}}
.form-grid{display:grid;gap:16px;min-width:0}

/* ---------------------------------------------------------- Theme picker */
.themes{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
@media (max-width:520px){.themes{grid-template-columns:repeat(2,1fr)}}
.themes input{position:absolute;opacity:0;pointer-events:none}
.themes label{
  display:block;margin:0;cursor:pointer;border:2px solid var(--line);border-radius:10px;
  padding:0;overflow:hidden;transition:border-color .15s,box-shadow .15s;font-weight:520;
}
.themes label:hover{border-color:#C6D0CB}
.themes input:checked + label{border-color:var(--accent);box-shadow:0 0 0 3px rgba(47,111,98,.14)}
.themes input:focus-visible + label{outline:2px solid var(--accent);outline-offset:2px}
.swatchbox{height:56px;display:grid;place-items:center}
.swatchbox span{width:26px;height:17px;border-radius:3px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.28)}
.sw-classic{background:#1E2E28}
.sw-bold{background:linear-gradient(140deg,#2F6F62,#153029)}
.sw-minimal{background:#FFFFFF;border-bottom:1px solid var(--line)}
.sw-minimal span{background:#F1F1F1;box-shadow:none;border:1px solid #E2E2E2}
.sw-midnight{background:#12151A}
.sw-midnight span{background:rgba(255,255,255,.16);box-shadow:none}
.themes .cap{display:block;padding:8px 6px;text-align:center;font-size:.78rem}

/* ------------------------------------------------------------- Live card */
.previewwrap{position:sticky;top:80px}
@media (max-width:960px){.previewwrap{position:static}}
.pv-head{display:flex;align-items:center;gap:8px;margin-bottom:11px}
.pv-head h2{margin:0;font-size:.82rem;font-weight:620;letter-spacing:-.01em}
.pv-live{width:6px;height:6px;border-radius:50%;background:var(--accent);flex:none}
.pv-note{margin:11px 0 0;font-size:.75rem;color:var(--muted);text-align:center}

#pv{
  border-radius:14px;padding:26px 22px;overflow:hidden;position:relative;
  transition:background .25s,color .25s;
}
#pv[data-t="classic"]{background:#1E2E28}
#pv[data-t="bold"]{background:#F2F2F0}
#pv[data-t="minimal"]{background:#FFFFFF;border:1px solid var(--line)}
#pv[data-t="midnight"]{background:#12151A}

.pv-band{display:none;height:74px;margin:-26px -22px 0;background:var(--pv-accent)}
#pv[data-t="bold"] .pv-band{display:block}

.pv-card{
  background:#F7F4EC;color:#16211D;border-radius:3px;padding:20px 18px;
  box-shadow:0 14px 30px -14px rgba(0,0,0,.55);position:relative;
}
#pv[data-t="classic"] .pv-card::before{
  content:"";position:absolute;left:0;top:0;bottom:0;width:3px;
  border-radius:3px 0 0 3px;background:var(--pv-accent);
}
#pv[data-t="bold"] .pv-card{border-radius:14px;background:#fff;margin-top:-26px}
#pv[data-t="minimal"] .pv-card{
  background:#fff;box-shadow:none;border-radius:0;padding:18px 0 0;
  border-top:2px solid var(--pv-accent);
}
#pv[data-t="midnight"] .pv-card{
  background:rgba(255,255,255,.06);color:#F2F4F6;border:1px solid rgba(255,255,255,.11);
  border-radius:13px;box-shadow:none;
}

.pv-av{
  width:42px;height:42px;border-radius:50%;display:grid;place-items:center;
  background:var(--pv-accent);color:#fff;font-family:var(--mono);font-size:.8rem;
  margin-bottom:12px;overflow:hidden;
}
.pv-av img{width:100%;height:100%;object-fit:cover}
#pv[data-t="bold"] .pv-av{margin-top:-34px;border:3px solid #fff;width:50px;height:50px}
.pv-name{margin:0;font-size:1.2rem;font-weight:700;letter-spacing:-.032em;line-height:1.1;overflow-wrap:anywhere}
#pv[data-t="bold"] .pv-name{font-size:1.42rem;font-weight:780;letter-spacing:-.042em}
#pv[data-t="minimal"] .pv-name{font-weight:560;letter-spacing:-.018em}
.pv-role{margin:5px 0 0;font-size:.78rem;opacity:.62;line-height:1.35}
.pv-rows{margin:14px 0 0;padding:12px 0 0;border-top:1px solid rgba(0,0,0,.11);list-style:none}
#pv[data-t="midnight"] .pv-rows{border-top-color:rgba(255,255,255,.13)}
.pv-rows li{font-size:.76rem;line-height:1.5;overflow-wrap:anywhere}
.pv-rows li + li{margin-top:6px}
.pv-lab{font-family:var(--mono);font-size:.53rem;text-transform:uppercase;letter-spacing:.16em;opacity:.55;display:block}
.pv-btn{
  margin-top:16px;padding:11px;border-radius:8px;background:var(--pv-accent);color:#fff;
  text-align:center;font-size:.8rem;font-weight:620;
}
#pv[data-t="minimal"] .pv-btn{border-radius:0}
.pv-empty{opacity:.45;font-style:italic}
.actionbar{
  position:sticky;bottom:0;margin-top:20px;padding:14px 18px;
  background:rgba(255,255,255,.92);backdrop-filter:blur(8px);
  border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow);
  display:flex;align-items:center;gap:10px;flex-wrap:wrap;
}
.actionbar .spacer{flex:1}
.linkrow{display:grid;gap:12px;grid-template-columns:minmax(120px,1fr) minmax(180px,2fr)}
.linkrow + .linkrow{margin-top:12px}
@media (max-width:520px){.linkrow{grid-template-columns:1fr}}
.swatch{display:flex;align-items:center;gap:11px}
.preview{
  display:flex;align-items:center;gap:12px;padding:12px;
  background:var(--wash);border-radius:9px;margin-bottom:16px;
}
.preview img{width:40px;height:40px;border-radius:50%;object-fit:cover;border:1px solid var(--line)}
</style>

<div class="pagehead">
  <div>
    <p class="eyebrow">{{ $editing ? 'Editing card' : 'New card' }}</p>
    <h1>{{ $editing ? $card->fullName() : 'Create a card' }}</h1>
    @unless($editing)
      <p class="lede">Only a first name is required. You can fill in the rest later and publish when you’re ready.</p>
    @endunless
  </div>
  <a class="btn" href="{{ route('dbc.index') }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    All cards
  </a>
</div>

<form method="POST"
      action="{{ $editing ? route('dbc.update', $card) : route('dbc.store') }}"
      enctype="multipart/form-data">
  @csrf
  @if($editing) @method('PUT') @endif

  <div class="layout">
  <div class="form-grid">

    <div class="panel">
      <div class="section">
        <div class="section__head">
          <h2 class="section__title">Name and role</h2>
          <p class="section__note">How this person is introduced on the card.</p>
        </div>

        <div class="grid">
          <div class="field">
            <label for="first_name">First name</label>
            <input id="first_name" name="first_name" type="text" required
                   class="@error('first_name') invalid @enderror"
                   value="{{ old('first_name', $card->first_name) }}">
            @error('first_name')<p class="error">{{ $message }}</p>@enderror
          </div>

          <div class="field">
            <label for="last_name">Last name</label>
            <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $card->last_name) }}">
          </div>

          <div class="field">
            <label for="job_title">Job title</label>
            <input id="job_title" name="job_title" type="text" value="{{ old('job_title', $card->job_title) }}">
          </div>

          <div class="field">
            <label for="company">Company</label>
            <input id="company" name="company" type="text" value="{{ old('company', $card->company) }}">
          </div>

          <div class="field">
            <label for="department">Team or department</label>
            <input id="department" name="department" type="text" value="{{ old('department', $card->department) }}">
          </div>
        </div>

        <div class="field" style="margin-top:6px">
          <label for="bio">Short intro</label>
          <textarea id="bio" name="bio" maxlength="600"
                    class="@error('bio') invalid @enderror"
                    placeholder="A line or two about what they do.">{{ old('bio', $card->bio) }}</textarea>
          <p class="hint">Keep it to two lines — it sits directly under the name.</p>
          @error('bio')<p class="error">{{ $message }}</p>@enderror
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="section">
        <div class="section__head">
          <h2 class="section__title">Contact details</h2>
          <p class="section__note">These are what get saved when someone taps “Save contact”.</p>
        </div>

        <div class="grid">
          <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email"
                   class="@error('email') invalid @enderror"
                   value="{{ old('email', $card->email) }}">
            @error('email')<p class="error">{{ $message }}</p>@enderror
          </div>

          <div class="field">
            <label for="phone">Mobile</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone', $card->phone) }}">
          </div>

          <div class="field">
            <label for="phone_alt">Office phone</label>
            <input id="phone_alt" name="phone_alt" type="tel" value="{{ old('phone_alt', $card->phone_alt) }}">
          </div>

          <div class="field">
            <label for="website">Website</label>
            <input id="website" name="website" type="url" placeholder="https://"
                   class="@error('website') invalid @enderror"
                   value="{{ old('website', $card->website) }}">
            @error('website')<p class="error">{{ $message }}</p>@enderror
          </div>
        </div>
      </div>

      <div class="section">
        <div class="section__head">
          <h2 class="section__title">Address</h2>
          <p class="section__note">Optional. Leave blank to keep the card short.</p>
        </div>

        <div class="grid">
          <div class="field">
            <label for="address_line">Street address</label>
            <input id="address_line" name="address_line" type="text" value="{{ old('address_line', $card->address_line) }}">
          </div>
          <div class="field">
            <label for="city">City</label>
            <input id="city" name="city" type="text" value="{{ old('city', $card->city) }}">
          </div>
          <div class="field">
            <label for="region">State or province</label>
            <input id="region" name="region" type="text" value="{{ old('region', $card->region) }}">
          </div>
          <div class="field">
            <label for="postal_code">Postal code</label>
            <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $card->postal_code) }}">
          </div>
          <div class="field">
            <label for="country">Country</label>
            <input id="country" name="country" type="text" value="{{ old('country', $card->country) }}">
          </div>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="section">
        <div class="section__head">
          <h2 class="section__title">Profiles and links</h2>
          <p class="section__note">LinkedIn, a portfolio, a booking page — they appear as buttons on the card.</p>
        </div>

        @for($i = 0; $i < $rows; $i++)
          <div class="linkrow">
            <div class="field" style="margin:0">
              <label for="links-{{ $i }}-label">Label</label>
              <input id="links-{{ $i }}-label" name="links[{{ $i }}][label]" type="text"
                     placeholder="LinkedIn" value="{{ $links[$i]['label'] ?? '' }}">
            </div>
            <div class="field" style="margin:0">
              <label for="links-{{ $i }}-url">Address</label>
              <input id="links-{{ $i }}-url" name="links[{{ $i }}][url]" type="url"
                     placeholder="https://" value="{{ $links[$i]['url'] ?? '' }}">
            </div>
          </div>
        @endfor

        <p class="hint" style="margin-top:12px">Rows left blank are ignored.</p>
      </div>
    </div>

    <div class="panel">
      <div class="section">
        <div class="section__head">
          <h2 class="section__title">Appearance</h2>
          <p class="section__note">A photo and one accent colour is all the card uses.</p>
        </div>

        <div class="field">
          <label>Card design</label>
          <div class="themes">
            @foreach(\Modules\DigitalBusinessCards\Models\Card::THEMES as $key => $label)
              <input type="radio" name="theme" id="theme-{{ $key }}" value="{{ $key }}"
                     @checked(old('theme', $card->themeKey()) === $key)>
              <label for="theme-{{ $key }}">
                <span class="swatchbox sw-{{ $key }}"><span></span></span>
                <span class="cap">{{ $label }}</span>
              </label>
            @endforeach
          </div>
          @error('theme')<p class="error">{{ $message }}</p>@enderror
        </div>

        @if($card->photoUrl() || $card->logoUrl())
          <div class="preview">
            @if($card->photoUrl())<img src="{{ $card->photoUrl() }}" alt="Current photo">@endif
            @if($card->logoUrl())<img src="{{ $card->logoUrl() }}" alt="Current logo" style="border-radius:6px">@endif
            <span class="hint" style="margin:0">Currently in use. Choosing a new file replaces it.</span>
          </div>
        @endif

        <div class="grid">
          <div class="field">
            <label for="photo">Photo</label>
            <input id="photo" name="photo" type="file" accept="image/*">
            @error('photo')<p class="error">{{ $message }}</p>@enderror
          </div>

          <div class="field">
            <label for="logo">Company logo</label>
            <input id="logo" name="logo" type="file" accept="image/*">
            @error('logo')<p class="error">{{ $message }}</p>@enderror
          </div>

          <div class="field">
            <label for="accent_color">Accent colour</label>
            <div class="swatch">
              <input id="accent_color" name="accent_color" type="color"
                     value="{{ old('accent_color', $card->accent_color ?: config('digital-business-cards.defaults.accent_color')) }}">
              <span class="hint" style="margin:0">Used for the card edge and buttons.</span>
            </div>
            @error('accent_color')<p class="error">{{ $message }}</p>@enderror
          </div>
        </div>
      </div>

      <div class="section">
        <div class="section__head">
          <h2 class="section__title">Address and visibility</h2>
        </div>

        <div class="field">
          <label for="slug">Card address</label>
          <input id="slug" name="slug" type="text"
                 class="@error('slug') invalid @enderror"
                 value="{{ old('slug', $card->slug) }}"
                 placeholder="{{ \Illuminate\Support\Str::slug($card->fullName() ?: 'your-name') }}">
          <p class="hint">
            Leave blank and one is made from the name. The card will live at
            <code>{{ url(config('digital-business-cards.routes.public.prefix')) }}/…</code>
          </p>
          @error('slug')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div class="check" style="margin-top:20px">
          <input id="is_published" name="is_published" type="checkbox" value="1"
                 @checked(old('is_published', $card->is_published))>
          <div>
            <label for="is_published">Publish this card</label>
            <p class="hint">Anyone with the link or QR code can open it. Leave off to keep working on it privately.</p>
          </div>
        </div>
      </div>
    </div>

  </div>

  <aside class="previewwrap">
    <div class="pv-head">
      <span class="pv-live" aria-hidden="true"></span>
      <h2>Live preview</h2>
    </div>

    <div id="pv" data-t="{{ old('theme', $card->themeKey()) }}"
         style="--pv-accent:{{ old('accent_color', $card->accent_color ?: config('digital-business-cards.defaults.accent_color')) }}">
      <div class="pv-band" aria-hidden="true"></div>
      <div class="pv-card">
        <div class="pv-av" id="pvAv">@if($card->photoUrl())<img src="{{ $card->photoUrl() }}" alt="">@else<span id="pvIni">{{ $card->initials() ?: '—' }}</span>@endif</div>
        <p class="pv-name" id="pvName">{{ $card->fullName() ?: 'Your name' }}</p>
        <p class="pv-role" id="pvRole">{{ $card->role() ?: 'Job title at Company' }}</p>
        <ul class="pv-rows">
          <li><span class="pv-lab">Email</span><span id="pvEmail" class="pv-empty">not set</span></li>
          <li><span class="pv-lab">Mobile</span><span id="pvPhone" class="pv-empty">not set</span></li>
        </ul>
        <div class="pv-btn">Save to contacts</div>
      </div>
    </div>

    <p class="pv-note">Updates as you type. Not to scale.</p>
  </aside>

  </div>

  <div class="actionbar">
    <button class="btn btn--primary" type="submit">{{ $editing ? 'Save changes' : 'Create card' }}</button>
    @if($editing && $card->is_published)
      <a class="btn" href="{{ $card->publicUrl() }}" target="_blank" rel="noopener">Open card</a>
      @if($qrAvailable ?? false)
        <a class="btn" href="{{ route('dbc.public.qr', $card->slug) }}" target="_blank" rel="noopener">Download QR</a>
      @endif
    @endif
    <span class="spacer"></span>
    <a class="btn btn--ghost" href="{{ route('dbc.index') }}">Cancel</a>
  </div>
</form>

<script>
(function () {
  var pv = document.getElementById('pv');
  if (!pv) return;

  function el(id) { return document.getElementById(id); }
  function val(name) {
    var n = document.querySelector('[name="' + name + '"]');
    return n ? n.value.trim() : '';
  }

  function setText(node, text, placeholder) {
    if (!node) return;
    node.textContent = text || placeholder;
    node.classList.toggle('pv-empty', !text);
  }

  function initials() {
    var a = val('first_name'), b = val('last_name');
    var s = (a ? a[0] : '') + (b ? b[0] : '');
    return s.toUpperCase() || '\u2014';
  }

  function role() {
    var t = val('job_title'), c = val('company');
    if (t && c) return t + ' at ' + c;
    return t || c || '';
  }

  function refresh() {
    var name = (val('first_name') + ' ' + val('last_name')).trim();
    setText(el('pvName'), name, 'Your name');
    setText(el('pvRole'), role(), 'Job title at Company');
    setText(el('pvEmail'), val('email'), 'not set');
    setText(el('pvPhone'), val('phone'), 'not set');

    var ini = el('pvIni');
    if (ini) ini.textContent = initials();

    var colour = document.querySelector('[name="accent_color"]');
    if (colour) pv.style.setProperty('--pv-accent', colour.value);

    var theme = document.querySelector('[name="theme"]:checked');
    if (theme) pv.dataset.t = theme.value;
  }

  ['first_name','last_name','job_title','company','email','phone','accent_color'].forEach(function (n) {
    var node = document.querySelector('[name="' + n + '"]');
    if (node) node.addEventListener('input', refresh);
  });

  document.querySelectorAll('[name="theme"]').forEach(function (r) {
    r.addEventListener('change', refresh);
  });

  // Swap in a locally chosen photo without uploading first.
  var photo = document.querySelector('[name="photo"]');
  if (photo) {
    photo.addEventListener('change', function () {
      var file = this.files && this.files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function (e) {
        var box = el('pvAv');
        if (box) box.innerHTML = '<img src="' + e.target.result + '" alt="">';
      };
      reader.readAsDataURL(file);
    });
  }

  refresh();
})();
</script>

@endsection
