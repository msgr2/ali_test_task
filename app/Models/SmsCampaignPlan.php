<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsCampaignPlan extends Model
{
    use SoftDeletes;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'team_id',
    ];

    public function addSettings(array $array): void
    {
        $this->meta = json_encode($array);
        $this->save();
    }

    public function getSettings()
    {
        $meta = json_decode($this->meta, true);

        return $meta ? $meta : [];
    }
}
