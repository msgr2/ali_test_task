<?php

namespace App\Models;

use App\Enums\DataFileStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataFile extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'team_id',
        'name',
        'file_name',
        'file_size',
        'status_id',
        'meta',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'meta' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function getColumns(): array
    {
        return $this->meta['columns'] ?? [];
    }

    public function isPending(): bool
    {
        return $this->status_id === DataFileStatusEnum::pending()->value;
    }

    public function getFilePath(): string
    {
        return 'teams/' . $this->team_id . '/data-files/' . $this->file_name;
    }
}
