<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::with('roles')->find(Auth::guard('api')->id());
        $isAdmin = false;
        foreach ($user['roles'] as $role) {
            if ($role['name'] === 'admin') {
                $isAdmin = true;
                break;
            }
        }
        if (!$isAdmin) {
            return response('You do not have admin access.');
        }

        return $next($request);
    }
}
