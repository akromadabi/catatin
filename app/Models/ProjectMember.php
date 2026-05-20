<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMember extends Model
{
    protected $fillable = [
        'project_id', 'user_id', 'role', 'status',
        'invite_token', 'invited_at', 'joined_at',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'joined_at'  => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}
