<?php

// app/Models/BidangProgram.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BidangProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_bidang',
        'deskripsi',
        'aktif',
    ];

    public function programPenyalurans()
    {
        return $this->hasMany(ProgramPenyaluran::class);
    }
}