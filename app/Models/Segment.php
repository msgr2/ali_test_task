<?php

namespace App\Models;

use App\Services\SegmentBuilderService;
use ClickHouseDB\Query\Query;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use PhpClickHouseLaravel\Builder;

class Segment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'team_id',
        'type',
        'name',
        'meta',
        'status_id',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function getBuilder(): ?Builder
    {
        return SegmentBuilderService::create($this);
    }

    public function getWhereFromSegment(): ?Query
    {
        return SegmentBuilderService::getWhereFromSegment($this);
    }
}
