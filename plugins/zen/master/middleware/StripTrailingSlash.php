<?php

namespace Zen\Master\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Единообразные канонические URL без завершающего слэша (кроме корня "/").
 */
class StripTrailingSlash
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->runningInBackend()) {
            return $next($request);
        }

        if (!$request->isMethod('get') && !$request->isMethod('head')) {
            return $next($request);
        }

        $path = $request->getPathInfo();

        if ($path === '/' || substr($path, -1) !== '/') {
            return $next($request);
        }

        $targetPath = rtrim($path, '/') ?: '/';
        $query = $request->getQueryString();
        $uri = $targetPath . ($query ? '?' . $query : '');

        return redirect($uri, 301);
    }
}
