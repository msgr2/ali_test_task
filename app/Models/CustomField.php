<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomField extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'team_id',
        'field_name',
        'field_key',
    ];
}
