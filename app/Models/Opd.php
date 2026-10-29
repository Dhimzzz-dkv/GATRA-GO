<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Concerns\EnforcesLazyLoading;

class Opd extends Model
{
    use HasFactory, SoftDeletes, EnforcesLazyLoading;

    protected $fillable = [
        'kode',
        'nama',
        'singkatan',
        'kepala_opd',
        'nip_kepala',
        'alamat',
        'telepon',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get user accounts associated with this OPD.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all findings related to this OPD.
     */
    public function temuans(): HasMany
    {
        return $this->hasMany(Temuan::class);
    }

    /**
     * Get all LHPs targeting this OPD (Many-to-Many via lhp_opd).
     */
    public function lhps(): BelongsToMany
    {
        return $this->belongsToMany(Lhp::class, 'lhp_opd')->withTimestamps();
    }

    /**
     * Scope query to only active OPDs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
