<?php

namespace Sobhanatar\Idempotent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Sobhanatar\Idempotent\Database\Factories\IdempotentFactory;

class Idempotent extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @return IdempotentFactory
     */
    protected static function newFactory(): IdempotentFactory
    {
        return IdempotentFactory::new();
    }
}
