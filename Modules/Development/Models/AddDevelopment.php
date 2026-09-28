<?php

namespace Modules\Development\Models;

use App\User;
use App\UserGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Development\Entities\DevelopmentModule;

class AddDevelopment extends Model
{
    protected $fillable = [
        'datetime',
        'doc_no',
        'user_id',
        'task_heading',
        'development_module_id',
        'type',
        'details',
        'related_doc_no',
        'priority',
        'visible_to_groups',
        'group_comments',
        'status',
        // 'image',
        'status_notes',
    ];

    protected $attributes = [
        'status' => 'Pending',
        'status_notes' => '[]',
        'group_comments' => '[]',
    ];

    protected $casts = [
        'visible_to_groups' => 'array',
        'group_comments' => 'array',
        'status_notes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function module()
    {
        return $this->belongsTo(DevelopmentModule::class, 'development_module_id');
    }
    

    public function getUserGroupsAttribute()
    {
        return $this->visible_to_groups;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'Urgent' => 'red',
            'Priority' => 'yellow',
            'Normal' => 'lightblue',
        ];
        return $colors[$this->priority] ?? 'lightblue';
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getStatusNoteAttribute()
    {
        return $this->status_notes ? $this->status_notes[0]['note'] ?? '' : '';
    }
}
