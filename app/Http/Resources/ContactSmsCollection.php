<?php

namespace App\Http\Resources;

use App\Models\Clickhouse\Views\ContactSmsView;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ContactSmsCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $query = ContactSmsView::where('team_id', auth()->user()->current_team_id)->get();

        return [
            'data' => $this->collection,
            'meta' => [
                'page' => (int)$request->get('page', 1),
                'per_page' => (int)$request->get('per_page', 25),
                'total' => (int)$query->count(),
            ],
        ];
    }
}
