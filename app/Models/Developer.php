<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Developer extends Model
{
    use HasFactory;

    public function apps(): HasMany
    {
        return $this->hasMany(StoreApp::class, 'developer_id', 'id');
    }
}
