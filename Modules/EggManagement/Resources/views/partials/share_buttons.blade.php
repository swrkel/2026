@php($eggShareType = $shareType ?? \Illuminate\Support\Str::afterLast((string)\Illuminate\Support\Facades\Route::currentRouteName(), '.'))
<div class="egg-share-row no-print" data-egg-share-box data-endpoint="{{ route('egg.share.create') }}" data-resource-type="{{ $eggShareType }}">
  <button type="button" class="egg-btn egg-btn-light" onclick="window.print()">Print</button>
  <button type="button" class="egg-btn egg-btn-light" data-egg-share="link">Copy Link</button>
  <button type="button" class="egg-btn egg-btn-light" data-egg-share="email">Email</button>
  <button type="button" class="egg-btn egg-btn-light" data-egg-share="sms">SMS Link</button>
  <button type="button" class="egg-btn egg-btn-light" data-egg-share="whatsapp">WhatsApp</button>
</div>
