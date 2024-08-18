<?php

namespace App\Http\Controllers;

use Common\Database\Datasource\Datasource;
use App\Models\FileEntry;
use Common\Core\BaseController;
use Common\Tags\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use const ast\flags\RETURNS_REF;
use Illuminate\Support\Facades\DB;

class StarredEntriesController extends BaseController
{
    public const TAG_NAME = 'starred';

    public function __construct(private Request $request, private Tag $tag)
    {
    }

    public function index()
    {
        // $this->authorize('index', Tag::class);
             // $this->authorize('index', Tag::class);
             $results = DB::table('taggables')
             ->where('user_id', Auth::id() ?? null)
             ->orderBy('id', 'desc') // Order by ID descending
             ->limit(10) // Limit to 10 results
            ->get();

        return $this->success(['results' => $results]);
    }




    public function add(): JsonResponse
    {
        $entryIds = $this->request->get('entryIds');

        $this->validate($this->request, [
            'entryIds' => 'required|array|exists:file_entries,id',
        ]);

        // $this->authorize('update', [FileEntry::class, $entryIds]);

        $tag = $this->tag->where('name', self::TAG_NAME)->first();
        $id =  $this->request->user()->id ?? null;
        $tag->attachEntries($entryIds, $id );

        return $this->success(['tag' => $tag]);
    }

    public function remove(): JsonResponse
    {
        $entryIds = $this->request->get('entryIds');

        $this->validate($this->request, [
            'entryIds' => 'required|array|exists:file_entries,id',
        ]);

        // $this->authorize('update', [FileEntry::class, $entryIds]);

        $tag = $this->tag->where('name', self::TAG_NAME)->first();
        $id =  $this->request->user()->id ?? null;
        $tag->detachEntries($entryIds, $id );

        return $this->success(['tag' => $tag]);
    }

}
