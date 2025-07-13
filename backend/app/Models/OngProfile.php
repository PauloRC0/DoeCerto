<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OngProfile extends Model
{
    use HasFactory;

    protected $table = 'ong_profiles';

    protected $fillable = [
        'ong_id',
        'description',
        'image',
        'phone',
    ];

    public function ong()
    {
        return $this->belongsTo(Ong::class);
    }
}
