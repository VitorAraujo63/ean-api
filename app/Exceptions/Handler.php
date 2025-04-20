<?php

public function handle($request, Closure $next, $role)
{
    if (!$request->user() || !$request->user()->hasRole($role)) {
        abort(403, 'Unauthorized');
    }

    return $next($request);
}
