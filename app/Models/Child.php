<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Child extends Model
{
    use HasFactory;

    protected $hidden = ["password"];
    protected $appends = ["profile_pic_url"];

    public function getProfilePicUrlAttribute()
    {
        return $this->profile_pic != null && Storage::exists($this->profile_pic) ? asset(Storage::url($this->profile_pic)) : null;
    }

    public function relation()
    {
        return $this->belongsTo(Relationship::class,'relationship_id');
    }
}
