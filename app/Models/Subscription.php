<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function isValid()
    {
        return  now()->lt($this->ends_at);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
