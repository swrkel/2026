{{--
    Settlement SW Reconfirmation / Preview.

    The preview is intentionally built from the exact in-browser settlement
    draft which is then submitted. It does not re-query live sales and therefore
    cannot pull another shift's rows into the confirmation screen.
--}}
<div class="modal fade" id="sw_settlement_preview_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="width:min(96%,1400px);">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-check-square-o"></i> Settlement Preview - Confirm Before Finalizing</h4>
            </div>
            <div class="modal-body">
                <div class="sw-preview-meta" id="sw_preview_meta"></div>
                <div id="sw_preview_sections"></div>
            </div>
            <div class="modal-footer">
                <button type="button" id="sw_confirm_settlement_preview" class="btn btn-primary pull-left">
                    Confirm all the entered details are correct
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
#sw_settlement_preview_modal .modal-body{max-height:76vh;overflow:auto;background:#f7f9fc}
#sw_settlement_preview_modal .modal-title{font-weight:700}
.sw-preview-meta{display:grid;grid-template-columns:repeat(5,minmax(150px,1fr));gap:10px;margin-bottom:14px}
.sw-preview-meta>div{background:#fff;border:1px solid #dfe5ee;border-radius:6px;padding:9px 11px}
.sw-preview-meta span{display:block;font-size:11px;color:#68758b;text-transform:uppercase;font-weight:700;margin-bottom:3px}
.sw-preview-meta strong{display:block;color:#182235;word-break:break-word}
.sw-preview-card{background:#fff;border:1px solid #dfe5ee;border-radius:7px;margin-bottom:13px;overflow:hidden}
.sw-preview-card h4{margin:0;padding:10px 12px;background:#f1f5f9;border-bottom:1px solid #dfe5ee;font-size:14px;font-weight:700}
.sw-preview-card .table{margin-bottom:0}
.sw-preview-card .table th{white-space:nowrap;background:#fafbfd}
.sw-preview-card .table td,.sw-preview-card .table th{vertical-align:middle}
.sw-preview-num{text-align:right!important;white-space:nowrap}
.sw-preview-actions{white-space:nowrap;text-align:center}
.sw-preview-empty{padding:14px!important;text-align:center;color:#7a8698}
@media(max-width:991px){.sw-preview-meta{grid-template-columns:repeat(2,minmax(140px,1fr))}}
@media(max-width:600px){.sw-preview-meta{grid-template-columns:1fr}}
</style>
