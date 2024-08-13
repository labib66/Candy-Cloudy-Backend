<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserApiController extends Controller
{  

  public function handleUsers(Request $request)
  {
      $query = User::query();

      if ($request->has('search')) {
        $email = $request->query('search');
          $query->where('email', 'LIKE', '%' . $email . '%');
      }
  
      if ($request->has('role')) {
          $role = $request->query('role');
          $query->whereHas('permissions', function ($query) use ($role) {
            $query->where('name', $role);
        });
        $users = $query->get(); 
        return UserResource::collection($users);
        }

      if ($request->has('page')) {
          $page = (int)$request->query('page', 1);
          $perPage = 10;
          $offset = ($page - 1) * $perPage;
          $users = $query->offset($offset)->limit($perPage)->get();
          return UserResource::collection($users);
      }

      $users = $query->get();
      return UserResource::collection($users);
  }
  
  

    public function showApi($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'error' => 'User not found',
            ], 404);
        }
        return new UserResource($user);


    }

  public function storeApi(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'username' => 'required|string|max:255',
      'first_name' => 'nullable|string|max:255',
      'last_name' => 'nullable|string|max:255',
      'avatar_url' => 'nullable|url',
      'gender' => 'nullable|in:male,female',
      'permissions' => 'nullable',
      'email' => 'required|string|email|max:255|unique:users,email',
      "password" => "required|min:6|confirmed"

    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'error',
        'message' => 'Validation failed',
        'errors' => $validator->errors(),
      ], 422);
    }
    //password hash
    $password = bcrypt($request->password);

    // If validation passes, create the user
    $user = User::create([
      'username' => $request->username,
      'first_name' => $request->first_name,
      'last_name' => $request->last_name,
      'avatar_url' => $request->avatar_url,
      'gender' => $request->gender,
      'permissions' => $request->permissions,
      'email' => $request->email,
      'password' => $password,
    ]);

    return response()->json([
      'status' => 'success',
      'message' => 'User created successfully',
      'data' => $user,
    ]);
  }
  public function update(Request $request, $id)
  {
    // Find the user by ID
    $user = User::find($id);

    // Check if user exists
    if ($user === null) {
      return response()->json([
        "error" => "User not found"
      ], 404);
    }
    //  return response()->json(["test"=>$user->username]);
    //validtion
    $validator = Validator::make($request->all(), [
      'username' => 'required|string|max:255',
      'first_name' => 'nullable|string|max:255',
      'last_name' => 'nullable|string|max:255',
      'avatar_url' => 'nullable|url',
      'gender' => 'nullable|in:male,female',
      'permissions' => 'nullable',
    ]);
    //check error
    if ($validator->fails()) {
      return response()->json([
        'status' => 'error',
        'message' => 'Validation failed',
        'errors' => $validator->errors(),
      ], 422);
    }


    // Update user information
    $user->update([
      'username' => $request->username,
      'first_name' => $request->first_name,
      'last_name' => $request->last_name,
      'avatar_url' => $request->avatar_url,
      'gender' => $request->gender,
      'permissions' => $request->permissions,
    ]);

    // Return a success response
    return response()->json([
      "success" => "User updated successfully",
      "user" => $user
    ]);
  }
  public function delete($id)
  {
    // Find the user by ID
    $user = User::find($id);

    // Check if user exists
    if ($user === null) {
      return response()->json([
        "error" => "User not found"
      ], 404);
    }
    //delete
    $user->delete();
    //message
    return response()->json([
      "success" => "User Deleted successfully",
    ]);
  }

  public function updatePassword(Request $request, $id)
  {
    //find
    // Find the user by ID
    $user = User::find($id);
    // Check if the user exists
    if (!$user) {
      return response()->json([
        'error' => 'User not found',
      ], 404);
    }
    // Validate the request data
    $validatedData = Validator::make($request->all(), [
      'old_password' => 'required|string',
      'new_password' => 'required|string|min:8|confirmed',

    ]);
    // Check if validation fails
    if ($validatedData->fails()) {
      return response()->json([
        'error' => $validatedData->errors(),
      ], 422); // Unprocessable Entity
    }

    // Check the old password
    if (!Hash::check($request->old_password, $user->password)) {
      return response()->json([
        'error' => 'Incorrect old password',
      ], 401);
    }
    // Update the password using $user->update()
    $user->update([
      'password' => Hash::make($request->new_password),
    ]);
    //message
    // Return success message
    return response()->json([
      'message' => 'Password updated successfully',
    ], 200);
  }




  
  // public function searchByEmail( $email)
  // {

  //   // Search for the user by email
  //   $user = User::where('email', $email)->first();
  //   // Check if the user exists
  //   if (!$user) {
  //     return response()->json([
  //       'error' => 'User not found',
  //     ], 404);
  //   }
  //    // Return user details
  //    return response()->json([
  //     'user' => $user,
  //  ], 200);
  // }

  // public function paginateAndLimet($num)
  // {
  //    $perPage = 10;
  //    $offset = ($num - 1) * $perPage;
  //       $users = User::offset($offset)->limit($perPage)->get();
  //       return response()->json($users);
  // }

  // public function indexApi()
  // {
  //   $users = User::all();
  //   return UserResource::collection($users);
  // }

}
