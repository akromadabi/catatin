<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'google_id', 'role', 'package_id', 'avatar', 'whatsapp_number', 'active_project_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public function activeProject() {
        return $this->belongsTo(Project::class, 'active_project_id');
    }

    public function categories() {
        return $this->hasMany(Category::class);
    }
    
    public function transactions() {
        return $this->hasMany(Transaction::class);
    }

    public function projects() {
        return $this->hasMany(Project::class);
    }

    /** All projects this user can access (owned + collaborated) */
    public function accessibleProjects() {
        return Project::whereHas('members', function ($q) {
            $q->where('user_id', $this->id)->where('status', 'active');
        });
    }

    /** Membership records */
    public function projectMemberships() {
        return $this->hasMany(ProjectMember::class);
    }
    
    public function package() {
        return $this->belongsTo(Package::class);
    }
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, \NotificationChannels\WebPush\HasPushSubscriptions;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
