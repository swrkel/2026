{{--
    Task 8046 - shared shell for the View and QR Action popups.

    Both are rendered server-side and injected here. One reusable shell avoids
    two near-identical modal skeletons in the page, and means the QR popup's
    markup is only fetched when someone actually opens it.
--}}
<div class="modal fade" id="cus_ref_generic_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" id="cus_ref_generic_modal_content">
            {{-- Replaced by the AJAX response. --}}
        </div>
    </div>
</div>
