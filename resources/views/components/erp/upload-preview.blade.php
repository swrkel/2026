@props([
    'label' => null,
    'name',
    'src' => null,
    'accept' => 'image/*',
])

<div class="erp-form-group erp-upload-box">
    @if($label)
        <label for="{{ $name }}">{{ $label }}</label>
    @endif
    <input type="file" id="{{ $name }}" name="{{ $name }}" accept="{{ $accept }}" class="form-control erp-file-input" data-preview-target="{{ $name }}_preview">
    <div class="erp-upload-preview">
        <img id="{{ $name }}_preview" src="{{ $src ?: '' }}" alt="{{ $label ?: $name }} preview" style="{{ $src ? '' : 'display:none;' }}">
    </div>
</div>

@once
    @push('javascript')
        <script>
            document.addEventListener('change', function (event) {
                var input = event.target;
                if (!input.classList.contains('erp-file-input')) return;
                var targetId = input.getAttribute('data-preview-target');
                var preview = document.getElementById(targetId);
                if (!preview || !input.files || !input.files[0]) return;
                var reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            });
        </script>
    @endpush
@endonce
