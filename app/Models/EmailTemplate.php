<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class EmailTemplate extends Model
{
    use CentralConnection;

    protected $fillable = ['key', 'subject', 'body', 'locale', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
