@if(session('status'))
    @php($poneStatus = session('status'))
    <div class="pone-alert {{ is_array($poneStatus) && ($poneStatus['success'] ?? 0) ? 'pone-alert-success' : 'pone-alert-danger' }}" data-auto-dismiss>
        {{ is_array($poneStatus) ? ($poneStatus['msg'] ?? '') : $poneStatus }}
    </div>
@endif
@if($errors->any())
    <div class="pone-alert pone-alert-danger">
        <strong>{{ __('Please correct the following:') }}</strong>
        <ul style="margin:7px 0 0 18px;padding:0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
