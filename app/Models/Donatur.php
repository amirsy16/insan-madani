<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Donatur extends Model {
    use HasFactory;
    
    protected $fillable = [
        'kode_donatur', 'gender', 'nama', 'alamat_detail', 'alamat_lengkap',
        'province_id', 'city_id', 'district_id', 'village_id',
        'nomor_hp', 'email', 'pekerjaan_id'
        // 'institusi', 'npwp', 'catatan_internal' // Removed these fields
    ];
    
    protected $casts = [
        'gender' => 'string',
    ];
    
    // Relationships
    public function donasis(): HasMany {
        return $this->hasMany(Donasi::class, 'donatur_id');
    }
    
    public function pekerjaan(): BelongsTo {
        return $this->belongsTo(Pekerjaan::class);
    }
    
    public function province(): BelongsTo {
        return $this->belongsTo(Province::class);
    }
    
    public function regency(): BelongsTo {
        return $this->belongsTo(Regency::class, 'city_id');
    }
    
    public function district(): BelongsTo {
        return $this->belongsTo(District::class);
    }
    
    public function village(): BelongsTo {
        return $this->belongsTo(Village::class);
    }
    
    // Accessors
    public function getNamaLengkapAttribute(): string {
        if ($this->gender === 'male') {
            return 'Bapak ' . $this->nama;
        } elseif ($this->gender === 'female') {
            return 'Ibu ' . $this->nama;
        } else {
            // Untuk organization atau value lainnya, return nama saja tanpa prefix
            return $this->nama;
        }
    }
    
    // Generate kode donatur otomatis
    public static function generateNewKodeDonatur(): string
    {
        $prefix = 'DN'; // Prefix untuk kode donatur
        $currentYearYY = Carbon::now()->format('y'); // Dua digit tahun, misal '25' untuk 2025

        // Cari donatur terakhir yang dibuat pada tahun ini untuk mendapatkan nomor urut berikutnya
        $searchPrefix = $prefix . $currentYearYY;

        $lastDonaturThisYear = self::where('kode_donatur', 'like', "{$searchPrefix}%")
                                    ->orderBy('kode_donatur', 'desc')
                                    ->first();

        $nextSequence = 1; // Default nomor urut jika belum ada donatur di tahun ini

        if ($lastDonaturThisYear && $lastDonaturThisYear->kode_donatur) {
            // Ekstrak nomor urut dari kode_donatur terakhir
            // Contoh: DN250001 -> ekstrak 0001
            $sequencePart = substr($lastDonaturThisYear->kode_donatur, strlen($prefix) + 2);
            if (is_numeric($sequencePart)) {
                $lastSequence = (int) $sequencePart;
                $nextSequence = $lastSequence + 1;
            }
        }
        
        $newKode = '';
        // Loop untuk memastikan keunikan
        do {
            // Format nomor urut menjadi 4 digit dengan padding nol di depan
            $formattedSequence = sprintf('%04d', $nextSequence);
            $newKode = $searchPrefix . $formattedSequence;
            $nextSequence++; // Persiapkan untuk iterasi berikutnya jika kode sudah ada
        } while (self::where('kode_donatur', $newKode)->exists());
        
        return $newKode;
    }
    
    // Generate full address when saving and kode donatur
    protected static function booted()
    {
        static::creating(function ($donatur) {
            // Generate kode donatur jika belum ada
            if (empty($donatur->kode_donatur)) {
                $donatur->kode_donatur = self::generateNewKodeDonatur();
            }
        });
        
        static::saving(function ($donatur) {
            $donatur->alamat_lengkap = $donatur->generateFullAddress();
        });
    }
    
    // Method to generate full address
    public function generateFullAddress(): string
    {
        $parts = [];
        
        if (!empty($this->alamat_detail)) {
            $parts[] = $this->alamat_detail;
        }
        
        if ($this->village_id && $this->village) {
            $parts[] = $this->village->name;
        }
        
        if ($this->district_id && $this->district) {
            $parts[] = $this->district->name;
        }
        
        if ($this->city_id && $this->city) {
            $parts[] = $this->city->name;
        }
        
        if ($this->province_id && $this->province) {
            $parts[] = $this->province->name;
        }
        
        return !empty($parts) ? implode(', ', $parts) : '-';
    }
}



