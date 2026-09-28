<div class="pdn-card" style="margin-top:14px">
<h3>Settlement Workflow</h3>
<div class="pdn-actions" style="justify-content:flex-start">
@if(in_array($settlement->status,['draft','reopened'],true))
    @can('petro_pd_new.workflow.review')
    <form method="post" action="{{ route('petro-pd-new.workflow.submit',$settlement->id) }}" data-confirm="Submit this settlement for review?" data-prevent-double-submit>@csrf
        <input type="hidden" name="note" value="Submitted from settlement screen"><button class="pdn-btn primary">Submit for Review</button>
    </form>
    @endcan
    @can('petro_pd_new.settlements.cancel')
    <form method="post" action="{{ route('petro-pd-new.settlements.cancel',$settlement->id) }}" class="pdn-inline" data-confirm="Cancel this settlement?" data-prevent-double-submit>@csrf
        <input class="pdn-input" name="reason" required placeholder="Cancellation reason"><button class="pdn-btn danger">Cancel</button>
    </form>
    @endcan
@elseif($settlement->status==='review')
    @can('petro_pd_new.workflow.review')
    <form method="post" action="{{ route('petro-pd-new.workflow.return',$settlement->id) }}" class="pdn-inline" data-prevent-double-submit>@csrf
        <input class="pdn-input" name="note" placeholder="Return note"><button class="pdn-btn warning">Return to Draft</button>
    </form>
    @endcan
    @can('petro_pd_new.workflow.approve')
    <form method="post" action="{{ route('petro-pd-new.workflow.approve',$settlement->id) }}" data-confirm="Approve this balanced settlement?" data-prevent-double-submit>@csrf
        <input type="hidden" name="note" value="Approved from settlement screen"><button class="pdn-btn success">Approve</button>
    </form>
    @endcan
@elseif($settlement->status==='approved')
    @can('petro_pd_new.workflow.finalize')
    <form method="post" action="{{ route('petro-pd-new.workflow.finalize',$settlement->id) }}" data-confirm="Finalize and lock this settlement? A final reference will be written to Pumper Dashboard-New." data-prevent-double-submit>@csrf
        <input type="hidden" name="note" value="Finalized from settlement screen"><button class="pdn-btn success">Finalize Settlement</button>
    </form>
    @endcan
@elseif($settlement->status==='finalized')
    @can('petro_pd_new.settlements.reopen')
    <form method="post" action="{{ route('petro-pd-new.workflow.reopen',$settlement->id) }}" class="pdn-inline" data-confirm="Reopen this finalized settlement and remove its PONE final reference?" data-prevent-double-submit>@csrf
        <input class="pdn-input" name="note" required placeholder="Reopen reason"><button class="pdn-btn warning">Reopen</button>
    </form>
    @endcan
@else
    <span class="pdn-badge {{ $settlement->status }}">{{ $settlement->status }}</span>
@endif
</div>
</div>
