<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TahunAnggaran extends Model
{
    protected $fillable = [
        'tahun',
        'is_active',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'is_active' => 'boolean',
            'is_current' => 'boolean',
        ];
    }

    public static function activeYears(): Collection
    {
        return static::query()
            ->where('is_active', true)
            ->orderByDesc('tahun')
            ->pluck('tahun');
    }

    public static function allYears(): Collection
    {
        return static::query()
            ->orderByDesc('tahun')
            ->pluck('tahun');
    }

    public static function currentYear(): int
    {
        return (int) (static::query()->where('is_current', true)->value('tahun') ?? now()->year);
    }
}
