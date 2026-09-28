<?php
namespace Modules\EzyLaw\Entities;
class LawMatter extends LawModel
{
    protected $table='law_matters'; protected $guarded=['id'];
    protected $casts=['opened_on'=>'date','closed_on'=>'date','estimated_value'=>'decimal:4','hourly_rate'=>'decimal:4','fixed_fee'=>'decimal:4','retainer_amount'=>'decimal:4'];
    public function client(){ return $this->belongsTo(LawClient::class,'client_id'); }
    public function practiceArea(){ return $this->belongsTo(LawPracticeArea::class,'practice_area_id'); }
    public function court(){ return $this->belongsTo(LawCourt::class,'court_id'); }
    public function hearings(){ return $this->hasMany(LawHearing::class,'matter_id'); }
    public function tasks(){ return $this->hasMany(LawTask::class,'matter_id'); }
    public function timeEntries(){ return $this->hasMany(LawTimeEntry::class,'matter_id'); }
    public function parties(){ return $this->hasMany(LawMatterParty::class,'matter_id'); }
    public function chronology(){ return $this->hasMany(LawChronologyEntry::class,'matter_id')->orderByDesc('event_at'); }
    public function retainers(){ return $this->hasMany(LawRetainer::class,'matter_id'); }
    public function reminders(){ return $this->hasMany(LawReminder::class,'matter_id'); }
    public function communications(){ return $this->hasMany(LawCommunication::class,'matter_id'); }
    public function stageHistory(){ return $this->hasMany(LawMatterStageHistory::class,'matter_id')->orderByDesc('started_at'); }
    public function deadlines(){ return $this->hasMany(LawDeadline::class,'matter_id')->orderBy('due_at'); }
    public function evidence(){ return $this->hasMany(LawEvidenceItem::class,'matter_id'); }
    public function filings(){ return $this->hasMany(LawCourtFiling::class,'matter_id')->orderByDesc('filed_on'); }
    public function settlements(){ return $this->hasMany(LawSettlement::class,'matter_id')->orderByDesc('id'); }
    public function researchItems(){ return $this->hasMany(LawResearchItem::class,'matter_id'); }
    public function estimates(){ return $this->hasMany(LawFeeEstimate::class,'matter_id'); }
    public function closure(){ return $this->hasOne(LawMatterClosure::class,'matter_id'); }
}
