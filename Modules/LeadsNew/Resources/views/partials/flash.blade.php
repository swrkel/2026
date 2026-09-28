@if(session('status'))
    @php($statusMessage = is_array(session('status')) ? (session('status')['msg'] ?? '') : session('status'))
    @if($statusMessage)<div class="ln-flash"><i class="fa fa-check-circle"></i> {{ $statusMessage }}</div>@endif
@endif
@if($errors->any())
    <div class="ln-errors"><strong>{{ __('leadsnew::messages.please_fix_errors') }}</strong><ul style="margin:8px 0 0 18px; padding:0;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif