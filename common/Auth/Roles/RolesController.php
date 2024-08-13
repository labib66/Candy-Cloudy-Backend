<?php namespace Common\Auth\Roles;

use App\Models\User;
use Common\Core\BaseController;
use Common\Database\Datasource\Datasource;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use App\Http\Resources\RoleResource;

class RolesController extends BaseController
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var Role
     */
    private $role;

    /**
     * @var Request
     */
    private $request;

    public function __construct(Request $request, Role $role, User $user)
    {
        $this->role = $role;
        $this->user = $user;
        $this->request = $request;
    }

    public function show(Role $role)
    {
        $this->authorize('show', Role::class);

        $role->load(['permissions']);

        return $this->success(['role' => $role]);
    }

    public function index()
    {
        $this->authorize('index', Role::class);

        $pagination = (new Datasource(
            $this->role,
            request()->all(),
        ))->paginate();

        return $this->success(['pagination' => $pagination]);
    }

    public function store()
    {
        $this->authorize('store', Role::class);

        $this->validate($this->request, [
            'name' => 'required|unique:roles|min:2|max:255',
            'default' => 'nullable|boolean',
            'guests' => 'nullable|boolean',
            'permissions' => 'nullable|array',
        ]);

        $role = app(CrupdateRole::class)->execute($this->request->all());

        return $this->success(['role' => $role], 201);
    }

    public function update(int $id)
    {
        $this->authorize('update', Role::class);

        $this->validate($this->request, [
            'name' => "min:2|max:255|unique:roles,name,$id",
            'default' => 'boolean',
            'guests' => 'boolean',
            'permissions' => 'array',
        ]);

        $role = $this->role->findOrFail($id);

        $role = app(CrupdateRole::class)->execute($this->request->all(), $role);

        return $this->success(['role' => $role]);
    }

    public function destroy(int $id)
    {
        $role = $this->role->findOrFail($id);

        $this->authorize('destroy', $role);

        $role->users()->detach();
        $role->delete();

        return $this->success([], 204);
    }

    public function addUsers(int $roleId)
    {
        $this->authorize('update', Role::class);

        $this->validate($this->request, [
            'userIds' => 'required|array|min:1|max:25',
            'userIds.*' => 'required|int',
        ]);

        $role = $this->role->findOrFail($roleId);

        $users = $this->user
            ->with('roles')
            ->whereIn('id', $this->request->get('userIds'))
            ->get(['email', 'id']);

        if ($users->isEmpty()) {
            return $this->error(
                __('Could not attach specified users to role.'),
            );
        }

        //filter out users that are already attached to this role
        $users = $users->filter(function ($user) use ($roleId) {
            return !$user->roles->contains('id', $roleId);
        });

        $role->users()->attach($users->pluck('id')->toArray());

        return $this->success(['users' => $users]);
    }

    public function removeUsers(int $roleId)
    {
        $this->authorize('update', Role::class);

        $this->validate($this->request, [
            'userIds' => 'required|array|min:1',
            'userIds.*' => 'required|integer',
        ]);

        $role = $this->role->findOrFail($roleId);

        $role->users()->detach($this->request->get('userIds'));

        return $this->success();
    }


    //admin -----------------------------------
    public function indexApi()
    {
        $roles = Role::all();
        return RoleResource::collection($roles);
    }
    public function storApi(Request $request)
    {
        $validator = Validator::make($request->all(), [
        'name' => 'required|unique:roles|string',
        'legacy_permissions' => 'nullable|boolean',
        'default' => 'required|boolean',
        'guests' => 'required|boolean',
        'type' => 'required|in:workspace,sitewide',
        // 'internal' => 'required|boolean',
        'description' => 'nullable|string',

        ]);
        if ($validator->fails()) {
        $errors = $validator->errors();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 422);
        }

        $role= Role::create([
        "name"=>$request->name,
        "legacy_permissions"=>$request->legacy_permissions,
        "default"=>$request->default,
        "guests"=>$request->guests,
        "type"=>$request->type,
        // "internal"=>$request->internal,
        "description"=>$request->description,
        ]);

        return response()->json([
        'status' => 'success',
        'message' => 'Data validated and saved successfully',
        'data' => $role,
        ]);
    }

    public function showApi($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'error' => 'Role not found'
            ], 404);
        }
        return new RoleResource($role);
    }



    public function updateApi(Request $request,$id){
        //validation
        $validator = Validator::make($request->all(), [
        'name' => 'required|unique:roles|string',
        'legacy_permissions' => 'nullable|boolean',
        'default' => 'required|boolean',
        'guests' => 'required|boolean',
        'type' => 'required|in:workspace,sitewide',
        // 'internal' => 'required|boolean',
        'description' => 'nullable|string', 

        ]);

        if ($validator->fails()) {
        $errors = $validator->errors();
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $errors,
        ], 422);
        }

        $role=Role::find($id);
        if ($role === null) {
        return response()->json([
            "error" => "Role not found"
        ], 404);
        }

        $role->update([
        "name"=>$request->name,
        "legacy_permissions"=>$request->legacy_permissions,
        "default"=>$request->default,
        "guests"=>$request->guests,
        "type"=>$request->type,
        // "internal"=>$request->internal,
        "description"=>$request->description,
        ]);

        return response()->json([
        'status' => 'success',
        'message' => 'Role updated successfully',
        'data' => $role,
    ]);
    }

    public function deleteApi($id){
        $role=Role::find($id);
        if ($role === null) {
        return response()->json([
            "error" => "Role not found"
        ], 404);
        }
        $role->delete();
        return response()->json([
        "success" => "Role Deleted successfully",
        ]);

    }
}
