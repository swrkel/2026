@if(session('success')) <div class="pdn-alert success">{{ session('success') }}</div> @endif
@if(session('error')) <div class="pdn-alert error">{{ session('error') }}</div> @endif
@if(session('warning')) <div class="pdn-alert warning">{{ session('warning') }}</div> @endif
@if($errors->any())
    <div class="pdn-alert error"><strong>Please correct the following:</strong><ul>
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul></div>
@endif
