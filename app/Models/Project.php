<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['user_id', 'name', 'icon', 'color'];

    /** Original owner (creator) */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /** All membership records */
    public function members()
    {
        return $this->hasMany(ProjectMember::class);
    }

    /** Active members as User models */
    public function collaborators()
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->wherePivot('status', 'active')
            ->withPivot('role', 'joined_at');
    }

    /** Activity log */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class)->orderByDesc('created_at');
    }

    /** Check if a user is the owner */
    public function isOwner($userId): bool
    {
        return $this->members()
            ->where('user_id', $userId)
            ->where('role', 'owner')
            ->where('status', 'active')
            ->exists();
    }

    /** Check if a user is an active member (owner or member) */
    public function isMember($userId): bool
    {
        return $this->members()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    /** Count active members */
    public function activeMemberCount(): int
    {
        return $this->members()->where('status', 'active')->count();
    }
}
