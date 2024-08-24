<?php namespace Common\Auth\Roles;

use App\Models\User;
use Common\Core\BaseController;
use Illuminate\Support\Facades\Auth;

class UserRolesController extends BaseController
{
    public function attach()
    {
        $userId = Auth::guard('api')->id();
        $user = User::findOrFail($userId);

        $this->authorize('update', $user);

        $data = $this->validate(request(), [
            'roles' => 'array',
            'roles.*' => 'integer|exists:roles,id',
        ]);

        $user->roles()->attach($data['roles']);

        return $this->success();
    }

    public function detach()
    {
        $userId = Auth::guard('api')->id();
        $user = User::findOrFail($userId);

        $this->authorize('update', $user);

        $data = $this->validate(request(), [
            'roles' => 'array',
        ]);

        return $user->roles()->detach($data['roles']);
    }


    public function updateRoles($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }    
        $data = $this->validate(request(), [
            'roles' => 'array',
            'roles.*' => 'integer|exists:roles,id',
        ]);
        $user->roles()->sync($data['roles']);
        return response()->json(['success' => true]);
    }
    
    public function getRoles($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        $roles = $user->roles;
        return response()->json($roles);
    }
    




}
