<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthMember extends MyHealthBaseModel
{
    protected $table = 'myhealth_members';
    protected $guarded = ['id'];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    public function login()
    {
        return $this->hasOne(MyHealthMemberLogin::class, 'member_id');
    }

    public function medicalHistory()
    {
        return $this->hasOne(MyHealthMedicalHistory::class, 'member_id');
    }

    public function consultations()
    {
        return $this->hasMany(MyHealthConsultation::class, 'member_id');
    }

    public function diagnoses()
    {
        return $this->hasMany(MyHealthDiagnosis::class, 'member_id');
    }

    public function prescriptions()
    {
        return $this->hasMany(MyHealthPrescription::class, 'member_id');
    }

    public function labRequests()
    {
        return $this->hasMany(MyHealthLabRequest::class, 'member_id');
    }

    public function documents()
    {
        return $this->hasMany(MyHealthDocument::class, 'member_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(MyHealthAuditLog::class, 'member_id');
    }

    public function getStatusLabelAttribute(): string
    {
        if (isset($this->status) && $this->status) {
            return ucwords(str_replace('_', ' ', $this->status));
        }

        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }
}
