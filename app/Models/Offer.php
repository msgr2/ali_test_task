<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use SoftDeletes;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'url',
        'profit',
    ];

    public function getNeededParams()
    {
        $matches = [];
        $neededParams = [];
        preg_match('{(.*?)}', $this->url, $matches);
        if (empty($matches[1])) {
            return [];
        }
        foreach ($matches[1] as $param) {
            $neededParams[] = $param;
        }

        return $neededParams;
    }
}
