<?php

namespace App\Models;

use App\Models\Report;
use App\Models\Developer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreApp extends Model
{
    use HasFactory;

    protected $table = 'apps';

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'app_id', 'id');
    }

    public function fakeAds(): HasMany
    {
        return $this->hasMany(FakeAd::class, 'app_id', 'id');
    }
}
