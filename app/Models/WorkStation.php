<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'area_name',
        'pic_name',
        'status',
        'description',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'active' => ['label' => 'Lini Aktif', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
            'inactive' => ['label' => 'Lini Istirahat/Nonaktif', 'class' => 'bg-slate-100 text-slate-800 border-slate-300'],
            default => ['label' => ucfirst($this->status), 'class' => 'bg-gray-100 text-gray-800 border-gray-300'],
        };
    }
}
