<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsRouteSmppConnection extends Model
{
    use SoftDeletes, HasFactory, HasUuids;

    static array $rules = [
        'url' => ['required'],
        'username' => ['required'],
        'password' => ['required'],
        'port' => ['integer', 'required'],
        'dlr_url' => ['nullable'],
        'dlr_port' => ['nullable', 'integer'],
        'workers_count' => ['nullable', 'integer'],
        'workers_delay' => ['nullable', 'numeric'],
    ];

    protected $fillable = [
        'url',
        'username',
        'password',
        'port',
        'dlr_url',
        'dlr_port',
    ];

    public function route()
    {
        return $this->morphMany(SmsRoute::class, 'connection');
    }
}
