<?php

// app/Models/SumberDanaPenyaluran.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SumberDanaPenyaluran extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_sumber_dana',
        'deskripsi',
        'aktif',
    ];

    public function programPenyalurans()
    {
        return $this->hasMany(ProgramPenyaluran::class);
    }

        public function jenisDonasis()
    {
        return $this->hasMany(JenisDonasi::class);
    }
// 
}