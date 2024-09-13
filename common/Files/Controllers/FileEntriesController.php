<?php namespace Common\Files\Controllers;

use Illuminate\Support\Facades\Auth;
use Common\Core\BaseController;
use Common\Database\Datasource\Datasource;
use Common\Files\Actions\CreateFileEntry;
use Common\Files\Actions\Deletion\DeleteEntries;
use Common\Files\Actions\StoreFile;
use Common\Files\Actions\ValidateFileUpload;
use Common\Files\Events\FileUploaded;
use Common\Files\FileEntry;
use Common\Files\FileEntryPayload;
use Common\Files\Response\FileResponseFactory;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class FileEntriesController extends BaseController
{
    public function __construct(
        protected Request $request,
        protected FileEntry $entry,
    ) {
        // $this->middleware('auth')->only(['index']);
    }

    // public function index()
    // {
    //     $params = $this->request->all();
    //     $params['userId'] = $this->request->get('userId');
    
    //     // scope files to current user by default if it's an API request
    //     if (!requestIsFromFrontend() && !$params['userId']) {
    //         $params['userId'] =  Auth::guard('api')->id();
    //     }
    
    //     $query = $this->entry->where('type', '!=' ,'folder');
    
    //     // add user scope if userId is specified
    //     if ($params['userId']) {
    //         $query->where('owner_id', $params['userId']);
    //     }
    
    //     // create datasource with filtered query
    //     $dataSource = new Datasource($query, $params);
    
    //     // paginate the results
    //     $pagination = $dataSource->paginate();
    
    //     return $this->success(['pagination' => $pagination]);
    // }


    public function index()
    {
        $params = $this->request->all();
        $params['userId'] = $this->request->get('userId');
    
        $query = $this->entry->where('type', '!=', 'folder');
        
        // Scope files to current user by default if it's an API request
        if (!requestIsFromFrontend() && !$params['userId']) {
            $query->where('owner_id', Auth::guard('api')->id());
        }
    
        // Create datasource with filtered query
        // Uncomment and adjust the instantiation of Datasource if it exists
        // $dataSource = new Datasource($query, $params);
    
        // Assuming you want to paginate the results directly from the query
        $pagination = $query->paginate($params['perPage'] ?? 15); // Default perPage to 15 if not provided
    
        return $this->success(['pagination' => $pagination]);
    }
    
    


    public function details(FileEntry $fileEntry, FileResponseFactory $response)
    {
        try {
            return $response->create($fileEntry);
        } catch (FileNotFoundException $e) {
            abort(404);
        }
    }

    // public function show($id, FileResponseFactory $response)
    // {
    //     // $this->authorize('show', $fileEntry);
        
    //     try {
    //         $fileEntry = FileEntry::findOrFail($id);
        
    //     // قم بإنشاء المسار الكامل للملف
    //     $filePath = public_path('uploads/' . $fileEntry->file_name);
    //     // return $filePath ;
    
    //         // تحقق مما إذا كان الملف موجودًا
    //         if (!file_exists($filePath)) {
    //             abort(404);
    //         }
    
    //         // إرجاع استجابة تعرض الملف
    //         return response()->file($filePath);
    //     } catch (\Exception $e) {
    //         // التعامل مع الأخطاء الأخرى
    //         return response()->json(['error' => 'Error retrieving file: ' . $e->getMessage()], 500);
    //     }
    // }
    

    public function show($id, FileResponseFactory $response)
    {
        try {
            $fileEntry = FileEntry::findOrFail($id);
            // $filePath = public_path('uploads/' . $fileEntry->file_name);
            return $response->create($fileEntry);
        } catch (FileNotFoundException $e) {
            abort(404);
        }
    }

    public function showModel(FileEntry $fileEntry)
    {
        return $this->success(['fileEntry' => $fileEntry]);
    }

    // public function store()
    // {
    //     $parentId = (int) request('parentId') ?: null;
    //     request()->merge(['parentId' => $parentId]);
    
    //     $this->validate($this->request, [
    //         'file' => 'required',
    //         'file.*' => [
    //             'required',
    //             'file',
    //             function ($attribute, UploadedFile $value, $fail) {
    //                 $errors = app(ValidateFileUpload::class)->execute([
    //                     'extension' => $value->guessExtension(),
    //                     'size' => $value->getSize(),
    //                 ]);
    //                 if ($errors) {
    //                     $fail($errors->first());
    //                 }
    //             },
    //         ],
    //         'parentId' => 'nullable|exists:file_entries,id',
    //         'relativePath' => 'nullable|string',
    //     ]);
    
    //     $files = $this->request->file('file');
    //     if (!is_array($files)) {
    //         $files = [$files];
    //     }
    //     $fileEntries = [];
    //     try {
    //         foreach ($files as $file) {
    //             $payload = new FileEntryPayload(array_merge($this->request->all(), ['file' => $file]));
    //             $fileEntry = app(CreateFileEntry::class)->execute($payload);
    //             event(new FileUploaded($fileEntry));
    //             $fileEntries[] = $fileEntry->load('users');
    //         }
    
    //         return $this->success(['fileEntries' => $fileEntries], 201);
    //     } catch (\Exception $e) {
    //         // Handle the error, log it, or return a response with the error message
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }


    public function store()
    {
        $parentId = (int) request('parentId') ?: null;
        request()->merge(['parentId' => $parentId]);
    
        $this->authorize('store', [FileEntry::class, $parentId]);
    
        $this->validate($this->request, [
            'file' => 'required',
            'file.*' => [
                'required',
                'file',
                function ($attribute, UploadedFile $value, $fail) {
                    $errors = app(ValidateFileUpload::class)->execute([
                        'extension' => $value->guessExtension(),
                        'size' => $value->getSize(),
                    ]);
                    if ($errors) {
                        $fail($errors->first());
                    }
                },
            ],
            'parentId' => 'nullable|exists:file_entries,id',
            'relativePath' => 'nullable|string',
        ]);
    
        $files = $this->request->file('file');
        if (!is_array($files)) {
            $files = [$files];
        }

        $fileEntries = [];
        try {
            foreach ($files as $file) {
                // Generate a unique file name
                $fileName = time() . '-' . $file->getClientOriginalName();
                $fileUrl = asset('uploads/' . $fileName);
                $payload = new FileEntryPayload(array_merge($this->request->all(), ['file' => $file]));
                $fileEntry = app(CreateFileEntry::class)->execute($payload);
                event(new FileUploaded($fileEntry));
                $fileEntries[] = $fileEntry->load('users');
                $file->move(public_path('uploads'), $fileName);
            }
            return $this->success(['fileEntries' => $fileEntries], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error storing files: ' . $e->getMessage()], 500);
        }
    }
    
    // public function store()
    // {
    //     $validatedData = $this->validate(request(), [
    //         'clientExtension' => 'required|string',
    //         'clientMime' => 'nullable|string|max:255',
    //         'clientName' => 'required|string',
    //         'disk' => 'string',
    //         'diskPrefix' => 'string',
    //         'filename' => 'required|string',
    //         'parentId' => 'nullable|exists:file_entries,id',
    //         'relativePath' => 'nullable|string',
    //         'workspaceId' => 'nullable|int',
    //         'size' => 'required|int',
    //     ]);

    //     $payload = new FileEntryPayload($validatedData);

    //     $fileEntry = app(CreateFileEntry::class)->execute($payload);

    //     event(new FileUploaded($fileEntry));

    //     return $this->success(['fileEntry' => $fileEntry]);
    // }

    public function update(int $entryId)
    {
        // $this->authorize('update', [FileEntry::class, [$entryId]]);

        $this->validate($this->request, [
            'name' => 'string|min:3|max:200',
            'description' => 'nullable|string|min:3|max:200',
        ]);
        $params = $this->request->all();
        $entry = $this->entry->findOrFail($entryId);

        $entry->fill($params)->update();

        return $this->success(['fileEntry' => $entry->load('users')]);
    }

    // public function destroy(string $entryIds = null)
    // {
    //     if ($entryIds) {
    //         $entryIds = explode(',', $entryIds);
    //     } else {
    //         $entryIds = $this->request->get('entryIds');
    //     }

    //     $userId = Auth::guard('api')->id() ?: 1;

    //     $this->validate($this->request, [
    //         'entryIds' => 'array|exists:file_entries,id',
    //         'paths' => 'array',
    //         'deleteForever' => 'boolean',
    //         'emptyTrash' => 'boolean',
    //     ]);

    //     // get all soft deleted entries for user, if we are emptying trash
    //     if ($this->request->get('emptyTrash')) {
    //         $entryIds = $this->entry
    //             ->where('owner_id', $userId)
    //             ->onlyTrashed()
    //             ->pluck('id')
    //             ->toArray();
    //     }
    //     app(DeleteEntries::class)->execute([
    //         'paths' => $this->request->get('paths'),
    //         'entryIds' => $entryIds,
    //         'soft' =>
    //         !$this->request->get('deleteForever', true) &&
    //         !$this->request->get('emptyTrash'),
    //     ]);

    //     return $this->success();
    // }


    public function destroy(string $entryIds = null)
    {
        if ($entryIds) {
            $entryIds = explode(',', $entryIds);
        } else {
            $entryIds = $this->request->get('entryIds');
        }
        $userId = Auth::guard('api')->id();
    
        $this->validate($this->request, [
            'entryIds' => 'array|exists:file_entries,id',
            'paths' => 'array',
            'deleteForever' => 'boolean',
            'emptyTrash' => 'boolean',
        ]);
    
        if ($this->request->get('deleteForever')) {
            app(DeleteEntries::class)->execute([
                'paths' => $this->request->get('paths'),
                'entryIds' => $entryIds,
                'soft' => false,
            ]);
        } elseif ($this->request->get('emptyTrash')) {
            $entryIds = $this->entry
                ->where('owner_id', $userId)
                ->onlyTrashed()
                ->pluck('id')
                ->toArray();
            app(DeleteEntries::class)->execute([
                'paths' => $this->request->get('paths'),
                'entryIds' => $entryIds,
                'soft' => false, 
            ]);
        } else {
            app(DeleteEntries::class)->execute([
                'paths' => $this->request->get('paths'),
                'entryIds' => $entryIds,
                'soft' => true,
            ]);
        }
    
        return $this->success();
    }
    



}
