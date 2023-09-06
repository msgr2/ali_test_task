<?php

namespace App\Http\Controllers;

use App\Enums\JqBuilderFieldEnum;
use App\Enums\JqBuilderOperatorEnum;
use App\Enums\SegmentStatusEnum;
use App\Enums\SegmentTypeEnum;
use App\Http\Resources\ContactSmsResource;
use App\Http\Resources\JqFieldResource;
use App\Http\Resources\SegmentResource;
use App\Models\CustomField;
use App\Models\Segment;
use App\Rules\JqQueryGroup;
use App\Services\AuthService;
use Illuminate\Http\Request;

class SegmentsController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'sometimes|in:' . implode(',', SegmentTypeEnum::toLabels()),
        ]);

        $segments = Segment::whereTeamId(auth()->user()->current_team_id)
            ->whereStatusId(SegmentStatusEnum::active()->value)
            ->when($request->has('type'), function ($query) use ($request) {
                $query->whereType(SegmentTypeEnum::from($request->get('type'))->value);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return SegmentResource::collection($segments);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', SegmentTypeEnum::toLabels()),
            /**
             * @var array<string, string> $query
             * @example {"condition":"AND","rules":[{"field":"clicked_count","operator":"greater","value":0},{"field":"country_id","operator":"equal","value":225},{"condition":"OR","rules":[{"field":"leads_count","operator":"equal","value":1},{"field":"sales_count","operator":"equal","value":1}]},{"field":"date_created","operator":"equal","value":"2023-07-05"}]}
             */
            'query' => ['required', 'array', new JqQueryGroup],
        ]);

        $segment = Segment::create([
            'team_id' => auth()->user()->current_team_id,
            'type' => SegmentTypeEnum::from($request->get('type'))->value,
            'name' => $request->get('name'),
            'meta' => [
                'query' => $request->get('query'),
            ],
            'status_id' => SegmentStatusEnum::active()->value,
        ]);

        return new SegmentResource($segment);
    }

    public function update(Request $request, $id)
    {
        $segment = Segment::findOrFail($id);

        AuthService::isModelOwner($segment);

        $request->validate([
            'name' => 'required|string|max:255',
            /**
             * @var array<string, string> $query
             * @example {"condition":"AND","rules":[{"field":"clicked_count","operator":"greater","value":0},{"field":"country_id","operator":"equal","value":225},{"condition":"OR","rules":[{"field":"leads_count","operator":"equal","value":1},{"field":"sales_count","operator":"equal","value":1}]},{"field":"date_created","operator":"equal","value":"2023-07-05"}]}
             */
            'query' => ['required', 'array', new JqQueryGroup],
        ]);

        $segment->update([
            'name' => $request->get('name'),
            'meta' => [
                'query' => $request->get('query'),
            ],
        ]);

        return new SegmentResource($segment);
    }

    public function destroy($id)
    {
        $segment = Segment::findOrFail($id);

        AuthService::isModelOwner($segment);

        $segment->delete();

        return response()->noContent();
    }

    public function preview(Request $request)
    {
        $request->validate([
            'type' => 'required|in:' . implode(',', SegmentTypeEnum::toLabels()),
            /**
             * @var array<string, string> $query
             * @example {"condition":"AND","rules":[{"field":"clicked_count","operator":"greater","value":0},{"field":"country_id","operator":"equal","value":225},{"condition":"OR","rules":[{"field":"leads_count","operator":"equal","value":1},{"field":"sales_count","operator":"equal","value":1}]},{"field":"date_created","operator":"equal","value":"2023-07-05"}]}
             */
            'query' => ['required', 'array', new JqQueryGroup],
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $segment = Segment::make([
            'team_id' => auth()->user()->current_team_id,
            'type' => SegmentTypeEnum::from($request->get('type'))->value,
            'name' => 'Preview',
            'meta' => [
                'query' => $request->get('query'),
            ],
            'status_id' => SegmentStatusEnum::active()->value,
        ]);

        $builder = $segment->getBuilder();
        $total = $builder->get()->count();
        $stats = $builder->get()->statistics();

        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $query = $builder->limit($perPage, $offset);
        $rows = match ($segment->type) {
            SegmentTypeEnum::numbers()->value => ContactSmsResource::collection($query->getRows()),
//            SegmentTypeEnum::emails()->value => new ContactSmsCollection($query->getRows()),
        };

        $response = [
            'total' => (int)$total,
            /** @var array<ContactSmsResource> $rows */
            'rows' => $rows,
            /** @var array<string, string> $stats only for admins (elapsed, rows_read, bytes_read) (null for users) */
            'stats' => null,
            /** @var string $sql only for admins (null for users) */
            'sql' => null,
        ];

        if (AuthService::isAdmin()) {
            $response['stats'] = $stats;
            $response['sql'] = $builder->toSql();
        }

        return response()->json($response);
    }

    /**
     * /v1/segments/fields
     *
     * Example response:
     * ```json
     * {
     * "fields": [
     * {
     * "field": "clicked_count",
     * "label": "Clicked Count",
     * "type": "integer",
     * "operators": [
     * "equal",
     * "not_equal",
     * "less",
     * "less_or_equal",
     * "greater",
     * "greater_or_equal"
     * ]
     * },
     * {
     * "field": "sent_count",
     * "label": "Sent Count",
     * "type": "integer",
     * "operators": [
     * "equal",
     * "not_equal",
     * "less",
     * "less_or_equal",
     * "greater",
     * "greater_or_equal"
     * ]
     * },
     * {
     * "field": "country_id",
     * "label": "Country",
     * "type": "integer",
     * "operators": [
     * "equal",
     * "not_equal",
     * "in",
     * "not_in"
     * ]
     * },
     * {
     * "field": "state_id",
     * "label": "State",
     * "type": "integer",
     * "operators": [
     * "equal",
     * "not_equal",
     * "in",
     * "not_in"
     * ]
     * },
     * {
     * "field": "network_brand",
     * "label": "Network Brand",
     * "type": "string",
     * "operators": [
     * "contains",
     * "not_contains",
     * "begins_with",
     * "not_ends_with",
     * "ends_with",
     * "not_ends_with",
     * "is_empty",
     * "is_not_empty"
     * ]
     * },
     * {
     * "field": "date_created",
     * "label": "Date Created",
     * "type": "date",
     * "operators": [
     * "equal",
     * "not_equal",
     * "less",
     * "less_or_equal",
     * "greater",
     * "greater_or_equal"
     * ]
     * },
     * {
     * "field": "last_sent",
     * "label": "Last Sent",
     * "type": "date",
     * "operators": [
     * "equal",
     * "not_equal",
     * "less",
     * "less_or_equal",
     * "greater",
     * "greater_or_equal"
     * ]
     * },
     * {
     * "field": "last_clicked",
     * "label": "Last Clicked",
     * "type": "date",
     * "operators": [
     * "equal",
     * "not_equal",
     * "less",
     * "less_or_equal",
     * "greater",
     * "greater_or_equal"
     * ]
     * },
     * {
     * "field": "leads_count",
     * "label": "Leads Count",
     * "type": "integer",
     * "operators": [
     * "equal",
     * "not_equal",
     * "less",
     * "less_or_equal",
     * "greater",
     * "greater_or_equal"
     * ]
     * },
     * {
     * "field": "sales_count",
     * "label": "Sales Count",
     * "type": "integer",
     * "operators": [
     * "equal",
     * "not_equal",
     * "less",
     * "less_or_equal",
     * "greater",
     * "greater_or_equal"
     * ]
     * },
     * {
     * "field": "custom1_str",
     * "label": "Qwerty",
     * "type": "string",
     * "operators": [
     * "contains",
     * "not_contains",
     * "begins_with",
     * "not_ends_with",
     * "ends_with",
     * "not_ends_with",
     * "is_empty",
     * "is_not_empty"
     * ]
     * },
     * {
     * "field": "custom2_str",
     * "label": "Custom 2 String",
     * "type": "string",
     * "operators": [
     * "contains",
     * "not_contains",
     * "begins_with",
     * "not_ends_with",
     * "ends_with",
     * "not_ends_with",
     * "is_empty",
     * "is_not_empty"
     * ]
     * },
     * {
     * "field": "custom3_str",
     * "label": "Custom 3 String",
     * "type": "string",
     * "operators": [
     * "contains",
     * "not_contains",
     * "begins_with",
     * "not_ends_with",
     * "ends_with",
     * "not_ends_with",
     * "is_empty",
     * "is_not_empty"
     * ]
     * },
     * {
     * "field": "custom4_str",
     * "label": "Custom 4 String",
     * "type": "string",
     * "operators": [
     * "contains",
     * "not_contains",
     * "begins_with",
     * "not_ends_with",
     * "ends_with",
     * "not_ends_with",
     * "is_empty",
     * "is_not_empty"
     * ]
     * },
     * {
     * "field": "custom5_str",
     * "label": "Custom 5 String",
     * "type": "string",
     * "operators": [
     * "contains",
     * "not_contains",
     * "begins_with",
     * "not_ends_with",
     * "ends_with",
     * "not_ends_with",
     * "is_empty",
     * "is_not_empty"
     * ]
     * },
     * {
     * "field": "custom1_dec",
     * "label": "Custom 1 Decimal",
     * "type": "double",
     * "operators": [
     * "equal",
     * "not_equal",
     * "less",
     * "less_or_equal",
     * "greater",
     * "greater_or_equal"
     * ]
     * },
     * {
     * "field": "custom2_dec",
     * "label": "Custom 2 Decimal",
     * "type": "double",
     * "operators": [
     * "equal",
     * "not_equal",
     * "less",
     * "less_or_equal",
     * "greater",
     * "greater_or_equal"
     * ]
     * },
     * {
     * "field": "custom1_datetime",
     * "label": "Dt",
     * "type": "datetime",
     * "operators": [
     * "equal",
     * "not_equal",
     * "less",
     * "less_or_equal",
     * "greater",
     * "greater_or_equal"
     * ]
     * },
     * {
     * "field": "custom2_datetime",
     * "label": "Custom 2 Datetime",
     * "type": "datetime",
     * "operators": [
     * "equal",
     * "not_equal",
     * "less",
     * "less_or_equal",
     * "greater",
     * "greater_or_equal"
     * ]
     * },
     * {
     * "field": "custom3_datetime",
     * "label": "Custom 3 Datetime",
     * "type": "datetime",
     * "operators": [
     * "equal",
     * "not_equal",
     * "less",
     * "less_or_equal",
     * "greater",
     * "greater_or_equal"
     * ]
     * },
     * {
     * "field": "custom4_datetime",
     * "label": "Custom 4 Datetime",
     * "type": "datetime",
     * "operators": [
     * "equal",
     * "not_equal",
     * "less",
     * "less_or_equal",
     * "greater",
     * "greater_or_equal"
     * ]
     * },
     * {
     * "field": "custom5_datetime",
     * "label": "Custom 5 Datetime",
     * "type": "datetime",
     * "operators": [
     * "equal",
     * "not_equal",
     * "less",
     * "less_or_equal",
     * "greater",
     * "greater_or_equal"
     * ]
     * },
     * {
     * "field": "tags",
     * "label": "Tag",
     * "type": "string",
     * "operators": [
     * "contains",
     * "not_contains",
     * "begins_with",
     * "not_ends_with",
     * "ends_with",
     * "not_ends_with"
     * ]
     * }
     * ],
     * "operators": [
     * "equal",
     * "not_equal",
     * "in",
     * "not_in",
     * "less",
     * "less_or_equal",
     * "greater",
     * "greater_or_equal",
     * "begins_with",
     * "not_begins_with",
     * "contains",
     * "not_contains",
     * "ends_with",
     * "not_ends_with",
     * "is_empty",
     * "is_not_empty"
     * ]
     * }
     * ```
     */
    public function fields()
    {
        $customFields = CustomField::whereTeamId(auth()->user()->current_team_id)
            ->get()
            ->map(function ($field) {
                $arr = [];
                $arr[$field->field_key] = $field->field_name;
                return $arr;
            })
            ->flatMap(fn($item) => $item)
            ->toArray();

        $fields = collect(JqBuilderFieldEnum::toLabels())
            ->map(function ($fieldName) use ($customFields) {
                $field = JqBuilderFieldEnum::from($fieldName);

                return $field->toJqRule($customFields);
            });
        $operators = JqBuilderOperatorEnum::toLabels();

        return response()->json([
            /** @var array<JqFieldResource> $fields */
            'fields' => JqFieldResource::collection($fields),
            /** @var array<string> $operators */
            'operators' => $operators,
        ]);
    }
}
