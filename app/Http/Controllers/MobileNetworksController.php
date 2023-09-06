<?php

namespace App\Http\Controllers;

use App\Http\Resources\MobileNetworkResource;
use App\Models\MobileNetwork;
use Doctrine\DBAL\Query;
use Illuminate\Http\Request;

class MobileNetworksController extends Controller
{
    /**
     * @param int $filter_country_id
     */
    public function index(Request $request)
    {
        $request->validate([
            'filter_country_id' => 'sometimes|exists:countries,id',
            'show_empty_brand_values' => 'sometimes|boolean',
        ]);

        return MobileNetworkResource::collection(MobileNetwork::query()
            ->when($request->has('filter_country_id'),
                function ($query) use ($request) {
                    $query->where(['country_id' => $request->get('filter_country_id')]);
                })
            ->when($request->isNotFilled('show_empty_brand_values', function ($query) {
                $query->whereNotNull('brand');
                $query->where('brand', '<>', '');
            }))
            ->get()
        );
    }

    public function store(Request $request)
    {
    }

    public function show(MobileNetwork $network)
    {
    }

    public function update(Request $request, MobileNetwork $network)
    {
    }

    public function destroy(MobileNetwork $network)
    {
    }
}
