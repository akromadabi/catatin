<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'transaction_limit',
        'description',
        'is_active',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
