<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\EnforcesLazyLoading;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory, EnforcesLazyLoading;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'sumber_pemeriksaan',
    ];

    /**
     * Get users that belong to this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if role is Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->name === 'super_admin';
    }

    /**
     * Check if role is an Admin Sumber (BPK, BPKP, ITJEN, Inspektorat).
     */
    public function isAdminSumber(): bool
    {
        return in_array($this->name, ['admin_bpk', 'admin_bpkp', 'admin_itjen', 'admin_inspektorat']);
    }

    /**
     * Check if role is Pemantau.
     */
    public function isPemantau(): bool
    {
        return $this->name === 'pemantau';
    }

    /**
     * Check if role is OPD.
     */
    public function isOpd(): bool
    {
        return $this->name === 'opd';
    }
}
