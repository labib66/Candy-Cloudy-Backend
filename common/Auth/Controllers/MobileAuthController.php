<?php

namespace Common\Auth\Controllers;

use App\Models\User;
use Common\Core\BaseController;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUser;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class MobileAuthController extends BaseController
{
    public function login(Request $request)
{
    try {
        // Validate the request input
        $this->validate($request, [
            Fortify::username() => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Retrieve the user based on the username (email)
        $user = User::where(Fortify::username(), $request->get(Fortify::username()))->first();

        // Check if the user exists
        if (!$user) {
            Log::error('User not found: ' . $request->get(Fortify::username()));
            throw ValidationException::withMessages([
                Fortify::username() => [trans('auth.failed')],
            ]);
        }

        // Check if the provided password matches the stored password
        if (!Hash::check($request->get('password'), $user->password)) {
            Log::error('Password mismatch for user: ' . $request->get(Fortify::username()));
            throw ValidationException::withMessages([
                Fortify::username() => [trans('auth.failed')],
            ]);
        }

        // Log the user in
        Auth::login($user);

        // Generate a token for the user
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
        ]);
    } catch (\Exception $e) {
        Log::error('Login error: ' . $e->getMessage());
        return response()->json(['message' => 'There was an issue. Please try again later.'], 500);
    }
}

public function register(
    Request $request,
    CreatesNewUsers $creator,
): JsonResponse {
    $this->validate($request, [
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|confirmed|min:8',
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'gender' => 'required|in:male,female',
        'age' => 'required|integer',
        'job_occubation' => 'required|string|max:255',
        'add_company'=> 'required|string|max:255',
    ]);

    // dd($request->post());
    event(new Registered(($user = $creator->create($request->post()))));

    Auth::login($user);

    $response = [
        'status' => 'success',
        'user' => [
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'gender' => $user->gender,
            'age' => $user->age,
            'job_occubation' => $user->job_occubation,
            'add_company' => $user->add_company,
        ],
    ];

    return response()->json($response);
}

}


