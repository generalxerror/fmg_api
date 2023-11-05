<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Report extends Model
{
    use HasFactory;

    public function storeApp(): HasOne
    {
        return $this->hasOne(StoreApp::class, 'id', 'app_id');
    }
}
