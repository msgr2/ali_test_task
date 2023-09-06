<?php

namespace App\Http\Controllers;

use App\Enums\CustomFieldEnum;
use App\Http\Resources\CustomFieldResource;
use App\Models\CustomField;
use App\Services\AuthService;
use Illuminate\Http\Request;

class CustomFieldsController extends Controller
{
    public function index()
    {
        $fields = CustomField::whereTeamId(auth()->user()->current_team_id)
            ->paginate(25);

        return CustomFieldResource::collection($fields);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'field_name' => 'required|string|max:255',
            'field_key' => 'required|string|max:255|in:' . join(',', CustomFieldEnum::toValues()),
        ]);

        $field = CustomField::whereTeamId(auth()->user()->current_team_id)
            ->whereFieldKey($validated['field_key'])
            ->withTrashed()
            ->first();

        if ($field?->trashed()) {
            $field->restore();

            $field->update([
                'field_name' => $validated['field_name'],
            ]);
        } else {
            $field = CustomField::create([
                'team_id' => auth()->user()->current_team_id,
                'field_name' => $validated['field_name'],
                'field_key' => $validated['field_key'],
            ]);
        }

        return response()->json([
            'data' => new CustomFieldResource($field),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $field = CustomField::findOrFail($id);

        AuthService::isModelOwner($field);

        $validated = $request->validate([
            'field_name' => 'required|string|max:255',
            'field_key' => 'required|string|max:255|in:' . join(',', CustomFieldEnum::toValues()),
        ]);

        $field->update([
            'field_name' => $validated['field_name'],
            'field_key' => $validated['field_key'],
        ]);

        return new CustomFieldResource($field);
    }

    public function destroy($id)
    {
        $field = CustomField::findOrFail($id);

        AuthService::isModelOwner($field);

        $field->delete();

        return response()->noContent();
    }
}
