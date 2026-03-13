<?php

namespace PeopleFinders\LaravelPowerUserSurvey\Http\Middleware;

use Closure;

class PowerUserNoCacheHtmlMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (!$request->isMethod('get')) {
            return $response;
        }

        if (!config('power-user-survey.enabled')) {
            return $response;
        }

        $path = '/' . ltrim($request->path(), '/');

        foreach ((array) config('power-user-survey.exclude_prefixes', []) as $prefix) {
            $prefix = trim($prefix);
            if ($prefix !== '' && strpos($path, $prefix) === 0) {
                return $response;
            }
        }

        $applyOnly = (array) config('power-user-survey.apply_only_prefixes', []);
        if (!empty($applyOnly)) {
            $matched = false;
            foreach ($applyOnly as $prefix) {
                $prefix = trim($prefix);
                if ($prefix !== '' && strpos($path, $prefix) === 0) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return $response;
            }
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if (strpos($contentType, 'text/html') !== false) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            $response->headers->remove('ETag');
        }

        return $response;
    }
}
