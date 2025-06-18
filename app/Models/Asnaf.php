<?php

// app/Models/Asnaf.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asnaf extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_asnaf',
        'deskripsi',
        'aktif',
    ];

    public function programPenyalurans()
    {
        return $this->hasMany(ProgramPenyaluran::class);
    }
}
