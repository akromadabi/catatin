<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'project_id', 'user_id', 'action',
        'model_type', 'model_id', 'data', 'undone',
    ];

    protected $casts = [
        'data'   => 'array',
        'undone' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
