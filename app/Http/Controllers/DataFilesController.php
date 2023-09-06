<?php

namespace App\Http\Controllers;

use App\Enums\DataFileStatusEnum;
use App\Http\Resources\DataFileResource;
use App\Http\Resources\SampleRowsResource;
use App\Imports\ContactsImport;
use App\Jobs\DataFileImportJob;
use App\Models\DataFile;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DataFilesController extends Controller
{
    public function index()
    {
        $dataFiles = DataFile::whereTeamId(auth()->user()->current_team_id)
            ->orderByDesc('created_at')
            ->paginate(25);

        return DataFileResource::collection($dataFiles);
    }

    public function uploadContacts(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:txt,csv,xlsx,xls|max:' . (50 * 1024),
        ]);

        $file = $request->file('file');
        $file->store('teams/' . auth()->user()->current_team_id . '/data-files');

        $dataFile = DataFile::create([
            'team_id' => auth()->user()->current_team_id,
            'name' => $file->getClientOriginalName(),
            'file_name' => $file->hashName(),
            'file_size' => $file->getSize(),
            'status_id' => DataFileStatusEnum::pending()->value,
            'meta' => [],
        ]);

        return new DataFileResource($dataFile);
    }

    public function sample($id)
    {
        $dataFile = DataFile::findOrFail($id);

        AuthService::isModelOwner($dataFile);

        $contacts = new ContactsImport($dataFile);

        return new SampleRowsResource($contacts->getSampleRows());
    }

    public function startImport($id, Request $request)
    {
        $dataFile = DataFile::findOrFail($id);

        AuthService::isModelOwner($dataFile);

        if (!$dataFile->isPending()) {
            return response([
                'message' => 'Data file is not in pending status.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $request->validate([
            'columns' => 'required|array',
            'columns.number' => 'required_without:columns.email|numeric|min:0',
            'columns.country' => 'required_without:columns.email|numeric|min:0',
            'columns.email' => 'required_without:columns.number|numeric|min:0',
            'columns.name' => 'sometimes|numeric|min:0',
            'columns.custom1_str' => 'sometimes|numeric|min:0',
            'columns.custom2_str' => 'sometimes|numeric|min:0',
            'columns.custom3_str' => 'sometimes|numeric|min:0',
            'columns.custom4_str' => 'sometimes|numeric|min:0',
            'columns.custom5_str' => 'sometimes|numeric|min:0',
            'columns.custom1_int' => 'sometimes|numeric|min:0',
            'columns.custom2_int' => 'sometimes|numeric|min:0',
            'columns.custom3_int' => 'sometimes|numeric|min:0',
            'columns.custom4_int' => 'sometimes|numeric|min:0',
            'columns.custom5_int' => 'sometimes|numeric|min:0',
            'columns.custom1_dec' => 'sometimes|numeric|min:0',
            'columns.custom2_dec' => 'sometimes|numeric|min:0',
            'columns.custom1_datetime' => 'sometimes|numeric|min:0',
            'columns.custom2_datetime' => 'sometimes|numeric|min:0',
            'columns.custom3_datetime' => 'sometimes|numeric|min:0',
            'columns.custom4_datetime' => 'sometimes|numeric|min:0',
            'columns.custom5_datetime' => 'sometimes|numeric|min:0',
            'columns.foreign_id' => 'sometimes',
            'tags' => 'sometimes|array',
            'tags.*' => 'string',
            'fixed_country_id' => 'sometimes|numeric|exists:countries,id',
        ]);

        $keys = array_unique(array_keys($request->get('columns')));
        $values = array_unique(array_values($request->get('columns')));

        if (count($keys) !== count($values)) {
            return response([
                'message' => 'Columns must be unique.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $dataFile->meta = array_merge($dataFile->meta, [
            'fixed_country_id' => $request->get('fixed_country_id') ?? null,
            'columns' => $request->get('columns'),
            'tags' => $request->get('tags') ?? null,
        ]);
        $dataFile->saveOrFail();

        DataFileImportJob::dispatch($dataFile);

        return new DataFileResource($dataFile);
    }
}
