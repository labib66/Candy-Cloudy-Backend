<?php namespace Common\Auth\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Common\Auth\Actions\CreateUser;
use Common\Auth\Actions\DeleteUsers;
use Common\Auth\Actions\PaginateUsers;
use Common\Auth\Actions\UpdateUser;
use Common\Auth\Requests\CrupdateUserRequest;
use Common\Core\BaseController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends BaseController
{
    public function __construct()
    {
        // $this->middleware('auth', ['except' => ['show']]);
    }

    public function index()
    {
        $this->authorize('index', User::class);

        $pagination = (new PaginateUsers())->execute(request()->all());

        return $this->success(['pagination' => $pagination]);
    }

    public function user_settings($id)
    {
        // $this->authorize('index', User::class);

        $user = User::where('id', $id)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }     
        $data =  [
            'details' =>[
                'username'=> $user->username ?? '',
                'first_name'=> $user->first_name ?? '',
                'last_name'=> $user->last_name ?? '',
                'avatar'=> $user->avatar ?? '',
                'email'=> $user->email ?? '',
                'language'=> $user->language ?? '',
                'country'=> $user->country ?? '',
                'timezone'=> $user->timezone ?? '',
            ],
        ];
        return $this->success(['pagination' => $data]);
    }

    public function update_user_settings($id, Request $request)
    {
        $this->validate($request, [
            'username' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'language' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:100',
        ]);
    
        $user = User::findOrFail($id);
    
        if ($request->hasFile('avatar')) {
            if ($user->getRawOriginal('avatar')) {
                Storage::disk('public')->delete($user->getRawOriginal('avatar'));
            }
    
            $image = $request->file('avatar');
            $imageName = Str::uuid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storePubliclyAs('avatars', $imageName, ['disk' => 'public']);
            $user->avatar = $path;
        }
    
        $user->update($request->except('avatar'));
        $user->save();
    
        return response()->json(['success' => true], 200);
    }

    public function updatePassword($id , Request $request)
    { 
      $user = User::find($id);
      if (!$user) {
        return response()->json([
            'error' => 'User not found',
        ], 404);
      }

      $validatedData = Validator::make($request->all(), [
        'old_password' => 'required|string',
        'new_password' => 'required|string|min:8|confirmed',
        'new_password_confirmation' => 'required|string|min:8|confirmed',
  
      ]);
      if ($validatedData->fails()) {
        return response()->json([
          'error' => $validatedData->errors(),
        ], 422); 
      }
  
      if (!Hash::check($request->old_password, $user->password)) {
        return response()->json([
          'error' => 'Incorrect old password',
        ], 401);
      }
      $user->update([
        'password' => Hash::make($request->new_password),
      ]);

      return response()->json([
        'message' => 'Password updated successfully',
      ], 200);
    }
  

    
    
    public function show(User $user)
    {
        $relations = array_filter(explode(',', request('with', '')));
        $relations = array_merge(['roles', 'social_profiles'], $relations);

        if (settings('envato.enable')) {
            $relations[] = 'purchase_codes';
        }

        if (Auth::guard('api')->id() === $user->id) {
            $relations[] = 'tokens';
            $user->makeVisible([
                'two_factor_confirmed_at',
                'two_factor_recovery_codes',
            ]);
            if ($user->two_factor_confirmed_at) {
                $user->two_factor_recovery_codes = $user->recoveryCodes();
                $user->syncOriginal();
            }
        }

        $user->load($relations);

        $this->authorize('show', $user);

        return $this->success(['user' => $user]);
    }

    public function store(CrupdateUserRequest $request)
    {
        $this->authorize('store', User::class);

        $user = (new CreateUser())->execute($request->validated());

        return $this->success(['user' => $user], 201);
    }

    public function update(User $user, CrupdateUserRequest $request)
    {
        $this->authorize('update', $user);

        $user = (new UpdateUser())->execute($user, $request->validated());

        return $this->success(['user' => $user]);
    }

    public function destroy(string $ids)
    {
        $userIds = explode(',', $ids);
        $shouldDeleteCurrentUser = request('deleteCurrentUser');
        // $this->authorize('destroy', [User::class, $userIds]);

        $users = User::whereIn('id', $userIds)->get();

        // guard against current user or admin user deletion
        foreach ($users as $user) {
            if (!$shouldDeleteCurrentUser && $user->id === Auth::guard('api')->id()) {
                return $this->error(
                    __('Could not delete currently logged in user: :email', [
                        'email' => $user->email,
                    ]),
                );
            }

            if ($user->hasPermission('admin')) {
                return $this->error(
                    __('Could not delete admin user: :email', [
                        'email' => $user->email,
                    ]),
                );
            }
        }

        (new DeleteUsers())->execute($users->pluck('id')->toArray());

        return $this->success();
    }
}
