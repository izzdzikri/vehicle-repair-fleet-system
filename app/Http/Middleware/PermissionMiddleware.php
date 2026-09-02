<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        if (!Auth::check() || !Auth::user()->hasPermission($permission)) {
            abort(403, 'You do not have permission to perform this action.');
        }
        return $next($request);
    }
}