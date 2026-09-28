<div class="modal-dialog" role="document" style="width: 65%;">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">{{$settlement->settlement_no}}</h4>
        </div>

        <div class="modal-body">
            {{--
                MA-002: the preview now shows THE SAME CONTENT AS THE PRINT.

                It had its own layout, about half the size of the printed
                settlement, so several sections never appeared - meter sales,
                other sales, expenses, customer payments, loans and other
                income among them. Anyone confirming from here was reviewing
                less than they were committing to.

                Rather than copying 350 lines across, IT INCLUDES THE PRINT
                VIEW. One definition, so the two cannot drift apart - the same
                reasoning as the shared meter-sale rule.

                I CHECKED THE PRINT VIEW IS SAFE TO INCLUDE. Every variable it
                uses that the preview does not pass is already guarded:

                    final_cash_amount    guarded with ?? 0
                    shift_ids            guarded with !empty()
                    work_shift           a loop variable over the settlement
                    asset_v              shared globally by AppServiceProvider

                and it carries no @extends, so it drops in as a fragment.
            --}}
            <style>
                /* The print view's own controls do not belong inside a modal. */
                .preview_settlement .no-print,
                .preview_settlement .petropd-print-toolbar {
                    display: none !important;
                }
                .preview_settlement .modal-body {
                    max-height: 70vh;
                    overflow-y: auto;
                }
            </style>

            @include('petropd::pd_settlement.print')
        </div>

        <div class="clearfix"></div>
        <div class="modal-footer">
            {{--
                MA-002: confirm from the preview.

                Finalize now opens this preview first, so there has to be a way
                to go on from here. This button closes the preview and runs the
                finalise that was interrupted.

                IT ONLY APPEARS WHEN FINALIZE SENT YOU HERE. Opening the
                preview on its own with the Preview button shows Close alone,
                exactly as before - the flag decides, and it is cleared as soon
                as it is used.
            --}}
            <button type="button" class="btn btn-success" id="pd_preview_confirm_btn"
                style="display:none; margin-right:5px;">Confirm &amp; Finalize</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>

            <script>
                (function () {
                    var $confirm = $('#pd_preview_confirm_btn');

                    if (!window.pdFinalizeAwaitingPreview) {
                        return;
                    }

                    $confirm.show();

                    $confirm.off('click.pdFinalize').on('click.pdFinalize', function () {
                        window.pdFinalizeAwaitingPreview = false;
                        $confirm.prop('disabled', true);

                        /*
                         * S 639: the preview is a plain panel now, not a modal,
                         * so Bootstrap's hide no longer applies. create.blade.php
                         * owns closing it.
                         */
                        if (typeof window.pdCloseSettlementPreview === 'function') {
                            window.pdCloseSettlementPreview();
                        } else {
                            $('.preview_settlement').removeClass('pd-preview-open').empty();
                            $('body').css('overflow', '');
                        }

                        /*
                         * The save handler skips the preview when this flag is
                         * set, so the click below finalises rather than looping
                         * back here. A short delay lets the modal finish closing
                         * first, which keeps the page from being left with a
                         * stale backdrop.
                         */
                        var $finalize = $('#settlement_save_btn');
                        $finalize.data('previewConfirmed', true);

                        window.setTimeout(function () {
                            $finalize.trigger('click');
                        }, 300);
                    });
                })();
            </script>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->