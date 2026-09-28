@once
    @push('javascript')
        <script>
            window.membershipAuthorizedSignatureRoutes = {
                index: "{{ route('membership.setting.authorized-signatures.index') }}"
            };
        </script>
        <script>
@include('membership::settings.authorized_signature.script_source')
        </script>
    @endpush
@endonce
