<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContactSmsCollection;
use App\Models\Clickhouse\Views\ContactSmsView;
use Illuminate\Http\Request;

class ContactsController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'sometimes|integer',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $page = (int)$request->get('page', 1);
        $perPage = (int)$request->get('per_page', 25);
        $offset = ($page - 1) * $perPage;
        $contacts = ContactSmsView::where('team_id', auth()->user()->current_team_id)
            ->orderByAsc('contact_id')
            ->take($perPage, $offset)
            ->getRows();

        return new ContactSmsCollection($contacts);
    }
}
