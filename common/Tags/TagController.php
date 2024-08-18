<?php

namespace Common\Tags;

use App\Models\Tag as AppTag;
use Common\Core\BaseController;
use Common\Database\Datasource\Datasource;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class TagController extends BaseController
{
    public function index()
    {
        $this->authorize('index', Tag::class);

        $builder = $this->getModel()->newQuery();

        if ($type = request('type')) {
            $builder->where('type', $type);
        }

        if ($notType = request('notType')) {
            $builder->where('type', '!=', $notType);
        }

        // don't show "label" tags in bedrive
        $builder->where('type', '!=', 'label');

        $dataSource = new Datasource($builder, request()->all());

        $pagination = $dataSource->paginate();

        return $this->success(['pagination' => $pagination]);
    }

    public function store()
    {
        $this->authorize('store', Tag::class);

        $this->validate(request(), [
            'name' => 'required|string|min:2|unique:tags',
            'display_name' => 'string|min:2',
            'type' => 'required|string|min:2',
        ]);

        $tag = $this->getModel()->create([
            'name' => request('name'),
            'display_name' => request('display_name'),
            'type' => request('type'),
        ]);

        return $this->success(['tag' => $tag]);
    }

    public function update(int $tagId)
    {
        $this->authorize('update', Tag::class);

        $this->validate(request(), [
            'name' => "string|min:2|unique:tags,name,$tagId",
            'display_name' => 'string|min:2',
            'type' => 'string|min:2',
        ]);

        $tag = $this->getModel()->findOrFail($tagId);

        $tag->fill(request()->all())->save();

        return $this->success(['tag' => $tag]);
    }

    public function destroy(string $ids)
    {
        $tagIds = explode(',', $ids);
        $this->authorize('destroy', [Tag::class, $tagIds]);

        $this->getModel()
            ->whereIn('id', $tagIds)
            ->delete();
        DB::table('taggables')
            ->whereIn('tag_id', $tagIds)
            ->delete();

        return $this->success();
    }

    protected function getModel(): Tag
    {
        return app(class_exists(AppTag::class) ? AppTag::class : Tag::class);
    }




    
  public function indexApi()
  {
    $tags = Tag::all();
    return TagResource::collection($tags);
  }
  public function storApi(Request $request)
  {
    // validation
    $validator = Validator::make($request->all(), [
      "name" => "required|string",
      "display_name" => "required|string",
      "type" => "required"
    ]);
       // Check for validation errors
       if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors(),
            'message' => 'Validation failed' // message
        ], 422); // 422 Unprocessable Entity is more suitable for validation errors
    }

    //create
    $tag= Tag::create([
        "name"=>$request->name,
        "display_name"=>$request->display_name,
        "type"=>$request->type
    ]);
    //message
     // Successful creation response
     return response()->json([
      'data' => $tag,
      'message' => 'Tag created successfully' // message
    ], 201); // 201 Created is suitable for successful resource creation

  }

    public function showApi($id)
    {
        $tag = Tag::find($id);

        if (!$tag) {
            return response()->json([
                'message' => 'Tag not found'
            ], 404);
        }

        return new TagResource($tag);
    }


    public function updateApi(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required|string",
            "display_name" => "required|string",
            "type" => "required"
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation failed'
            ], 422);
        }

        $tag = Tag::find($id);

        if (!$tag) {
            return response()->json([
                'message' => 'Tag not found'
            ], 404);
        }

        $tag->update([
            "name" => $request->name,
            "display_name" => $request->display_name,
            "type" => $request->type
        ]);

        return response()->json([
            'data' => $tag,
            'message' => 'Tag updated successfully'
        ], 200);
    }

    public function deleteApi($id)
    {
        $tag = Tag::find($id);

        if (!$tag) {
            return response()->json([
                'message' => 'Tag not found'
            ], 404);
        }

        $tag->delete();

        return response()->json([
            'message' => 'Tag deleted successfully'
        ], 200);
    }



}
