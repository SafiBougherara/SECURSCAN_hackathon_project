<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scan extends Model
{
    protected $fillable = [
        'user_id',
        'repo_url',
        'repo_name',
        'status',
        'score',
        'parent_scan_id',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Scan::class, 'parent_scan_id');
    }

    public function children()
    {
        return $this->hasMany(Scan::class, 'parent_scan_id');
    }

    public function vulnerabilities(): HasMany
    {
        return $this->hasMany(Vulnerability::class);
    }

    public function getScoreColorAttribute(): string
    {
        if ($this->score === null)
            return 'gray';
        if ($this->score >= 70)
            return 'green';
        if ($this->score >= 40)
            return 'yellow';
        return 'red';
    }
}
