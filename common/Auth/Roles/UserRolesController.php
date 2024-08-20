<?php namespace Common\Auth\Roles;

use App\Models\User;
use Common\Core\BaseController;

class UserRolesController extends BaseController
{
    public function attach(int $userId)
    {
        $user = User::findOrFail($userId);

        $this->authorize('update', $user);

        $data = $this->validate(request(), [
            'roles' => 'array',
            'roles.*' => 'integer|exists:roles,id',
        ]);

        $user->roles()->attach($data['roles']);

        return $this->success();
    }

    public function detach(int $userId)
    {
        $user = User::findOrFail($userId);

        $this->authorize('update', $user);

        $data = $this->validate(request(), [
            'roles' => 'array',
        ]);

        return $user->roles()->detach($data['roles']);
    }

    public function updateRoles(int $userId)
{
    $user = User::findOrFail($userId);


    $data = $this->validate(request(), [
        'roles' => 'array',
        'roles.*' => 'integer|exists:roles,id',
    ]);

    $user->roles()->sync($data['roles']);

    return response()->json(['success' => true]);
}

public function getRoles(int $userId)
{
    $user = User::findOrFail($userId);

    $roles = $user->roles;

    return response()->json($roles);
}



}
