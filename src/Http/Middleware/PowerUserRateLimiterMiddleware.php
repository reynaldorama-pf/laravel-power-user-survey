<?php

namespace PeopleFinders\LaravelPowerUserSurvey\Http\Middleware;

use Closure;
use PeopleFinders\LaravelPowerUserSurvey\PowerUserState;

class PowerUserRateLimiterMiddleware
{
    private $state;

    public function __construct(PowerUserState $state)
    {
        $this->state = $state;
    }

    public function handle($request, Closure $next)
    {
        if (!config('power-user-survey.enabled')) return $next($request);

        $path = '/' . ltrim($request->path(), '/');

        foreach ((array) config('power-user-survey.exclude_prefixes', []) as $prefix) {
            $prefix = trim($prefix);
            if ($prefix !== '' && strpos($path, $prefix) === 0) return $next($request);
        }

        $applyOnly = (array) config('power-user-survey.apply_only_prefixes', []);
        if (!empty($applyOnly)) {
            $matched = false;
            foreach ($applyOnly as $prefix) {
                $prefix = trim($prefix);
                if ($prefix !== '' && strpos($path, $prefix) === 0) { $matched = true; break; }
            }
            if (!$matched) return $next($request);
        }

        $ip = $request->ip();
        $now = time();
        $pageviews = max(1, (int) config('power-user-survey.limits.pageviews_per_cycle'));
        $captchaCycles = max(0, (int) config('power-user-survey.limits.captcha_cycles'));
        $blockHours = max(1, (int) config('power-user-survey.limits.block_hours'));

        $state = $this->state->get($ip);

        // Block expired => require captcha to re-enter
        if (!empty($state['blocked_until']) && $now >= (int) $state['blocked_until']) {
            $state['blocked_until'] = null;
            $state['cycle'] = 0;
            $state['views'] = 0;
            $state['cooldown_until'] = null;
            $state['require_captcha'] = true;
            $state['pending_captcha'] = true;
            $state['reentry_captcha'] = true;
        }

        // Still blocked => survey mode
        if (!empty($state['blocked_until']) && $now < (int) $state['blocked_until']) {
            $this->state->put($ip, $state, $this->state->ttlSecondsFor($state));
            return $this->redirectToRateLimited($request, 'survey');
        }

        // Cooldown => unlimited browsing, no counting
        if (!empty($state['cooldown_until']) && $now < (int) $state['cooldown_until']) {
            $this->state->put($ip, $state, $this->state->ttlSecondsFor($state));
            return $next($request);
        }

        // Captcha required => enforce immediately before serving any content
        if (!empty($state['require_captcha'])) {
            $this->state->put($ip, $state, $this->state->ttlSecondsFor($state));
            return $this->redirectToRateLimited($request, 'captcha');
        }

        // Determine whether this is a real HTML page navigation.
        // Modern browsers send Sec-Fetch-Dest/Sec-Fetch-Mode on every fetch; use those
        // as the primary signal so favicon, manifest, JS, CSS and XHR requests are never
        // counted.  Fall back to an Accept: text/html check only for old browsers/curl
        // that do not send Sec-Fetch-* headers at all.
        $accept       = strtolower((string) $request->header('Accept', ''));
        $secFetchMode = strtolower((string) $request->header('Sec-Fetch-Mode', ''));
        $secFetchDest = strtolower((string) $request->header('Sec-Fetch-Dest', ''));
        $purpose      = strtolower((string) $request->header('Purpose', ''));
        $isPrefetch   = ($purpose === 'prefetch') || (strtolower((string) $request->header('X-Moz', '')) === 'prefetch');
        $hasFetchHeaders = $secFetchMode !== '' || $secFetchDest !== '';
        $isDocumentNavigation = $hasFetchHeaders
            ? ($secFetchDest === 'document' || $secFetchMode === 'navigate')
            : strpos($accept, 'text/html') !== false;
        $shouldCountPageView = $request->isMethod('get') && !$request->ajax() && !$request->expectsJson() && !$isPrefetch && $isDocumentNavigation;

        if (!$shouldCountPageView) {
            $this->state->put($ip, $state, $this->state->ttlSecondsFor($state));
            return $next($request);
        }

        // Execute the request first, THEN count — this is intentional.
        // Counting after the response means app-internal redirects (canonical URLs,
        // trailing-slash normalisation, etc.) are NEVER double-counted: only a real
        // 200 page delivery increments the view counter.
        $response = $next($request);

        // Only count 2xx responses (skip redirects, errors)
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return $response;
        }

        // Deduplicate: only count when the URL changes within this session.
        // Prevents same-page reloads (F5) from inflating the count.
        $currentUrl = (string) $request->fullUrl();
        $previousUrl = (string) $request->session()->get('pus.last_counted_url', '');
        if ($previousUrl !== '' && $previousUrl === $currentUrl) {
            $this->state->put($ip, $state, $this->state->ttlSecondsFor($state));
            return $response;
        }
        $request->session()->put('pus.last_counted_url', $currentUrl);

        // Increment pageview for this unique-URL successful page delivery.
        $state['views'] = (int) ($state['views'] ?? 0) + 1;

        if ($state['views'] >= $pageviews) {
            if ((int) ($state['cycle'] ?? 0) >= $captchaCycles) {
                // All captcha cycles done => block for PUS_BLOCK_HOURS.
                // Current page was already served; next request will show survey.
                $state['blocked_until'] = $now + ($blockHours * 3600);
                $state['views'] = 0;
                $state['cooldown_until'] = null;
                $state['require_captcha'] = false;
                $state['pending_captcha'] = false;
                $state['reentry_captcha'] = false;
            } else {
                // Set captcha flag for the NEXT request.
                // Current page was already served; next request will show captcha.
                $state['require_captcha'] = true;
                $state['pending_captcha'] = true;
                $state['views'] = 0;
            }
        }

        $this->state->put($ip, $state, $this->state->ttlSecondsFor($state));
        return $response;
    }

    protected function redirectToRateLimited($request, $mode)
    {
        $request->session()->put('pus.rate_limited.mode', $mode);
        $request->session()->put('pus.rate_limited.redirect_to', $mode === 'captcha' ? $request->fullUrl() : null);

        return redirect()->route('pus.rate_limited');
    }
}
