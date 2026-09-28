@if(session('success'))<div class="rest-alert rest-alert-success"><i class="fa fa-check-circle"></i><span>{{ session('success') }}</span></div>@endif
@if(session('error'))<div class="rest-alert rest-alert-danger"><i class="fa fa-exclamation-circle"></i><span>{{ session('error') }}</span></div>@endif
@if($errors->any())<div class="rest-alert rest-alert-danger"><i class="fa fa-exclamation-triangle"></i><div><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
