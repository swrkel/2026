@if(session('status'))<div class="stk-alert success"><i class="fa fa-check-circle"></i><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="stk-alert danger"><i class="fa fa-exclamation-triangle"></i><div><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
