<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowedRoles = collect($roles)
            ->flatMap(fn (string $role) => explode(',', $role))
            ->map(fn (string $role) => trim($role))
            ->filter()
            ->all();

        if (! auth()->check() || ! auth()->user()->hasRole(...$allowedRoles)) {
            return redirect()->route('dashboard')
                ->withErrors(['permission' => 'No tienes permiso para acceder a esta sección.']);
        }

        return $next($request);
    }
}
