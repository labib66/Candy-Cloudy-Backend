<?php namespace Common\Pages;

use Illuminate\Support\Facades\Auth;
use Common\Core\BaseController;
use Common\Database\Datasource\Datasource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\PageResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomPageController extends BaseController
{
    /**
     * CustomPage model might get overwritten with
     * parent page model, for example, LinkPage
     */
    public function __construct(
        protected CustomPage $page,
        protected Request $request,
    ) {
    }

    public function index()
    {
        $userId = $this->request->get('userId');
        $this->authorize('index', [get_class($this->page), $userId]);

        $builder = $this->page->newQuery();

        // make sure we filter by page type on full text engines for
        // example, only search "link page" on "meilisearch" on "belink"
        $modelType = $this->page::PAGE_TYPE;
        $pageType = $this->request->get(
            'type',
            $modelType !== 'default' ? $modelType : null,
        );
        if ($pageType) {
            $builder->where('type', '=', $pageType);
        }

        if ($userId) {
            $builder->where('user_id', '=', $userId);
        }

        $pagination = (new Datasource($builder, $this->request->all()))
            ->paginate()
            ->toArray();

        $pagination['data'] = array_map(function ($page) {
            $page['body'] = Str::limit(strip_tags($page['body']), 100);
            return $page;
        }, $pagination['data']);

        return $this->success(['pagination' => $pagination]);
    }

    public function show(int|string $id)
    {
        $page = $this->page
            ->where('slug', $id)
            ->orWhere('id', $id)
            ->firstOrFail();

        $this->authorize('show', $page);

        return $this->renderClientOrApi([
            'pageName' => 'custom-page',
            'data' => [
                'page' => $page,
                'loader' => 'customPage',
            ],
        ]);
    }

    public function store()
    {
        $this->authorize('store', get_class($this->page));

        $validatedData = $this->validate($this->request, [
            'title' => [
                'string',
                'min:3',
                'max:250',
                Rule::unique('custom_pages')->where('user_id', Auth::id()),
            ],
            'slug' => [
                'nullable',
                'string',
                'min:3',
                'max:250',
                Rule::unique('custom_pages'),
            ],
            'body' => 'required|string|min:1',
            'meta' => 'nullable|array',
        ]);

        $page = app(CrupdatePage::class)->execute(
            $this->page->newInstance(),
            $validatedData,
        );

        return $this->success(['page' => $page]);
    }

    public function update(int $id)
    {
        $page = $this->page->findOrFail($id);
        $this->authorize('update', $page);

        $validatedData = $this->validate($this->request, [
            'title' => [
                'string',
                'min:3',
                'max:250',
                Rule::unique('custom_pages')
                    ->where('user_id', $page->user_id)
                    ->ignore($page->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'min:3',
                'max:250',
                Rule::unique('custom_pages')->ignore($page->id),
            ],
            'body' => 'string|min:1',
            'meta' => 'nullable|array',
        ]);

        $page = app(CrupdatePage::class)->execute($page, $validatedData);

        return $this->success(['page' => $page]);
    }

    public function destroy(string $ids)
    {
        $pageIds = explode(',', $ids);
        $this->authorize('destroy', [get_class($this->page), $pageIds]);

        $this->page->whereIn('id', $pageIds)->delete();

        return $this->success();
    }



  //admin
    public function indexApi()
    {
        $page = CustomPage::all();
        return PageResource::collection($page);
    }

    public function showApi($id)
    {
        $page = CustomPage::find($id);

        if (!$page) {
            return response()->json([
                'status' => 'error',
                'message' => 'Page not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new PageResource($page),
        ]);
    }

    public function storApi(Request $request)
    {
        $validator = Validator::make($request->all(), [
        'title' => 'required|string',
        'body' => 'required|string',
        'slug' => 'required|string',
        'meta' => 'nullable|string',
        'type' => 'required|string',
        ]);

        if ($validator->fails()) {
        $errors = $validator->errors();
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $errors,
        ], 422);
        }

        $page=CustomPage::create([
        'title' => $request->title,
        'body' => $request->body,
        'slug' => $request->slug,
        'meta' => $request->meta,
        'type' => $request->type,
            ]);

        //message
        return response()->json([
        'status' => 'success',
        'message' => 'Data validated and saved successfully',
        'data' => $page,
        ]);
    }
    public function updateApi(Request $request, $id)
    {
        $page = CustomPage::find($id);
    
        if (!$page) {
            return response()->json([
                'status' => 'error',
                'message' => 'Page not found',
            ], 404);
        }
    
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'body' => 'required|string',
            'slug' => 'required|string',
            'meta' => 'nullable|string',
            'type' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 422);
        }
    
        $page->update([
            'title' => $request->title,
            'body' => $request->body,
            'slug' => $request->slug,
            'meta' => $request->meta,
            'type' => $request->type,
        ]);
    
        return response()->json([
            'status' => 'success',
            'message' => 'Page updated successfully',
            'data' => new PageResource($page),
        ]);
    }
    public function deleteApi($id)
    {
        $page = CustomPage::find($id);

        if (!$page) {
            return response()->json([
                'status' => 'error',
                'message' => 'Page not found',
            ], 404);
        }

        $page->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Page deleted successfully',
        ]);
    }

}
